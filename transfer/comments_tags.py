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

task_type = 'comment_tag'

class CommentsTagsProcess(BlockProcess):
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
                    json_metadata = op_detail['json_metadata']
                    try:
                        json_metadata = json.loads(op_detail['json_metadata'])
                    except Exception as e:
                        print('parse json failed:', op_detail['json_metadata'])
                        continue

                    comment_id = await self.getId('comments', (op_detail['author'], op_detail['permlink']))
                    if comment_id == None:
                        self.processed_data['undo'].append((block_num, trans_id, op_idx, json.dumps(op), tasks.getTypeId(task_type), block_time))
                        continue

                    if 'tags' in json_metadata:
                        if isinstance(json_metadata['tags'], list):
                            for tag in json_metadata['tags']:
                                tag_id = await self.getId('tags', tag)
                                if tag_id != None:
                                    self.processed_data['data'].append((comment_id, tag_id))
            except Exception as e:
                self.processed_data['undo'].append((block_num, trans_id, op_idx, json.dumps(op), tasks.getTypeId(task_type), block_time))
                utils.PrintException([block_num, trans_id, op_idx, op])
        # print('processed:', self.processed_data)
        return self.processed_data

    async def getId(self, table, val):
        db = self.db
        if table == 'comments':
            sql = '''select id from comments
                where author_text = %s
                and permlink = %s'''
        elif table == 'tags':
            sql = '''select id from tags
                where tag_name = %s'''
        else:
            return None
        
        try:
            cur = await db.cursor()
            await cur.execute(sql, val)
            data = await cur.fetchone()
            if data == None:
                return None
            else:
                return data[0]
        except:
            return None

    async def insertData(self):
        db = self.db
        try:
            cur = await db.cursor()
            if self.prepared_data['data'] != []:
                # delete first
                comment_ids = []
                for v in self.prepared_data['data']:
                    comment_ids.append(v[0])
                tuple_comment_ids = tuple(comment_ids)
                format_strings = ','.join(['%s'] * len(comment_ids))
                sql_del = '''delete from comments_tags
                    where comments_id in (%s)''' % format_strings
                await cur.execute(sql_del, tuple_comment_ids)
                # then insert
                sql_main_data = '''
                    insert ignore into comments_tags
                        (comments_id, tags_id)
                    values
                        (%s, %s)'''
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
                comments_tags_task = CommentsTagsProcess(loop, task_type)
                loop_tasks.append(asyncio.ensure_future(comments_tags_task.doMultiTasks(one_task)))
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
