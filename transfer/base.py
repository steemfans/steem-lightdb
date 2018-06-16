#!/usr/bin/python3
#encoding:UTF-8
import json, os, sys, time, signal
import utils.BaseTasks as BaseTasks
import utils.utils as utils
from utils.BlockProcess import BlockProcess as BlockProcess
from contextlib import suppress
from steem.blockchain import Blockchain
from steem.steemd import Steemd
import queue
import threading
import pymysql

config = utils.get_config()
base_step = config['base_step']
base_thread_count = config['base_thread_count']

steemd_nodes = [
    'https://rpc.buildteam.io',
    'https://steemd.privex.io',
    'https://api.steemit.com',
    'https://rpc.steemviz.com',
]
s = Steemd(nodes=steemd_nodes, maxsize=base_step*base_thread_count)
b = Blockchain(s)

def processor(task_queue):
    global s, b
    while task_queue.qsize():
        tasks = task_queue.get()
        print('get_blocks_start', tasks)
        task_start_time = time.time()
        try:
            block_infos = s.get_blocks(tasks)
        except:
            print('get_data_from_chain_failed:', tasks)
            return
    
        print('got_data', tasks)
        insert_data = []
        for block in block_infos:
            block_num = block['block_num']
            previous = block['previous']
            block_id = block['block_id']
            timestamp = utils.strtotime(block['timestamp'])
            del block['previous']
            del block['block_id']
            del block['timestamp']
            del block['block_num']
            tmp_block_info = json.dumps(block)
            insert_data.append((
                block_num,
                previous,
                block_id,
                tmp_block_info,
                timestamp))

        r = saveToMysql(insert_data)
        if r == True:
            task_queue.task_done()
            task_end_time = time.time()
            print('get_blocks_end', tasks, task_end_time - task_start_time)
        else:
            task_end_time = time.time()
            print('get_blocks_end_failed', tasks, task_end_time - task_start_time)
            return

def saveToMysql(insert_data):
    config = utils.get_config()
    db_c = config['steem_config']
    db = pymysql.connect(
        host=db_c['host'],
        port=db_c['port'],
        user=db_c['user'],
        password=db_c['pass'],
        charset='utf8mb4',
        db=db_c['db'],
        autocommit=False)

    with db.cursor() as cursor:
        try:
            sql = '''
                insert into `block_cache` (
                `block_num`,
                `previous`,
                `block_id`,
                `block_info`,
                `timestamp`
                ) values (%s, %s, %s, %s, %s)'''
            cursor.executemany(sql, insert_data)
            db.commit()
            return True
        except Exception as e:
            db.rollback()
            print('[warning]insert block cache error', e, sql, insert_data)
            return False
    db.close()

def quit(signum, frame):
    print('quit')
    sys.exit()

def mainMultiProcess():
    try:
        config = utils.get_config()
        base_sleep = config['base_sleep']
        base_slice_step = config['base_slice_step']
        base_thread_count = config['base_thread_count']

        signal.signal(signal.SIGINT, quit)
        signal.signal(signal.SIGTERM, quit)

        while True:
            all_tasks = BaseTasks.get()
            if all_tasks == []:
                print('no_tasks')
                continue

            #task_length = len(all_tasks)
            #slice_tasks = [ all_tasks[i:i+base_slice_step] for i in range(0, task_length, base_slice_step) ]
            task_queue = queue.Queue()
            for tmp_tasks in all_tasks:
                task_queue.put(tmp_tasks)

            # make multi threads
            thread_list = []
            for n in range(base_thread_count):
                t_t = threading.Thread(target=processor, args=(task_queue, ))
                thread_list.append(t_t)
            for t in thread_list:
                t.setDaemon(True)
                t.start()
            task_queue.join() # suspend before all tasks finished

            time.sleep(base_sleep)
    except Exception as e:
        utils.PrintException()
        sys.exit()

if __name__ == '__main__':
    with suppress(KeyboardInterrupt):
        mainMultiProcess()
