#!/usr/bin/python3
#encoding:UTF-8
import json, os, sys, time
import utils.tasks as tasks
import utils.utils as utils
import asyncio, aiomysql
from multiprocessing import Pool
from concurrent.futures import ThreadPoolExecutor,ProcessPoolExecutor
from contextlib import suppress
import diff_match_patch as dmp_module
import traceback

conn = None

# task_type = 2
async def parsePost(val):
    undo_id = val[0]
    block_num = val[1]
    trans_id = val[2]
    op_idx = val[3]
    op = json.loads(val[4])
    task_type = val[5]
    block_time = val[7]
    try:
        op_type = op[0]
        op_detail = op[1]
        print('parse_post:', undo_id)
        if op_type == 'comment' and op_detail['parent_author'] == '':
            print('get_in_post')
            main_tag_id = await getId('tags', op_detail['parent_permlink'])
            if main_tag_id == None:
                print('none_main_tag_id')
                return await updateCount(undo_id)
            author_id = await getId('users', op_detail['author'])
            if author_id == None:
                print('none_main_author_id')
                return await updateCount(undo_id)
            body = op_detail['body']
            dmp = dmp_module.diff_match_patch()
            try:
                # update post
                patches = dmp.patch_fromText(body);
                old_post = await getData('posts', (author_id, op_detail['permlink']))
                if old_post == None:
                    print('post_not_exist', undo_id)
                    return await updateCount(undo_id)
                print('old_post1:', undo_id)
                old_body = old_post[5] # body
                new_body = dmp.patch_apply(patches, old_body);
                return await updateData('posts', old_post[0], undo_id, (
                    main_tag_id,
                    author_id,
                    op_detail['permlink'],
                    op_detail['title'],
                    new_body,
                    json.dumps(op_detail['json_metadata']),
                    old_post[7], # created_at
                    block_time,
                    False))
            except ValueError as e:
                old_post = await getData('posts', (author_id, op_detail['permlink']))
                print('old_post2:', undo_id)
                if old_post == None:
                    # insert new post
                    return await insertData('posts', undo_id, (
                            main_tag_id,
                            author_id,
                            op_detail['permlink'],
                            op_detail['title'],
                            op_detail['body'],
                            json.dumps(op_detail['json_metadata']),
                            block_time,
                            block_time,
                            False))
                else:
                    # update post
                    return await updateData('posts', old_post[0], undo_id, (
                            main_tag_id,
                            author_id,
                            op_detail['permlink'],
                            op_detail['title'],
                            op_detail['body'],
                            json.dumps(op_detail['json_metadata']),
                            old_post[7], # created_at
                            block_time,
                            old_post[9]))
        elif op_type == 'delete_comment':
            author_id = await getId('users', op_detail['author'])
            if author_id == None:
                print('none_main_author_id')
                return await updateCount(undo_id)
            old_post = await getData('posts', (author_id, op_detail['permlink']))
            print('old_post3:', undo_id)
            if old_post == None:
                old_comment = await getData('comments', (author_id, op_detail['permlink']))
                print('old_comment3:', undo_id)
                if old_comment == None:
                    return await updateCount(undo_id)
                else:
                    return await delData('comments', old_comment[0], undo_id)
            else:
                return await delData('posts', old_post[0], undo_id)
    except Exception as e:
        utils.PrintException(undo_id)
        return await updateCount(undo_id)

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
        print('parse_comment:', undo_id)
        if op_type == 'comment' and op_detail['parent_author'] != '':
            print('get_in_comment')
            parent_author_id = await getId('users', op_detail['parent_author'])
            if parent_author_id == None:
                # parent_author has not been inserted into users table.
                return await updateCount(undo_id)
            author_id = await getId('users', op_detail['author'])
            if author_id == None:
                # author has not been inserted into users table.
                return await updateCount(undo_id)

            permlink = op_detail['permlink']
            title = op_detail['title']

            # check if comment edit through body dmp
            body = op_detail['body']
            dmp = dmp_module.diff_match_patch()
            try:
                # if patch_fromText successed, this comment is edited.
                patches = dmp.patch_fromText(body);
                old_comment = await getData('comments', (author_id, op_detail['permlink']))
                if old_comment == None:
                    print('comment_not_exist', undo_id)
                    return await updateCount(undo_id)
                print('old_comment1:', undo_id)
                old_body = old_comment[4] # body
                new_body = dmp.patch_apply(patches, old_body);
                parent_comment = await getData('comments', (parent_author_id, op_detail['parent_permlink']))
                if parent_comment == None:
                    parent_comment_id = None
                else:
                    parent_comment_id = parent_comment[0]
                print('dmp_edit_comment', block_num, trans_id, op_idx, old_comment[0])
                return await updateData('comments', old_comment[0], undo_id, (
                    parent_comment_id,
                    op_detail['permlink'],
                    op_detail['title'],
                    new_body,
                    json.dumps(op_detail['json_metadata']),
                    old_comment[6], # post_id
                    old_comment[7], # parent_author_id
                    old_comment[8], # author_id
                    old_comment[9], # parent_permlink
                    old_comment[10], # created_at
                    block_time,
                    False))
            except ValueError as e:
                parent_permlink = op_detail['parent_permlink']
                old_comment = await getData('comments', (author_id, op_detail['permlink']))
                if old_comment == None:
                    print('comment_not_exist2', undo_id)
                    # this comment is a new comment.
                    parent_comment = await getData('comments', (parent_author_id, parent_permlink))
                    if parent_comment == None:
                        post_id = await getId('posts', (parent_author_id, parent_permlink))
                        if post_id == None:
                            # data not prepared
                            print('data_not_prepared1', block_num, trans_id, op_idx)
                            return await updateCount(undo_id)
                        else:
                            # This is a new parent comment
                            print('new_parent_comment', block_num, trans_id, op_idx)
                            return await insertData('comments', undo_id, (
                                None,
                                op_detail['permlink'],
                                op_detail['title'],
                                op_detail['body'],
                                json.dumps(op_detail['json_metadata']),
                                post_id, # post_id
                                parent_author_id, # parent_author_id
                                author_id, # author_id
                                parent_permlink, # parent_permlink
                                block_time, # created_at
                                block_time, # updated_at
                                False))
                    else:
                        # This is a new child comment
                        print('new_child_comment', block_num, trans_id, op_idx)
                        return await insertData('comments', undo_id, (
                            parent_comment[0],
                            op_detail['permlink'],
                            op_detail['title'],
                            op_detail['body'],
                            json.dumps(op_detail['json_metadata']),
                            parent_comment[6], # post_id
                            parent_author_id, # parent_author_id
                            author_id, # author_id
                            parent_permlink, # parent_permlink
                            block_time, # created_at
                            block_time, # updated_at
                            False))
                else:
                    # this comment is edited and does not use diff_match_patch
                    print('without_dmp_edit_comment', block_num, trans_id, op_idx, old_comment[0])
                    return await updateData('comments', old_comment[0], undo_id, (
                        old_comment[0],
                        op_detail['permlink'],
                        op_detail['title'],
                        op_detail['body'],
                        json.dumps(op_detail['json_metadata']),
                        old_comment[6], # post_id
                        old_comment[7], # parent_author_id
                        old_comment[8], # author_id
                        old_comment[9], # parent_permlink
                        old_comment[10], # created_at
                        block_time,
                        False))
    except Exception as e:
        utils.PrintException(undo_id)
        return await updateCount(undo_id)

