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

task_type = 'tag'

class TagProcess(BlockProcess):
    def __init__(self, loop, data_type):
        super().__init__(loop, data_type)
    async def process(self, block_num, block_time, trans_id, ops):
        db1 = self.db1
        db2 = self.db2
        # print('process %i blcok\'s ops' % block_num)
        self.processed_data = {
            'data': [],
            'undo': []}
        for op_idx, op in enumerate(ops):
            op_type = op[0]
            op_detail = op[1]
            if op_type == 'comment' and op_detail['parent_author'] == '':
                self.processed_data['data'].append((op_detail['parent_permlink'], ))
                if op_detail['json_metadata'] == '':
                    continue
                try:
                    json_metadata = json.loads(op_detail['json_metadata'])
                    if isinstance(json_metadata, list):
                        if 'tags' in json_metadata:
                            for tag in json_metadata['tags']:
                                if await self.checkExist(tag) == False:
                                    self.processed_data['data'].append((tag, ))
                    else:
                        print('invalid json_metadata:', json_metadata)
                except Exception as e:
                    utils.PrintException([block_num, trans_id, ops, e])
                    return False
            else:
                # print('unknown type:', op_type)
                continue

        # print('processed:', self.processed_data)
        return self.processed_data

    async def checkExist(self, tag):
        for val in self.processed_data['data']:
            if val[0] == tag:
                return True
        for val in self.prepared_data['data']:
            if val[0] == tag:
                return True
        sql = '''select id from tags
            where tag_name = %s'''
        db2 = self.db2
        cur2 = await db2.cursor()
        await cur2.execute(sql, (tag,))
        data = await cur2.fetchall()
        await cur2.close()
        if len(data) > 0:
            return True
        return False

    async def insertData(self):
        db1 = self.db1
        db2 = self.db2
        try:
            cur2 = await db2.cursor()
            if self.prepared_data['data'] != []:
                sql_main_data = '''
                    insert ignore into tags
                        (tag_name)
                    values
                        (%s)'''
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
            print('insert_data_failed', 'task_id:', self.task_id, e)

def processor(all_tasks):
    global task_type
    if all_tasks != []:
        loop = asyncio.get_event_loop()
        loop_tasks = []
        try:
            for one_task in all_tasks:
                tag_task = TagProcess(loop, task_type)
                loop_tasks.append(asyncio.ensure_future(tag_task.doMultiTasks(one_task)))
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
