#!/usr/bin/python3
#encoding:UTF-8
import json, os, sys, time
import requests
import pymysql
from contextlib import suppress
from steem.blockchain import Blockchain
from steem.steemd import Steemd

env_dist = os.environ

step = 10 - 1 
sleep_time = 3

# get block_num from env
env_block_num = env_dist.get('BLOCK_NUM')

# get discord webhook
discord_webhook = env_dist.get('DISCORD')

# init db config
DB_HOST = env_dist.get('DB_HOST')
if DB_HOST == None:
    print('Need DB_HOST config')
    sys.exit()
DB_USER = env_dist.get('DB_USER')
if DB_USER == None:
    print('Need DB_USER config')
    sys.exit()
DB_PASS = env_dist.get('DB_PASS')
if DB_PASS == None:
    print('Need DB_PASS config')
    sys.exit()
DB_NAME = env_dist.get('DB_NAME')
if DB_NAME == None:
    print('Need DB_NAME config')
    sys.exit()
# init db
try:
    conn = pymysql.connect(
        host = DB_HOST,
        user = DB_USER,
        password = DB_PASS,
        database = DB_NAME,
        charset = 'utf8',
        cursorclass = pymysql.cursors.DictCursor)
except Exception as e:
    print(e)
    sys.exit()

# steem lib init
steemd_nodes = [
    'https://rpc.buildteam.io',
    'https://api.steemit.com',
]
s = Steemd(nodes=steemd_nodes)
b = Blockchain(s)

# [start, end]
def worker(start, end):
    global s, b, conn
    print('start from {start} to {end}'.format(start=start, end=end))
    block_infos = s.get_blocks(range(start, end+1))
    # print(block_infos)
    block_num = start
    for block_info in block_infos:
        transactions = block_info['transactions']
        del block_info['transactions']
        tmp_block_info = json.dumps(block_info)
        insert_data = (
            block_num,
            block_info['previous'],
            block_info['block_id'],
            tmp_block_info,
            block_info['timestamp']
        )
        sql = '''
            insert into `blocks` (
            `block_num`,
            `previous`,
            `block_id`,
            `block_info`,
            `timestamp`
            ) values (%d, '%s', '%s', '%s', '%s')''' % insert_data
        with conn.cursor() as cursor:
            cursor.execute(sql)
            block_record_id = int(cursor.lastrowid)
        conn.commit()
        tmp_trans = []
        for trans in transactions:
            tmp_trans.append((block_record_id, json.dumps(trans)))
        if tmp_trans != []:
            with conn.cursor() as cursor:
                cursor.executemany('insert into `transactions` (`block_id`, `content`) \
                    values (%d, %s)', tmp_trans)
            conn.commit()
        block_num += 1

def getLatestBlockNumFromDB():
    global conn 
    sql = '''
    Select block_num from blocks
    Order by block_num desc limit 1;
    '''
    try:
        with conn.cursor() as cursor:
            cursor.execute(sql)
            result = cursor.fetchone()
            if result:
                return result['block_num']
            else:
                return 1
    except Exception as  e:
        print(e)
        return 1

def sendMsg(msg):
    global discord_webhook
    # print(discord_webhook)
    if discord_webhook != None:
        try:
            r = requests.post(discord_webhook, data={"content": msg})
            print(r.text)
        except Exception as e:
            print(e)
    else:
        print('discord url not found')

def run():
    global s, b, env_block_num, step, sleep_time
    if env_block_num != None:
        start_block_num = env_block_num
    else:
        start_block_num = getLatestBlockNumFromDB()

    while True:
        head_block_number = b.info()['head_block_number']
        end_block_num = int(head_block_number)
        # end_block_num = 34

        if start_block_num > end_block_num:
            print(':zap: WARNING: start_block_num > end_block_num')
            sendMsg(':zap: WARNING: start_block_num > end_block_num')
            time.sleep(sleep_time)
            continue

        tmp_start = start_block_num
        tmp_end = tmp_start + step
        while tmp_end <= end_block_num:
            worker(tmp_start, tmp_end)
            tmp_start = tmp_end + 1
            tmp_end = tmp_start + step
        worker(tmp_start, end_block_num)

        start_block_num = end_block_num + 1
        time.sleep(sleep_time)

if __name__ == '__main__':
    with suppress(KeyboardInterrupt):
        run()
