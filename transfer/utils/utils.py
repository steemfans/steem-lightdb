#!/usr/bin/python3
#encoding:UTF-8

import json, os, sys, time

def get_config():
    c = {}
    env_dist = os.environ
    c['worker'] = env_dist.get('WORKER')
    if c['worker'] == None:
        c['worker'] = 5
    if c['worker'] > 50:
        print('threads are too many')
        sys.exit()
    c['block_step'] = env_dist.get('BLOCK_STEP')
    if c['block_step'] == None:
        c['block_step'] = 100
    try:
        c['steemdb_config'] = json.loads(env_dist.get('STEEMDB_CONFIG'))
    except:
        print('STEEMDB_CONFIG error')
        sys.exit()
    try:
        c['steem_config'] = json.loads(env_dist.get('STEEM_CONFIG'))
    except:
        print('STEEM_CONFIG error')
        sys.exit()
    return c
