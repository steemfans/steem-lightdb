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

task_type = 'user'

class UserProcess(BlockProcess):
    def __init__(self, loop, data_type):
        super().__init__(loop, data_type)
    async def process(self, block_num, block_time, trans_id, ops):
        db = self.db
        # print('process %i blcok\'s ops' % block_num)
        self.processed_data = {
            'data': [],
            'undo': []}
        for op_idx, op in enumerate(ops):
            op_type = op[0]
            op_detail = op[1]
            if op_type == 'account_create':
                username = op_detail['new_account_name']
                json_metadata = op_detail['json_metadata']
                is_pow = False
            elif op_type == 'pow':
                username = op_detail['worker_account']
                json_metadata = None
                is_pow = True 
            elif op_type == 'pow2':
                username = op_detail['work'][1]['input']['worker_account']
                json_metadata = None
                is_pow = True 
            elif op_type == 'account_create_with_delegation':
                username = op_detail['new_account_name']
                json_metadata = op_detail['json_metadata']
                is_pow = False
            else:
                # print('unknown type:', op_type)
                continue
            if self.checkExist(username) == False:
                sql = '''
                    select username from users
                    where username = %s limit 1'''
                cur = await db.cursor()
                await cur.execute(sql, (username, ))
                is_exist = await cur.fetchone()
                await cur.close()
                if is_exist == None:
                    # print(username)
                    self.processed_data['data'].append((username, json_metadata, block_time, is_pow, ))
        # print('processed:', self.processed_data)
        return self.processed_data
    def checkExist(self, username):
        for val in self.processed_data['data']:
            if val[0] == username:
                return True
        for val in self.prepared_data['data']:
            if val[0] == username:
                return True
        return False

    async def insertData(self):
        db = self.db
        try:
            cur = await db.cursor()
            if self.prepared_data['data'] != []:
                sql_main_data = '''
                    insert ignore into users
                        (username, json_metadata, created_at, is_pow)
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

def main():
    global task_type
    while True:
        all_tasks = tasks.get(task_type)
        loop_tasks = []
        if all_tasks != []:
            loop = asyncio.get_event_loop()
            for one_task in all_tasks:
                user_task = UserProcess(loop, task_type)
                loop_tasks.append(asyncio.ensure_future(user_task.doMultiTasks(one_task)))
            loop.run_until_complete(asyncio.wait(loop_tasks))
            loop.close()
        time.sleep(3)

def processor(all_tasks):
    global task_type
    if all_tasks != []:
        loop = asyncio.get_event_loop()
        loop_tasks = []
        try:
            for one_task in all_tasks:
                user_task = UserProcess(loop, task_type)
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
