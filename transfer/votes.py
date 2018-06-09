#!/usr/bin/python3
#encoding:UTF-8
import json, os, sys, time
import utils.tasks as tasks
import utils.utils as utils
from utils.BlockProcess import BlockProcess as BlockProcess
import asyncio, aiomysql
from multiprocessing import Pool
from concurrent.futures import ThreadPoolExecutor,ProcessPoolExecutor
from contextlib import suppress
import diff_match_patch as dmp_module

task_type = 'vote'

class VotesProcess(BlockProcess):
    def __init__(self, loop, data_type):
        super().__init__(loop, data_type)
    async def process(self, block_num, block_time, trans_id, ops):
        global task_type
        db1 = self.db1
        db2 = self.db2
        # print('process %i blcok\'s ops' % block_num)
        self.processed_data = {
            'data': [],
            'undo': []}
        for op_idx, op in enumerate(ops):
            try:
                op_type = op[0]
                op_detail = op[1]
                if op_type == 'vote':
                    weight = op_detail['weight']
                    created_at = block_time
                    updated_at = block_time
                    voter_id = await self.getId('users', op_detail['voter'])
                    print(trans_id, op_idx, 'voter_id:', voter_id)
                    if voter_id == None:
                        self.processed_data['undo'].append((block_num, trans_id, op_idx, json.dumps(op), tasks.getTypeId(task_type)))
                        continue
                    post_id = await self.getId('posts', (op_detail['author'], op_detail['permlink']))
                    print(trans_id, op_idx, 'post_id:', post_id)
                    if post_id == None:
                        comment_id = await self.getId('comments', (op_detail['author'], op_detail['permlink']))
                        print(trans_id, op_idx, 'comment_id:', comment_id)
                        if comment_id == None:
                            self.processed_data['undo'].append((block_num, trans_id, op_idx, json.dumps(op), tasks.getTypeId(task_type)))
                            continue
                        else:
                            # vote to comment
                            vote_id = self.getId('comments_votes', (voter_id, comment_id))
                            print(trans_id, op_idx, 'vote_id:', vote_id)
                            if vote_id != None:
                                # edit vote
                                self.processed_data['undo'].append((block_num, trans_id, op_idx, json.dumps(op), tasks.getTypeId(task_type)))
                                continue
                            else:
                                # insert comment vote
                                if weight >= 0:
                                    updown = True
                                else:
                                    weight = (-1) * weight
                                    updown = False 
                                self.processed_data['data'].append(('comment', (comment_id, voter_id, weight, updown, created_at, updated_at)))
                    else:
                        vote_id = self.getId('posts_votes', (voter_id, post_id))
                        if vote_id != None:
                            # edit vote
                            self.processed_data['undo'].append((block_num, trans_id, op_idx, json.dumps(op), tasks.getTypeId(task_type)))
                            continue
                        else:
                            # insert post vote
                            if weight >= 0:
                                updown = True
                            else:
                                weight = (-1) * weight
                                updown = False 
                            self.processed_data['data'].append(('post', (post_id, voter_id, weight, updown, created_at, updated_at)))
            except Exception as e:
                utils.PrintException(e)
                return False
        # print('processed:', self.processed_data)
        return self.processed_data

    async def getId(self, table, val):
        db1 = self.db1
        db2 = self.db2
        if table == 'posts':
            sql = '''select posts.id from posts
                left join users on posts.author_id = users.id
                where users.username = %s
                    and posts.permlink = %s'''
        elif table == 'comments':
            sql = '''select comments.id from comments 
                left join users on comments.author_id = users.id
                where users.username = %s
                    and comments.permlink = %s'''
        elif table == 'comments_votes':
            sql = '''select id from comments_votes 
                where user_id = %s and comment_id = %s'''
        elif table == 'posts_votes':
            sql = '''select id from posts_votes 
                where user_id = %s and post_id = %s'''
        elif table == 'users':
            sql = '''select id from users
                where username = %s'''
        else:
            return None
        
        try:
            cur2 = await db2.cursor()
            await cur2.execute(sql, val)
            data = await cur2.fetchone()
            if data == None:
                return None
            else:
                return data[0]
        except:
            return None

    async def insertData(self):
        db1 = self.db1
        db2 = self.db2
        try:
            cur2 = await db2.cursor()
            if self.prepared_data['data'] != []:
                posts_votes = []
                comments_votes = []
                for val in self.prepared_data['data']:
                    if val[0] == 'post':
                        posts_votes.append(val[1])
                    if val[0] == 'comment':
                        comments_votes.append(val[1])
                sql_post_data = '''
                    insert ignore into posts_votes
                        (
                            post_id,
                            user_id,
                            weight,
                            updown,
                            created_at,
                            updated_at,
                        )
                    values
                        (%s, %s, %s, %s, %s, %s)'''
                await cur2.executemany(sql_post_data, posts_votes)
                sql_comment_data = '''
                    insert ignore into comments_votes
                        (
                            comment_id,
                            user_id,
                            weight,
                            updown,
                            created_at,
                            updated_at,
                        )
                    values
                        (%s, %s, %s, %s, %s, %s)'''
                await cur2.executemany(sql_main_data, self.prepared_data['data'])
            if self.prepared_data['undo'] != []:
                sql_undo_data = '''
                    insert ignore into undo_op
                        (block_num, transaction_id, op_index, op, task_type)
                    values
                        (%s, %s, %s, %s, %s)'''
                await cur2.executemany(sql_undo_data, self.prepared_data['undo'])
            sql_update_task = '''
                update multi_tasks set is_finished = 1
                where id = %s'''
            await cur2.execute(sql_update_task, (self.task_id))
            await db2.commit()
            await cur2.close()
        except Exception as e:
            await db2.rollback()
            print('insert_data_failed', 'task_id:', self.task_id, self.prepared_data, e)

def processor(all_tasks):
    global task_type
    if all_tasks != []:
        loop = asyncio.get_event_loop()
        loop_tasks = []
        try:
            for one_task in all_tasks:
                votes_task = VotesProcess(loop, task_type)
                loop_tasks.append(asyncio.ensure_future(votes_task.doMultiTasks(one_task)))
            loop.run_until_complete(asyncio.wait(loop_tasks))
        except KeyboardInterrupt as e:
            for task in asyncio.Task.all_tasks():
                task.cancel()
            loop.stop()
        finally:
            loop.close()

def mainMultiProcess():
    global task_type
    config = utils.get_config()
    while True:
        all_tasks = tasks.splitTasks(tasks.get(task_type), config['slice_step'])
        if all_tasks != []:
            p = ProcessPoolExecutor(config['worker'])
            for t in all_tasks:
                p.submit(processor, t)
            p.shutdown()
        time.sleep(3)

if __name__ == '__main__':
    with suppress(KeyboardInterrupt):
        mainMultiProcess()
