#!/usr/bin/python3
#encoding:UTF-8
import json, os, sys, time
import utils.TransferTasks as tasks
import utils.utils as utils
import asyncio, aiomysql
from multiprocessing import Pool
from concurrent.futures import ThreadPoolExecutor,ProcessPoolExecutor
from contextlib import suppress
import diff_match_patch as dmp_module
import traceback

conn = None

# task_type = 3
async def parseComment(val):
    undo_id = val[0]
    block_num = val[1]
    trans_id = val[2]
    op_idx = val[3]
    op = json.loads(val[4])
    task_type = val[5]
    block_time = val[6]
    try:
        op_type = op[0]
        op_detail = op[1]
        print('parse_comment:', op_detail, undo_id)
        if op_type == 'comment':
            print('get_in_comment')
            parent_author_text = op_detail['parent_author']
            author_text = op_detail['author']

            # check if comment edit through body dmp
            dmp = dmp_module.diff_match_patch()
            try:
                # if patch_fromText successed, this comment is edited.
                patches = dmp.patch_fromText(op_detail['body']);
                old_comment = await getData('comments', (author_text, op_detail['permlink']))
                if old_comment == None:
                    print('comment_not_exist_dmp', undo_id)
                    return await updateCount(undo_id)
                print('old_comment_dmp:', undo_id)
                new_body = dmp.patch_apply(patches, old_comment[4]);
                print('dmp_edit_comment', block_num, trans_id, op_idx, old_comment[0])
                return await updateData('comments', old_comment[0], undo_id, (
                    old_comment[1],
                    op_detail['permlink'],
                    op_detail['title'],
                    new_body,
                    json.dumps(op_detail['json_metadata']),
                    old_comment[9], # parent_permlink
                    old_comment[10], # created_at
                    block_time,
                    False,
                    parent_author_text,
                    author_text))
            except ValueError as e:
                old_comment = await getData('comments', (author_text, op_detail['permlink']))
                if old_comment == None:
                    print('comment_not_exist2', undo_id)
                    return await insertData('comments', undo_id, (
                        op_detail['permlink'],
                        op_detail['title'],
                        op_detail['body'],
                        json.dumps(op_detail['json_metadata']),
                        op_detail['parent_permlink'], # parent_permlink
                        block_time, # created_at
                        block_time, # updated_at
                        False,
                        parent_author_text,
                        author_text))
                else:
                    print('without_dmp_edit_comment', block_num, trans_id, op_idx, old_comment[0])
                    return await updateData('comments', old_comment[0], undo_id, (
                        op_detail['permlink'],
                        op_detail['title'],
                        op_detail['body'],
                        json.dumps(op_detail['json_metadata']),
                        old_comment[9], # parent_permlink
                        old_comment[10], # created_at
                        block_time,
                        False,
                        old_comment[12], # parent_author_text
                        old_comment[13])) # author_text
    except Exception as e:
        utils.PrintException(undo_id)
        return await updateCount(undo_id)

# task_type = 5
async def parseCommentTag(val):
    undo_id = val[0]
    block_num = val[1]
    trans_id = val[2]
    op_idx = val[3]
    op = json.loads(val[4])
    task_type = val[5]
    block_time = val[6]
    try:
        op_type = op[0]
        op_detail = op[1]
        print('parse_comment_tag:', undo_id)
        if op_type == 'comment':
            print('get_in_comment_tag')
            try:
                json_metadata = json.loads(op_detail['json_metadata'])
            except Exception as e:
                print('parse json failed:', op_detail['json_metadata'])
                return await delData('undo_op', None, undo_id)

            comment = await getData('comments', (op_detail['author'], op_detail['permlink']))
            if comment == None:
                print('not_found_comment_in_comment_tag')
                return await updateCount(undo_id)

            if 'tags' in json_metadata:
                if isinstance(json_metadata['tags'], list):
                    for tag in json_metadata['tags']:
                        tag_id = await getId('tags', tag)
                        if tag_id != None:
                            return await insertData('comments_tags', undo_id, (comment[0], tag_id))
                        else:
                            return await updateCount(undo_id)
                else:
                    return await delData('undo_op', None, undo_id)
            else:
                return await delData('undo_op', None, undo_id)
    except Exception as e:
        utils.PrintException(undo_id)
        return await updateCount(undo_id)

