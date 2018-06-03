#!/usr/bin/python3
#encoding:UTF-8

import pymysql
import json, os, sys, time
import utils.utils as utils

task_type = {
    'user': 1,
    'post': 2,
    'comment': 3,
    'tag': 4,
    'post_tag': 5,
    'post_vote': 6,
    'comment_vote': 7,
    'user_relation': 8}

def get(t):
    global task_type
    # get db config
    config = utils.get_config()
    c1 = config['steemdb_config']
    c2 = config['steem_config']
    # connect db
    steemdb = pymysql.connect(
        host = c1['host'],
        user = c1['user'],
        password = c1['pass'],
        database = c1['db'],
        charset = 'utf8',
        cursorclass = pymysql.cursors.DictCursor,
        autocommit = True)
    steem = pymysql.connect(
        host = c2['host'],
        user = c2['user'],
        password = c2['pass'],
        database = c2['db'],
        charset = 'utf8',
        cursorclass = pymysql.cursors.DictCursor,
        autocommit = True)

    # get data
    sql = 'select block_num from blocks order by block_num desc limit 1'
    with steemdb.cursor() as cur1:
        cur1.execute(sql)
        res = cur1.fetchone()
        if res == ():
            print('original data error')
            sys.exit()
        curr_head = res['block_num']
    print('curr_head', curr_head)

    sql = 'select count(*) as total from multi_tasks where task_type = %s'
    with steem.cursor() as cur2:
        cur2.execute(sql, (task_type[t]))
        res = cur2.fetchone()
        task_total = res['total']
    if task_total == 0:
        # compatible with PHP version
        sql = 'select val from config where param = "current_head"'
        with steem.cursor() as cur2:
            cur2.execute(sql)
            res = cur2.fetchone()
        if res == None:
            block_from = 1
        else:
            block_from = int(res['val']) + 1
        if block_from > curr_head:
            return []
        generate_tasks(block_from, curr_head, steem, task_type[t])
        data = get(t)
        steemdb.close()
        steem.close()
        return data
    else:
        sql = '''
            select * from multi_tasks
            where
                task_type = %s
                and is_finished = 0 
            order by block_num_to asc'''
        with steem.cursor() as cur2:
            cur2.execute(sql, (task_type[t]))
            res = cur2.fetchall()
        if res == ():
            sql = '''
                select * from multi_tasks
                where
                    task_type = %s
                    and is_finished = 1
                order by block_num_to desc
                limit 1'''
            with steem.cursor() as cur2:
                cur2.execute(sql, (task_type[t]))
                res = cur2.fetchone()
            if res == ():
                block_from = 1
            else:
                block_from = res['block_num_to'] + 1
            if block_from > curr_head:
                return []
            generate_tasks(block_from, curr_head, steem, task_type[t])
            data = get(t)
            steemdb.close()
            steem.close()
            return data
        else:
            steemdb.close()
            steem.close()
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

if __name__ == '__main__':
    data = get('user')
    print(data)
