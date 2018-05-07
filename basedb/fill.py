#!/usr/bin/python3
#encoding:UTF-8
import json, os, sys, time
import requests
import pymysql
from contextlib import suppress
from steem.blockchain import Blockchain
from steem.steemd import Steemd

env_dist = os.environ

sleep_time = 10
step = 100
last_block_num = 0

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
    print('[warning] DB connection failed', e)
    sys.exit()

# steem lib init
steemd_nodes = [
    'https://rpc.buildteam.io',
    'https://api.steemit.com',
]
s = Steemd(nodes=steemd_nodes)
b = Blockchain(s)

def worker(block_nums):
    global s, b, conn
    print("\nget blocks: {block_nums}\n".format(block_nums=block_nums))
    try:
        block_infos = s.get_blocks(block_nums)
    except Exception as e:
        print('[warning]:', e)
        sendMsg('[warning]: blocks from %s to %s didnot get.  %s' % (start, end, e))
        return
    # print(block_infos)
    for block_info in block_infos:
        transactions = block_info['transactions']
        block_num = block_info['block_num']
        previous = block_info['previous']
        block_id = block_info['block_id']
        timestamp = block_info['timestamp']
        del block_info['transactions']
        del block_info['previous']
        del block_info['block_id']
        del block_info['timestamp']
        tmp_block_info = json.dumps(block_info)
        insert_data = (
            block_num,
            previous,
            block_id,
            tmp_block_info,
            timestamp
        )
        print('[insert_data]:', insert_data)
        with conn.cursor() as cursor:
            try:
                sql = '''
                    insert into `blocks` (
                    `block_num`,
                    `previous`,
                    `block_id`,
                    `block_info`,
                    `timestamp`
                    ) values (%s, %s, %s, %s, %s)'''
                cursor.execute(sql, insert_data)
                conn.commit()
            except Exception as e:
                conn.rollback()
                print('[warning]insert block data error', e, sql, insert_data)
                sendMsg('Error: %s | %s' % (e, sql))
                continue
            block_record_id = int(cursor.lastrowid)
        tmp_trans = []
        for trans in transactions:
            tmp_trans.append((block_record_id, json.dumps(trans), block_num))
        if tmp_trans != []:
            print('[insert_transactions]', tmp_trans)
            with conn.cursor() as cursor:
                try:
                    cursor.executemany(
                        "insert into `transactions` (`block_id`, `content`, `block_num`) values (%s, %s, %s)",
                        tmp_trans
                    )
                    conn.commit()
                except Exception as e:
                    conn.rollback()
                    print('[warning]insert transactions error', e)
                    sendMsg('Error: %s' % e)
                    continue

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
                return int(result['block_num']) + 1
            else:
                return 1
    except Exception as  e:
        print('[warning]get latest block num error', e)
        return 1

def getLostBlocks():
    global last_block_num
    latest_block_num = getLatestBlockNumFromDB()
    if latest_block_num <= 1:
        return False
    else:
        all_list = range(last_block_num + 1, latest_block_num)
        print("block nums from %d to %d\n" % (last_block_num + 1, latest_block_num))
        exists = []
        with conn.cursor() as cursor:
            sql = 'select block_num from blocks where block_num > %s order by block_num asc'
            cursor.execute(sql, (last_block_num))
            results = cursor.fetchall()
            for row in results:
                exists.append(row['block_num'])
        lost = set(all_list) - set(exists)
        return {"lost": list(lost), "latest_block_num": latest_block_num}

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
    global s, b, sleep_time, step, last_block_num

    while True:
        lost = getLostBlocks()
        if (lost == False):
            print("no lost block\n")
            time.sleep(sleep_time)
            continue
        length = len(lost['lost'])
        if (length == 0):
            print("no lost block\n")
            time.sleep(sleep_time)
            continue
        r = [lost['lost'][i:i+step] for i in range(0, length, step)]
        for blocks in r:
            worker(blocks)
        last_block_num = lost['latest_block_num']
        print("\n\n")
        time.sleep(sleep_time)

if __name__ == '__main__':
    with suppress(KeyboardInterrupt):
        run()
