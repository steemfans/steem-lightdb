#!/usr/bin/python3
#encoding:UTF-8
import json, os, sys, time
import utils.TransferTasks as tasks
import utils.utils as utils
from utils.BlockProcess import BlockProcess as BlockProcess
import asyncio, aiomysql
from multiprocessing import Pool
from concurrent.futures import ThreadPoolExecutor,ProcessPoolExecutor
from contextlib import suppress
import diff_match_patch as dmp_module

task_type = 'comment'

class CommentsProcess(BlockProcess):
    def __init__(self, loop, data_type):
        super().__init__(loop, data_type)
    async def process(self, block_num, block_time, trans_id, ops):
        global task_type
        db = self.db
        # print('process %i blcok\'s ops' % block_num)
        self.processed_data = {
            'data': [],
            'undo': []}
        for op_idx, op in enumerate(ops):
            try:
                op_type = op[0]
                op_detail = op[1]
                if op_type == 'comment':
                    created_at = block_time
                    updated_at = block_time
                    is_del = False
                    json_metadata = op_detail['json_metadata']
                    parent_author_text = op_detail['parent_author']
                    parent_permlink = op_detail['parent_permlink']
                    author_text = op_detail['author']
                    permlink = op_detail['permlink']
                    title = op_detail['title']

                    # check if comment edit through body
                    body = op_detail['body']
                    dmp = dmp_module.diff_match_patch()
                    try:
                        # if patch_fromText successed, this comment is edited.
                        dmp.patch_fromText(body)
                        self.processed_data['undo'].append((block_num, trans_id, op_idx, json.dumps(op), tasks.getTypeId(task_type), block_time))
                        continue
                    except:
                        is_exist = await self.checkExist(author_text, permlink)
                        if is_exist == False:
                            # this comment is a new comment.
                            self.processed_data['data'].append((
                                None, # parent_id
                                permlink,
                                title,
                                body,
                                json_metadata,
                                parent_permlink,
                                created_at,
                                updated_at,
                                is_del,
                                parent_author_text,
                                author_text))
                        else:
                            # this comment is edited and does not use diff_match_patch
                            self.processed_data['undo'].append((block_num, trans_id, op_idx, json.dumps(op), tasks.getTypeId(task_type), block_time))
                            continue
                elif op_type == 'delete_comment':
                    self.processed_data['undo'].append((block_num, trans_id, op_idx, json.dumps(op), tasks.getTypeId(task_type), block_time))
                    print('do_later_del', block_num, trans_id, op_idx)
            except Exception as e:
                self.processed_data['undo'].append((block_num, trans_id, op_idx, json.dumps(op), tasks.getTypeId(task_type), block_time))
                utils.PrintException([block_num, trans_id, op_idx, e])

        # print('processed:', self.processed_data)
        return self.processed_data

    async def checkExist(self, author_text, permlink):
        db = self.db
        for val in self.processed_data['data']:
            if val[10] == author_text and val[1] == permlink:
                return True
        for val in self.prepared_data['data']:
            if val[10] == author_text and val[1] == permlink:
                return True
        #print('testTEST', author_text, permlink)
        sql = '''select id from comments 
            where author_text = %s
                and permlink = %s'''
        cur = await db.cursor()
        await cur.execute(sql, (author_text, permlink))
        data = await cur.fetchone()
        await cur.close()
        if data != None:
            return True
        return False

    async def insertData(self):
        db = self.db
        try:
            cur = await db.cursor()
            if self.prepared_data['data'] != []:
                sql_main_data = '''
                    insert ignore into comments
                        (
                            parent_id,
                            permlink,
                            title,
                            body,
                            json_metadata,
                            parent_permlink,
                            created_at,
                            updated_at,
                            is_del,
                            parent_author_text,
                            author_text)
                    values
                        (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)'''
                await cur.executemany(sql_main_data, self.prepared_data['data'])
            if self.prepared_data['undo'] != []:
                sql_undo_data = '''
                    insert ignore into undo_op
                        (block_num, transaction_id, op_index, op, task_type, block_time)
                    values
                        (%s, %s, %s, %s, %s, %s)'''
                await cur.executemany(sql_undo_data, self.prepared_data['undo'])
            sql_update_task = '''
                update multi_tasks set is_finished = 1
                where id = %s'''
            await cur.execute(sql_update_task, (self.task_id))
            await db.commit()
            await cur.close()
        except Exception as e:
            await db.rollback()
            await cur.close()
            print('insert_data_failed', 'task_id:', self.task_id, self.prepared_data, e)

def processor(all_tasks):
    global task_type
    if all_tasks != []:
        loop = asyncio.get_event_loop()
        loop_tasks = []
        try:
            for one_task in all_tasks:
                comment_task = CommentsProcess(loop, task_type)
                loop_tasks.append(asyncio.ensure_future(comment_task.doMultiTasks(one_task)))
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