# task_type = 5
async def parsePostTag(val):
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
        print('parse_post_tag:', undo_id)
        if op_type == 'comment' and op_detail['parent_author'] == '':
            print('get_in_post_tag')
            try:
                json_metadata = json.loads(op_detail['json_metadata'])
            except Exception as e:
                print('parse json failed:', op_detail['json_metadata'])
                return await delData('undo_op', None, undo_id)

            author_id = await getId('users', op_detail['author'])
            if author_id == None:
                print('none_main_author_id_in_post_tag')
                return await updateCount(undo_id)
            post = await getData('posts', (author_id, op_detail['permlink']))
            if post == None:
                print('not_found_post_in_post_tag')
                return await updateCount(undo_id)

            if 'tags' in json_metadata:
                if isinstance(json_metadata['tags'], list):
                    for tag in json_metadata['tags']:
                        tag_id = await getId('tags', tag)
                        if tag_id != None:
                            return await insertData('posts_tags', undo_id, (post[0], tag_id))
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
        print('parse_post_tag:', undo_id)
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
            author_id = await getId('users', op_detail['author'])
            if author_id == None:
                return await updateCount(undo_id)
            post = await getData('posts', (author_id, op_detail['permlink']))
            if post == None:
                comment = await getData('comments', (author_id, op_detail['permlink']))
                if comment == None:
                    print('not_found_post_or_comment', block_num, trans_id, op_idx)
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
            else:
                vote = await getData('posts_votes', (voter_id, post[0]))
                if vote != None:
                    # edit vote
                    return await updateData('posts_votes', vote[0], undo_id, (
                        post[0],
                        voter_id,
                        weight,
                        updown,
                        vote[5],
                        block_time))
                else:
                    # insert post vote
                    return await insertData('posts_votes', undo_id, (
                        post[0],
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
    if table == 'posts':
        sql = '''select * from posts
            where author_id = %s and permlink = %s limit 1'''
    elif table == 'comments':
        sql = '''select * from comments
            where author_id = %s and permlink = %s limit 1'''
    elif table == 'comments_votes':
        sql = '''select * from comments_votes
            where user_id = %s and comment_id = %s limit 1'''
    elif table == 'posts_votes':
        sql = '''select * from posts_votes
            where user_id = %s and post_id = %s limit 1'''
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
    if table == 'posts':
        sql = '''insert into posts
            (
                main_tag_id,
                author_id,
                permlink,
                title,
                body,
                json_metadata,
                created_at,
                updated_at,
                is_del
            )
            values
            (%s, %s, %s, %s, %s, %s, %s, %s, %s)'''
    elif table == 'comments':
        sql = '''insert into comments 
            (
                parent_id,
                permlink,
                title,
                body,
                json_metadata,
                post_id,
                parent_author_id,
                author_id,
                parent_permlink,
                created_at,
                updated_at,
                is_del
            )
            values
            (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)'''
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
    elif table == 'posts_votes':
        sql = '''insert into posts_votes
            (
                post_id,
                user_id,
                weight,
                updown,
                created_at,
                updated_at
            )
            values
            (%s, %s, %s, %s, %s, %s)'''
    elif table == 'posts_tags':
        sql = '''insert into posts_tags
            (posts_id, tags_id)
            values
            (%s, %s)'''
    elif table == 'user_relations':
        sql = '''insert into user_relations
            (follower_id, following_id, what, created_at)
            values
            (%s, %s, %s, %s)'''
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

async def updateData(table, old_id, undo_id, val):
    global conn
    if table == 'posts':
        sql = '''update posts
            set main_tag_id = %s,
                author_id = %s,
                permlink = %s,
                title = %s,
                body = %s,
                json_metadata = %s,
                created_at = %s,
                updated_at = %s,
                is_del = %s
            where id = {}'''.format(old_id)
    elif table == 'comments':
        sql = '''update comments
            set parent_id = %s,
                permlink = %s,
                title = %s,
                body = %s,
                json_metadata = %s,
                post_id = %s,
                parent_author_id = %s,
                author_id = %s,
                parent_permlink = %s,
                created_at = %s,
                updated_at = %s,
                is_del = %s
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
    elif table == 'posts_votes':
        sql = '''update posts_votes
            set post_id = %s,
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
                    # post
                    await parsePost(val)
                elif task_type == 3:
                    # comment
                    await parseComment(val)
                elif task_type == 4:
                    # tag
                    print('task_type:', val)
                elif task_type == 5:
                    # post_tag
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
        time.sleep(3)

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
