#!/usr/bin/python3
#encoding:UTF-8

import pymysql
import json, os, sys, time
import utils.utils as utils
from steem.blockchain import Blockchain
from steem.steemd import Steemd

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
    checkLostBlockCache(db, config)

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
        'https://rpc.buildteam.io',
        'https://api.steemit.com',
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

    result = []
    i = start_num
    while i <= end_num:
        tmp_end_num = i + base_step
        if tmp_end_num >= end_num:
            tmp_end_num = end_num
        result.append(range(i, tmp_end_num + 1))
        i = tmp_end_num + 1

    return result

def checkLostBlockCache(db, config):
    print('check_lost_block_cache')
    # get synced head_block_num
    sql = 'select max(block_num) as max_num, min(block_num) as min_num from block_cache'
    with db.cursor() as cur:
        cur.execute(sql)
        res = cur.fetchone()
        if res['min_num'] == None or res['max_num'] == None:
            print('records_not_exist')
            return
    start_num = int(res['min_num'])
    end_num = int(res['max_num'])

    sql = 'select block_num from block_cache where block_num >= %s and block_num <= %s order by block_num asc'
    with db.cursor() as cur:
        cur.execute(sql, (start_num, end_num))
        res = cur.fetchall()
        print('abc', res)
        if res == ():
            print('records_not_exist2')
            return
    exists_num = []
    records_count = len(res)
    
    if (end_num - start_num + 1) <= records_count:
        print('no_lost_block_cache')
        return

    print('detect_lost_block_cache')
    all_list = range(start_num, end_num + 1)
    exists_num = []
    for r in res:
        exists_num.append(r['block_num'])
    lost_blocks = list(set(all_list) - set(exists))

    # grouped
    #length = len(lost_blocks)
    #if length == 0:
    #    return
    #base_step = config['base_step']
    #r = [lost[i:i+base_step] for i in range(0, length, base_step)]
    try:
        block_infos = s.get_blocks(lost_blocks)
    except Exception as e:
        print('got_lost_block_error', e)
        return

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
            conn.commit()
        except Exception as e:
            conn.rollback()
            print('[warning]insert lost block cache error', e, sql, insert_data)
            return
    db.close()

if __name__ == '__main__':
    data = get()
    print(data)
