#!/usr/bin/python3
#encoding:UTF-8

import pymysql
import json, os, sys, time
import utils.utils as utils

task_type = {
    'user': 1,
    #'post': 2,
    'comment': 3,
    'tag': 4,
    'comment_tag': 5,
    'vote': 6,
    'user_relation': 7}

def get(t):
    global task_type
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

    # get unfinished tasks
    sql = '''select * from multi_tasks
        where task_type = %s
        and is_finished = 0'''
    with db.cursor() as cur:
        cur.execute(sql, (task_type[t],))
        res = cur.fetchall()

    if res == ():
        # get check_point
        sql = '''select val from config
            where param = "check_point" limit 1'''
        with db.cursor() as cur:
            cur.execute(sql)
            res = cur.fetchone()
        if res == None:
            print('cannot_get_check_point')
            return []
        else:
            check_point = res['val']
            print('get_check_point_in_transfer_tasks', check_point)
        # get last task block num
        sql = '''select * from multi_tasks
            where task_type = %s
            and is_finished = 1
            order by block_num_to desc
            limit 1'''
        with db.cursor() as cur:
            cur.execute(sql, (task_type[t], ))
            res = cur.fetchone()
        if res == None:
            # generate new tasks
            block_from = 1
            generate_tasks(1, check_point, db, task_type[t])
        else:
            last_task_block_to = res['block_num_to']
            block_from = last_task_block_to + 1
        if block_from > check_point:
            print('block_from > check_point in transfertasks')
            return []
        generate_tasks(block_from, check_point, db, task_type[t])
        data = get(t)
        db.close()
        return data
    else:
        db.close()
        return res

def generate_tasks(block_from, block_to, conn, user_t):
    config = utils.get_config()
    i = block_from
    data = []
    while i <= block_to:
        tmp_to = i + config['block_step']
        if tmp_to > block_to:
            tmp_to = block_to
        data.append((user_t, i, tmp_to, 0))
        i = tmp_to + 1
    sql = '''
        insert into multi_tasks
        (task_type, block_num_from, block_num_to, is_finished)
        values
        (%s, %s, %s, %s)'''
    with conn.cursor() as cur:
        cur.executemany(sql, data)
        conn.commit()
    return data

def splitTasks(tasks, step=10):
    length = len(tasks)
    if length == 0:
        return []
    i = 0
    result = []
    while i <= length:
        if (i+step) >= length:
            result.append(tasks[i:(length+1)])
        else:
            result.append(tasks[i:(i+step)])
        i = i + step
    return result

def getTypeId(t):
    global task_type
    if t in task_type:
        return task_type[t]
    else:
        return 0

if __name__ == '__main__':
    data = get('user')
    print(data)
