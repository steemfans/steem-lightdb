#!/usr/bin/python3
#encoding:UTF-8
import json, os, sys, time, signal
import utils.utils as utils
from contextlib import suppress
import utils.TransferTasks as tasks
import pymysql

def main():
    while True:
        config = utils.get_config()
        db_c = config['steem_config']
        db = pymysql.connect(
            host=db_c['host'],
            port=db_c['port'],
            user=db_c['user'],
            password=db_c['pass'],
            charset='utf8mb4',
            cursorclass = pymysql.cursors.DictCursor,
            db=db_c['db'],
            autocommit=False)

        last_block_nums = []
        for task_type in tasks.task_type.values():
            sql = '''select * from multi_tasks
                where task_type = %s
                and is_finished = 1
                order by block_num_to desc
                limit 2'''
            with db.cursor() as cur:
                cur.execute(sql, (task_type, ))
                res = cur.fetchall()
            # leave one finished task
            if len(res) <= 1:
                print('task_type', task_type, 'has_not_finished')
                last_block_nums.append(0)
            else:
                print('add', res[1]['block_num_to'], 'of task_type', task_type)
                last_block_nums.append(res[1]['block_num_to'])
        block_num = min(last_block_nums)
        if block_num > 0:
            print('will_remove_block_num', block_num)
            #remove multi_tasks
            sql = '''delete from multi_tasks
                where block_num_to <= %s'''
            with db.cursor() as cur:
                cur.execute(sql, (block_num, ))
            #remove block_cache
            sql = '''delete from block_cache
                where block_num <= %s'''
            with db.cursor() as cur:
                cur.execute(sql, (block_num, ))
            try:
                print('commit_task')
                db.commit()
            except:
                print('rollback')
                db.rollback()
            db.close()

        print('sleep')
        time.sleep(60*5)

if __name__ == '__main__':
    with suppress(KeyboardInterrupt):
        main()