# task_type = 6
async def parseVote(val):
    undo_id = val[0]
    block_num = val[1]
    trans_id = val[2]
    op_idx = val[3]
    op = json.loads(val[4])
    task_type = val[5]
    block_time = val[6]
    try:
        op_type = op[0]
        op_detail = op[1]
        print('parse_vote:', undo_id)
        if op_type == 'vote':
            print('get_in_vote')
            weight = op_detail['weight']
            if weight >= 0:
                updown = True
            else:
                weight = (-1) * weight
                updown = False 
            voter_id = await getId('users', op_detail['voter'])
            if voter_id == None:
                return await updateCount(undo_id)
            comment = await getData('comments', (op_detail['author'], op_detail['permlink']))
            if comment == None:
                print('not_found_comment', block_num, trans_id, op_idx)
                return await updateCount(undo_id)

            else:
                # vote to comment
                vote = await getData('comments_votes', (voter_id, comment[0]))
                if vote != None:
                    # edit vote
                    return await updateData('comments_votes', vote[0], undo_id, (
                        comment[0],
                        voter_id,
                        weight,
                        updown,
                        vote[5],
                        block_time))
                else:
                    # insert comment vote
                    return await insertData('comments_votes', undo_id, (
                        comment[0],
                        voter_id,
                        weight,
                        updown,
                        block_time,
                        block_time))

    except Exception as e:
        utils.PrintException(undo_id)
        return await updateCount(undo_id)

# task_type = 7
async def parseUserRelation(val):
    undo_id = val[0]
    block_num = val[1]
    trans_id = val[2]
    op_idx = val[3]
    op = json.loads(val[4])
    task_type = val[5]
    block_time = val[6]
    try:
        op_type = op[0]
        op_detail = op[1]
        print('parse_user_relation:', undo_id)
        if op_type == 'custom_json' and 'id' in op_detail and op_detail['id'] == 'follow':
            try:
                json_data = json.loads(op_detail['json'])
            except Exception as e:
                print('parse json failed:', op_detail['json'])
                return await delData('undo_op', None, undo_id)

            try:
                follower = None
                following = None
                what = None
                if isinstance(json_data, dict):
                    if 'follower' in json_data:
                        follower = json_data['follower']
                    else:
                        return await delData('undo_op', None, undo_id)
                    if 'following' in json_data:
                        following = json_data['following']
                    else:
                        return await delData('undo_op', None, undo_id)
                    if 'what' in json_data and isinstance(json_data['what'], list):
                        if len(json_data['what']) == 0:
                            what = ''
                        else:
                            what = json_data['what'][0]
                    else:
                        return await delData('undo_op', None, undo_id)
                else:
                    return await delData('undo_op', None, undo_id)
                if follower == None and following == None and what == None:
                    return await delData('undo_op', None, undo_id)
                follower_id = await getId('users', follower)
                if follower_id == None:
                    return await updateCount(undo_id)
                following_id = await getId('users', following)
                if following_id == None:
                    return await updateCount(undo_id)
                return await insertData('user_relations', undo_id, (follower_id, following_id, what, block_time))
            except Exception as e:
                utils.PrintException([block_num, trans_id, op_idx])
                return await updateCount(undo_id)
    except Exception as e:
        utils.PrintException(undo_id)
        return await updateCount(undo_id)
# --------------------------------------------------

async def updateCount(undo_id):
    global conn
    print('get_in_update_count: ', undo_id)
    sql = 'update undo_op set count = count + 1 where id = %s'
    try:
        cur = await conn.cursor()
        await cur.execute(sql, undo_id)
        await conn.commit()
        await cur.close()
        print('update_count_success: ', undo_id)
    except Exception as e:
        print('update_count_failed: ', undo_id)
        await cur.close()
        await conn.rollback()
        utils.PrintException(undo_id)

async def getId(table, val):
    global conn
    if table == 'users':
        sql = '''select id from users
            where username = %s'''
    elif table == 'tags':
        sql = '''select id from tags
            where tag_name = %s'''
    else:
        return None
    
    try:
        cur = await conn.cursor()
        await cur.execute(sql, val)
        data = await cur.fetchone()
        await cur.close()
        if data != None:
            return data[0]
        else:
            return None
    except:
        utils.PrintException()
        return None

async def getData(table, val):
    global conn
    if table == 'comments':
        sql = '''select * from comments
            where author_text = %s and permlink = %s limit 1'''
    elif table == 'comments_votes':
        sql = '''select * from comments_votes
            where user_id = %s and comment_id = %s limit 1'''
    else:
        return None
    
    try:
        cur = await conn.cursor()
        await cur.execute(sql, val)
        data = await cur.fetchone()
        await cur.close()
        return data
    except:
        utils.PrintException()
        return None

