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

if __name__ == '__main__':
    data = get()
    print(data)
