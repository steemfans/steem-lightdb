import json, os, sys, time
import utils.tasks as tasks
import utils.utils as utils
import asyncio, aiomysql

class BlockProcess(object):
    def __init__(self, loop, data_type):
        self.loop = loop
        self.data_type = data_type
        self.config = utils.get_config()

    async def doMultiTasks(self, task):
        try:
            config = self.config
            db1_c = config['steemdb_config']
            db2_c = config['steem_config']

            db1 = await aiomysql.connect(
                    host=db1_c['host'],
                    port=db1_c['port'],
                    user=db1_c['user'],
                    password=db1_c['pass'],
                    db=db1_c['db'],
                    autocommit=False,
                    loop=self.loop)
            db2 = await aiomysql.connect(
                    host=db2_c['host'],
                    port=db2_c['port'],
                    user=db2_c['user'],
                    password=db2_c['pass'],
                    db=db2_c['db'],
                    autocommit=False,
                    loop=self.loop)
            self.db1 = db1
            self.db2 = db2

            task_start_time = time.time()

            self.task_id = task['id']
            self.block_from = task['block_num_from']
            self.block_to = task['block_num_to']
            print('task_id:', self.task_id, 'from:', self.block_from, 'to:', self.block_to)

            # prepare data
            sql = '''
                select block_num, block_info, timestamp from blocks
                where block_num >= %s and block_num <= %s
                order by block_num asc'''
            cur1 = await db1.cursor()
            await cur1.execute(sql, (self.block_from, self.block_to))
            blocks = await cur1.fetchall()
            await cur1.close()
            self.prepared_data = {
                'data': [],
                'undo': []}
            for block in blocks:
                curr_block_num = block[0]
                curr_block_info = json.loads(block[1])
                curr_block_timestamp = utils.strtotime(block[2])
                if curr_block_info['transaction_ids'] != []:
                    sql = '''
                        select block_num, content from transactions
                        where block_num = %s
                        order by id asc'''
                    cur1 = await db1.cursor()
                    await cur1.execute(sql, (curr_block_num))
                    curr_block_all_transactions = await cur1.fetchall()
                    await cur1.close()
                    for idx, trans in enumerate(curr_block_all_transactions):
                        curr_block_trans_id = curr_block_info['transaction_ids'][idx]
                        curr_block_trans = json.loads(trans[1])
                        processed_data = await self.process(
                            curr_block_num,
                            curr_block_timestamp,
                            curr_block_trans_id,
                            curr_block_trans['operations'])
                        if processed_data['data'] != []:
                            tmp_len = len(self.prepared_data['data'])
                            self.prepared_data['data'][tmp_len:tmp_len] = processed_data['data']
                        if processed_data['undo'] != []:
                            tmp_len = len(self.prepared_data['undo'])
                            self.prepared_data['undo'][tmp_len:tmp_len] = processed_data['undo']
            # insert data
            await self.insertData()

            # end task
            task_end_time = time.time()
            print('task_spent:', task_end_time - task_start_time)
            db1.close()
            db2.close()
            self.db1 = None
            self.db2 = None
        except Exception as e:
            utils.PrintException(e)
    async def process(self, block_num, block_time, trans_id, ops):
        print('process parent', block_num, block_time, trans_id, ops)
    async def insertData(self):
        print('insertData parent', self.task_id, self.prepared_data)
