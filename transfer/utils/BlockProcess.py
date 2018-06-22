import json, os, sys, time
import utils.TransferTasks as tasks
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
            db_c = config['steem_config']

            db = await aiomysql.connect(
                    host=db_c['host'],
                    port=db_c['port'],
                    user=db_c['user'],
                    password=db_c['pass'],
                    charset='utf8mb4',
                    db=db_c['db'],
                    autocommit=False,
                    loop=self.loop)
            self.db = db

            task_start_time = time.time()

            self.task_id = task['id']
            self.block_from = task['block_num_from']
            self.block_to = task['block_num_to']
            print('task_id:', self.task_id, 'from:', self.block_from, 'to:', self.block_to)

            # prepare data
            sql = '''
                select block_num, block_info, timestamp from block_cache
                where block_num >= %s and block_num <= %s
                order by block_num asc'''
            cur = await db.cursor()
            await cur.execute(sql, (self.block_from, self.block_to))
            blocks = await cur.fetchall()
            await cur.close()
            self.prepared_data = {
                'data': [],
                'undo': []}
            has_err = False
            for block in blocks:
                curr_block_num = block[0]
                curr_block_info = json.loads(block[1])
                curr_block_timestamp = block[2]
                if curr_block_info['transaction_ids'] != []:
                    for idx, trans in enumerate(curr_block_info['transactions']):
                        curr_block_trans_id = curr_block_info['transaction_ids'][idx]
                        curr_block_trans = trans
                        processed_data = await self.process(
                            curr_block_num,
                            curr_block_timestamp,
                            curr_block_trans_id,
                            curr_block_trans['operations'])
                        if processed_data == False:
                            has_err = True
                            break;
                        if processed_data['data'] != []:
                            tmp_len = len(self.prepared_data['data'])
                            self.prepared_data['data'][tmp_len:tmp_len] = processed_data['data']
                        if processed_data['undo'] != []:
                            tmp_len = len(self.prepared_data['undo'])
                            self.prepared_data['undo'][tmp_len:tmp_len] = processed_data['undo']
                    if has_err == True:
                        break;
            if has_err == True:
                print('there are some unexpected errors. task_id ', self.task_id, 'will not run.')
            else:
                # insert data
                await self.insertData()

            # end task
            db.close()
            self.db = None
            task_end_time = time.time()
            print('task_id:', self.task_id, 'db closed', 'task_spent:', task_end_time - task_start_time)
        except Exception as e:
            utils.PrintException(e)
    async def process(self, block_num, block_time, trans_id, ops):
        print('process parent', block_num, block_time, trans_id, ops)
    async def insertData(self):
        print('insertData parent', self.task_id, self.prepared_data)
