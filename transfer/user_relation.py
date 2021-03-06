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

task_type = 'user_relation'

class UserRelationProcess(BlockProcess):
    def __init__(self, loop, data_type):
        super().__init__(loop, data_type)
    async def process(self, block_num, block_time, trans_id, ops):
        global task_type
        db = self.db
        # print('process %i blcok\'s ops' % block_num, ops)
        self.processed_data = {
            'data': [],
            'undo': []}
        for op_idx, op in enumerate(ops):
            op_type = op[0]
            op_detail = op[1]
            if op_type == 'custom_json' and 'id' in op_detail and op_detail['id'] == 'follow':
                if op_detail['json'] == '':
                    continue
                try:
                    json_data = json.loads(op_detail['json'])

                    follower = None
                    following = None
                    what = None
                    if isinstance(json_data, dict):
                        if 'follower' in json_data:
                            follower = json_data['follower']
                        if 'following' in json_data:
                            following = json_data['following']
                        if 'what' in json_data and isinstance(json_data['what'], list) and len(json_data['what']) > 0:
                            what = json_data['what'][0]
                    #elif isinstance(json_data, list):
                    #    if len(json_data) >= 2 and json_data[0] == 'follow':
                    #        if 'follower' in json_data[1]:
                    #            follower = json_data[1]['follower']
                    #        if 'following' in json_data[1]:
                    #            following = json_data[1]['following']
                    #        if 'what' in json_data[1] and len(json_data[1]['what']) > 0:
                    #            what = json_data[1]['what'][0]
                    #    else:
                    #        continue
                    else:
                        continue
                    if follower == None and following == None and (what == None or what == ''):
                        print('follow_data_error', block_num, trans_id, follower, following, what, op)
                        continue

                    sql = '''
                        select id, username from users
                        where username = %s or username = %s'''

                    cur = await db.cursor()
                    await cur.execute(sql, (follower, following))
                    user_data = await cur.fetchall()
                    await cur.close()

                    if len(user_data) == 2:
                        for user in user_data:
                            if user[1] == follower:
                                follower_id = user[0]
                            if user[1] == following:
                                following_id = user[0]
                        self.processed_data['data'].append((follower_id, following_id, what, block_time, ))
                    else:
                        self.processed_data['undo'].append((block_num, trans_id, op_idx, json.dumps(op), tasks.getTypeId(task_type), block_time))
                except Exception as e:
                    self.processed_data['undo'].append((block_num, trans_id, op_idx, json.dumps(op), tasks.getTypeId(task_type), block_time))
                    utils.PrintException([block_num, trans_id, op_idx])
            else:
                # print('unknown type:', op_type, block_num, trans_id, ops, op_idx)
                continue
        # print('processed:', self.processed_data)
        return self.processed_data

    async def insertData(self):
        db = self.db
        try:
            cur = await db.cursor()
            if self.prepared_data['data'] != []:
                sql_main_data = '''
                    insert ignore into user_relations
                        (follower_id, following_id, what, created_at)
                    values
                        (%s, %s, %s, %s)'''
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
            print('insert_data_failed', 'task_id:', self.task_id, e)

def processor(all_tasks):
    global task_type
    if all_tasks != []:
        loop = asyncio.get_event_loop()
        loop_tasks = []
        try:
            for one_task in all_tasks:
                user_task = UserRelationProcess(loop, task_type)
                loop_tasks.append(asyncio.ensure_future(user_task.doMultiTasks(one_task)))
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