async def insertData(table, undo_id, val):
    global conn
    if table == 'comments':
        sql = '''insert into comments 
            (
                permlink,
                title,
                body,
                json_metadata,
                parent_permlink,
                created_at,
                updated_at,
                is_del,
                parent_author_text,
                author_text
            )
            values
            (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)'''
    elif table == 'comments_votes':
        sql = '''insert into comments_votes
            (
                comment_id,
                user_id,
                weight,
                updown,
                created_at,
                updated_at
            )
            values
            (%s, %s, %s, %s, %s, %s)'''
    elif table == 'comments_tags':
        sql = '''insert into comments_tags
            (comments_id, tags_id)
            values
            (%s, %s)'''
        comment_ids = []
        for v in val:
            comment_ids.append(v[0])
        tuple_comment_ids = tuple(comment_ids)
        format_strings = ','.join(['%s'] * len(comment_ids))
        sql2 = '''delete from comments_tags
            where id in (%s)''' % format_strings
    elif table == 'user_relations':
        sql = '''insert into user_relations
            (follower_id, following_id, what, created_at)
            values
            (%s, %s, %s, %s)'''
    elif table == 'tags':
        sql = '''insert into tags
            (tag_name)
            values
            (%s)'''
    else:
        return None

    remove_undo_op_sql = '''delete from undo_op
        where id = %s'''
    
    try:
        cur = await conn.cursor()
        if table == 'comments_tags':
            # remove previous comment_tag records
            await cur.execute(sql2, tuple_comment_ids)
        #update data
        await cur.execute(sql, val)
        if undo_id != None:
            #remove undo_op
            await cur.execute(remove_undo_op_sql, undo_id)
        await conn.commit()
        await cur.close()
        return True
    except:
        await cur.close()
        await conn.rollback()
        utils.PrintException(undo_id)
        return False

async def updateData(table, old_id, undo_id, val):
    global conn
    if table == 'comments':
        sql = '''update comments
            set permlink = %s,
                title = %s,
                body = %s,
                json_metadata = %s,
                parent_permlink = %s,
                created_at = %s,
                updated_at = %s,
                is_del = %s,
                parent_author_text = %s,
                author_text = %s
            where id = {}'''.format(old_id)
    elif table == 'comments_votes':
        sql = '''update comments_votes
            set comment_id = %s,
                user_id = %s,
                weight = %s,
                updown = %s,
                created_at = %s,
                updated_at = %s
            where id = {}'''.format(old_id)
    else:
        return None

    remove_undo_op_sql = '''delete from undo_op
        where id = %s'''
    
    try:
        cur = await conn.cursor()
        #update data
        await cur.execute(sql, val)
        #remove undo_op
        await cur.execute(remove_undo_op_sql, undo_id)
        await conn.commit()
        await cur.close()
        return True
    except:
        await cur.close()
        await conn.rollback()
        utils.PrintException(undo_id)
        return False

async def delData(table, old_id, undo_id):
    global conn
    if table == 'posts':
        sql = '''update posts
            set is_del = 1
            where id = %s'''
    elif table == 'comments':
        sql = '''update comments 
            set is_del = 1
            where id = %s'''
    elif table == 'undo_op':
        sql = None
    else:
        return None

    remove_undo_op_sql = '''delete from undo_op
        where id = %s'''
    
    try:
        cur = await conn.cursor()
        #remove data
        if sql != None:
            await cur.execute(sql, old_id)
        #remove undo_op
        await cur.execute(remove_undo_op_sql, undo_id)
        await conn.commit()
        await cur.close()
        return True
    except:
        await cur.close()
        await conn.rollback()
        utils.PrintException(undo_id)
# --------------------------------------------------

async def processor(loop, config):
    global conn
    db_c = config['steem_config']
    while True:
        conn = await aiomysql.connect(
            host=db_c['host'],
            port=db_c['port'],
            user=db_c['user'],
            password=db_c['pass'],
            charset='utf8mb4',
            db=db_c['db'],
            autocommit=False,
            loop=loop)

        undo_count = config['undo_count']
        undo_limit = config['undo_limit']
        undo_sleep = config['undo_sleep']
        #get undo op
        sql = '''select * from undo_op
            where count <= %s
            order by id asc
            limit %s'''

        cur = await conn.cursor()
        await cur.execute(sql, (undo_count, undo_limit))
        data = await cur.fetchall()
        await cur.close()

        if data != ():
            for val in data:
                undo_id = val[0]
                block_num = val[1]
                trans_id = val[2]
                op_idx = val[3]
                op = val[4]
                task_type = val[5]
                if task_type == 1:
                    # user
                    print('task_type:', val)
                elif task_type == 2:
                    # has been removed
                    print('task_type:', val)
                elif task_type == 3:
                    # comment
                    await parseComment(val)
                elif task_type == 4:
                    # tag
                    print('task_type:', val)
                elif task_type == 5:
                    # comment_tag
                    await parsePostTag(val)
                elif task_type == 6:
                    # vote
                    await parseVote(val)
                elif task_type == 7:
                    # user_relation
                    await parseUserRelation(val)
                else:
                    print('unknown_task_type', val)

        conn.close()
        conn = None
        time.sleep(undo_sleep)

def mainMultiProcess():
    config = utils.get_config()
    loop = asyncio.get_event_loop()
    try:
        loop.run_until_complete(processor(loop, config))
    except KeyboardInterrupt as e:
        for task in asyncio.Task.all_tasks():
            task.cancel()
        loop.stop()
    finally:
        loop.close()

if __name__ == '__main__':
    with suppress(KeyboardInterrupt):
        mainMultiProcess()
