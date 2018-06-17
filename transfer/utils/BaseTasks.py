#!/usr/bin/python3
#encoding:UTF-8

import pymysql
import json, os, sys, time
import utils.utils as utils
from steem.blockchain import Blockchain
from steem.steemd import Steemd
import queue
import threading

def get():
    # get db config
    config = utils.get_config()
    db_c = config['steem_config']
    # connect db
    db = pymysql.connect(
        host = db_c['host'],
        user = db_c['user'],
        password = db_c['pass'],
        database = db_c['db'],
        charset = 'utf8mb4',
        cursorclass = pymysql.cursors.DictCursor,
        autocommit = True)

    # check lost block first
    result = checkLostBlockCache(db, config)

    # get synced head_block_num
    sql = 'select block_num from block_cache order by block_num desc limit 1'
    with db.cursor() as cur:
        cur.execute(sql)
        res = cur.fetchone()
        if res == None:
            print('no_block_cache')
            curr_cache_head = 0
        else:
            curr_cache_head = int(res['block_num'])
    db.close()

    print('curr_cache_head: ', curr_cache_head)

    base_step = config['base_step']

    # get lastest block_num
    steemd_nodes = [
        'https://api.steemit.com',
        'https://rpc.buildteam.io',
        #'https://steemd.privex.io',
        #'https://rpc.steemviz.com',
    ]
    s = Steemd(nodes=steemd_nodes)
    b = Blockchain(s)

    head_block_number = int(b.info()['head_block_number'])
    print('blockchain_head', head_block_number)

    if head_block_number <= curr_cache_head:
        print('It is newest', head_block_number, latest_num)
        return []

    start_num = curr_cache_head + 1
    end_num = head_block_number

    i = start_num
    while i <= end_num:
        tmp_end_num = i + base_step
        if tmp_end_num >= end_num:
            tmp_end_num = end_num
        result.append({"task_type": "new_block", "content": range(i, tmp_end_num + 1)})
        i = tmp_end_num + 1

    return result

def updateCheckPoint(db, v):
    print('update_check_point_in_base_tasks', v)
    if v == 1:
        sql = 'insert into config (param, val) values ("check_point", %s)'
    else:
        sql = 'update config set val = %s where param = "check_point"'
    with db.cursor() as cur:
        cur.execute(sql, (v,))

def checkLostBlockCache(db, config):
    print('check_lost_block_cache')
    # get synced head_block_num
    sql = 'select block_num from block_cache order by block_num desc limit 1'
    with db.cursor() as cur:
        cur.execute(sql)
        res = cur.fetchone()
        if res == None:
            print('block_cache_not_exist')
            return []
        else:
            max_num = res['block_num']

    sql = 'select val from config where param = "check_point"'
    with db.cursor() as cur:
        cur.execute(sql)
        res = cur.fetchone()
        if res == None:
            print('check_point_not_exist')
            check_point = 1
            updateCheckPoint(db, 1)
        else:
            print('to_get_check_point', res)
            check_point = int(res['val'])

    sql = 'select block_num from block_cache where block_num >= %s and block_num <= %s order by block_num asc'
    with db.cursor() as cur:
        cur.execute(sql, (check_point, max_num))
        res = cur.fetchall()
        if res == []:
            print('block_cache_not_exist_2')
            return []
    records_count = len(res)
    
    if (max_num - check_point + 1) <= records_count:
        print('no_lost_block_cache')
        return []

    print('detect_lost_block_cache')
    all_list = range(check_point, max_num + 1)
    exists_num = []
    for r in res:
        exists_num.append(r['block_num'])
    lost_blocks = list(set(all_list) - set(exists_num))
    
    # get lost blocks
    base_sleep = config['base_sleep']
    base_slice_step = config['base_slice_step']
    base_thread_count = config['base_thread_count']
    lost_blocks_length = len(lost_blocks)
    if lost_blocks_length <= 0:
        return []
    lost_blocks_slice = [ lost_blocks[i:i+base_slice_step] for i in range(0, lost_blocks_length, base_slice_step) ]
    result = []
    total_slice = len(lost_blocks_slice)
    for lb in lost_blocks_slice:
        result.append({"task_type":"lost_block", "content": lb, "total_slice": total_slice, "next_check_point": max_num + 1})
    return result

if __name__ == '__main__':
    data = get()
    print(data)
