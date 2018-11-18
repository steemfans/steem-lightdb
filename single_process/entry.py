#!/usr/bin/python3
#encoding:UTF-8

import json, os, sys, time, signal
from contextlib import suppress
import utils.utils as utils
from steem.blockchain import Blockchain
from steem.steemd import Steemd
import redis

steemd_nodes = [
    'https://api.steemit.com',
]
s = Steemd(nodes=steemd_nodes)
b = Blockchain(s)
config = utils.get_config()
redis_config = config['redis_config']
pool = redis.ConnectionPool(host=redis_config['host'], port=redis_config['port'])
r = redis.Redis(connection_pool=pool)

def main():
    global s, b, config, pool, r, time
    step = config['block_step']
    if r.get('latest_block_num') == None:
        start_block_num = 1
    else:
        start_block_num = int(r.get('latest_block_num'))
        last_cached_block = json.loads(r.get('block_%d' % (start_block_num-1)))
        last_block_hash = last_cached_block['block_id']
    head_block_number = int(b.info()['head_block_number'])
    while True:
        end_block_num = start_block_num + step - 1
        if head_block_number < end_block_num:
            end_block_num = head_block_number
        if end_block_num <= start_block_num:
            if config['base_sleep'] != 0:
                time.sleep(config['base_sleep'])
            head_block_number = int(b.info()['head_block_number'])
        blocks = s.get_blocks(range(start_block_num, end_block_num))
        for block in blocks:
            print('block_num: %d' % block['block_num'])
            if block['block_num'] > 1:
                if last_block_hash != block['previous']:
                    print('error')
                    sys.exit()
            block_str = json.dumps(block)
            r.set('block_%d' % block['block_num'], block_str)
            r.set('latest_block_num', int(block['block_num']))
            last_block_hash = block['block_id']
        start_block_num = end_block_num

if __name__ == '__main__':
    with suppress(KeyboardInterrupt):
        main()