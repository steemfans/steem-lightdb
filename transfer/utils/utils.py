#!/usr/bin/python3
#encoding:UTF-8

import json, os, sys, time, linecache
import traceback

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

    c['undo_count'] = int(env_dist.get('UNDO_COUNT'))
    if c['undo_count'] == 0:
        c['undo_count'] = 50
    
    c['undo_limit'] = int(env_dist.get('UNDO_LIMIT'))
    if c['undo_limit'] == 0:
        c['undo_limit'] = 50

    c['undo_sleep'] = int(env_dist.get('UNDO_SLEEP'))
    if c['undo_sleep'] == 0:
        c['undo_sleep'] = 5

    c['base_sleep'] = int(env_dist.get('BASE_SLEEP'))
    if c['base_sleep'] == 0:
        c['base_sleep'] = 3

    c['base_step'] = int(env_dist.get('BASE_STEP'))
    if c['base_step'] == 0:
        c['base_step'] = 1000

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

def PrintException(msg=''):
    exc_type, exc_obj, tb = sys.exc_info()
    f = tb.tb_frame
    lineno = tb.tb_lineno
    filename = f.f_code.co_filename
    linecache.checkcache(filename)
    line = linecache.getline(filename, lineno, f.f_globals)
    print('EXCEPTION IN ({}, LINE {} "{}"): {}, {}, {}'.format(filename, lineno, line.strip(), exc_obj, msg, traceback.print_exc()))
