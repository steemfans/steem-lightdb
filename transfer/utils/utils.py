#!/usr/bin/python3
#encoding:UTF-8

import json, os, sys, time

def get_config():
    c = {}
    env_dist = os.environ

    c['worker'] = int(env_dist.get('WORKER'))
    if c['worker'] == 0:
        c['worker'] = 6 
    if c['worker'] > 50:
        print('threads are too many')
        sys.exit()

    c['block_step'] = int(env_dist.get('BLOCK_STEP'))
    if c['block_step'] == 0:
        c['block_step'] = 100

    c['slice_step'] = int(env_dist.get('SLICE_STEP'))
    if c['slice_step'] == 0:
        c['slice_step'] = 50

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

def strtotime(string, format_string = "%Y-%m-%dT%H:%M:%S"):
    tuple = time.strptime(string, format_string)
    return int(time.mktime(tuple))
