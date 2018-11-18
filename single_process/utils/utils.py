#!/usr/bin/python3
#encoding:UTF-8

import json, os, sys, time, linecache
import traceback

def get_config():
    c = {}
    env_dist = os.environ

    c['block_step'] = int(env_dist.get('BLOCK_STEP'))
    if c['block_step'] == 0:
        c['block_step'] = 1000

    c['base_sleep'] = int(env_dist.get('BASE_SLEEP'))
    if c['base_sleep'] == 0:
        c['base_sleep'] = 1 

    try:
        c['postgres_config'] = json.loads(env_dist.get('POSTGRES_CONFIG'))
    except:
        print('POSTGRES_CONFIG error')
        sys.exit()

    try:
        c['redis_config'] = json.loads(env_dist.get('REDIS_CONFIG'))
    except:
        print('REDIS_CONFIG error')
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
