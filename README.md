# Steem LightDB

Steem LightDB is another database service and solution for Steem and is based on Mysql.
Steem LightDB will focus on the social data, not all data of Steem, such as comments
and votes. That's why I call it light. Steem LightDB will have three parts: Base Block
Data as cache, Final Social Data from cache, Apis for Final Social Data.

All components will be deployed with Docker. This will make people who want to customize
LightDB easy to run it on their servers.

# DB Structure

![](https://steemitimages.com/DQmXFfTDbr4s5wSu5AHuMd6zdHz2gGcMJp2XaTQ1t6GNMfy/image.png)

# How to deploy

### Create a custom network in Docker

```
# docker network create -d bridge --gateway "172.19.0.1" --subnet "172.19.0.0/24" lightdb
```

This command will create a network named `lightdb` with ip range `172.19.0.0/24`.

### Deploy a Mysql container

```
# docker run -d --name mysql -v /your/db/file/path:/var/lib/mysql -p 3306:3306 --network lightdb --ip "172.19.0.2" --restart always -e MYSQL_ROOT_PASSWORD=123456 mariadb
```

`123456` is your Mysql server root's password

### Creating the Database Tables/Schema

If you first run LightDB, please create database first.

```
# docker run -it --rm --network lightdb -e DATABASE_URL="mysql://root:123456@172.19.0.2:3306/steem" -e APP_ENV=prod ety001/lightdb-api php bin/console doctrine:database:create
```

Creating the Database Tables/Schema.

```
# docker run -it --rm --network lightdb -e DATABASE_URL="mysql://root:123456@172.19.0.2:3306/steem" -e APP_ENV=prod ety001/lightdb-api php bin/console doctrine:migrations:migrate
                                                              
                    Application Migrations                    
                                                              

WARNING! You are about to execute a database migration that could result in schema changes and data loss. Are you sure you wish to continue? (y/n)y
```

### Run transfer

Please run the transfer containers in order.

```
# docker run -itd -e STEEM_CONFIG='{"host": "172.19.0.2", "port": 3306, "user": "root", "pass": "123456", "db": "steem"}' --network lightdb --restart always --name lightdb-base ety001/lightdb-transfer:latest /app/base.py

# docker run -itd -e STEEM_CONFIG='{"host": "172.19.0.2", "port": 3306, "user": "root", "pass": "123456", "db": "steem"}' --network lightdb --restart always --name lightdb-users ety001/lightdb-transfer:latest /app/users.py

# docker run -itd -e STEEM_CONFIG='{"host": "172.19.0.2", "port": 3306, "user": "root", "pass": "123456", "db": "steem"}' --network lightdb --restart always --name lightdb-user-relation ety001/lightdb-transfer:latest /app/user_relation.py

# docker run -itd -e STEEM_CONFIG='{"host": "172.19.0.2", "port": 3306, "user": "root", "pass": "123456", "db": "steem"}' --network lightdb --restart always --name lightdb-tags ety001/lightdb-transfer:latest /app/tags.py

# docker run -itd -e STEEM_CONFIG='{"host": "172.19.0.2", "port": 3306, "user": "root", "pass": "123456", "db": "steem"}' --network lightdb --restart always --name lightdb-comments ety001/lightdb-transfer:latest /app/comments.py

# docker run -itd -e STEEM_CONFIG='{"host": "172.19.0.2", "port": 3306, "user": "root", "pass": "123456", "db": "steem"}' --network lightdb --restart always --name lightdb-comments-tags ety001/lightdb-transfer:latest /app/comments_tags.py

# docker run -itd -e STEEM_CONFIG='{"host": "172.19.0.2", "port": 3306, "user": "root", "pass": "123456", "db": "steem"}' --network lightdb --restart always --name lightdb-votes ety001/lightdb-transfer:latest /app/votes.py

# docker run -itd -e STEEM_CONFIG='{"host": "172.19.0.2", "port": 3306, "user": "root", "pass": "123456", "db": "steem"}' --network lightdb --restart always --name lightdb-undo ety001/lightdb-transfer:latest /app/undo.py

# docker run -itd -e STEEM_CONFIG='{"host": "172.19.0.2", "port": 3306, "user": "root", "pass": "123456", "db": "steem"}' --network lightdb --restart always --name lightdb-collect ety001/lightdb-transfer:latest /app/collect.py
```

Check if all containers are running.

```
# docker ps
CONTAINER ID        IMAGE                            COMMAND                   CREATED             STATUS              PORTS                    NAMES
bd4f44ebd922        ety001/lightdb-transfer:latest   "/app/collect.py"         5 seconds ago       Up 3 seconds                                 lightdb-collect
dd70d76f31d2        ety001/lightdb-transfer:latest   "/app/undo.py"            11 seconds ago      Up 9 seconds                                 lightdb-undo
4e8f9228e70c        ety001/lightdb-transfer:latest   "/app/votes.py"           17 seconds ago      Up 15 seconds                                lightdb-votes
5aab28feffa8        ety001/lightdb-transfer:latest   "/app/comments_tags.…"    26 seconds ago      Up 24 seconds                                lightdb-comments-tags
806485070c45        ety001/lightdb-transfer:latest   "/app/comments.py"        32 seconds ago      Up 31 seconds                                lightdb-comments
124f84348d50        ety001/lightdb-transfer:latest   "/app/tags.py"            39 seconds ago      Up 37 seconds                                lightdb-tags
628caeb2ae43        ety001/lightdb-transfer:latest   "/app/user_relation.…"    46 seconds ago      Up 44 seconds                                lightdb-user-relation
534348f38d17        ety001/lightdb-transfer:latest   "/app/users.py"           2 minutes ago       Up 2 minutes                                 lightdb-users
3e8f1ef3567b        ety001/lightdb-transfer:latest   "/app/base.py"            2 minutes ago       Up 2 minutes                                 lightdb-base
bfcaf0c230e0        mariadb                          "docker-entrypoint.s…"    39 minutes ago      Up 39 minutes       0.0.0.0:3306->3306/tcp   mysql
```

> Recommand to install [Portainer](https://portainer.io/install.html) to manage all your docker containers.
> Portainer will give you an UI to get more info of your containers.

### Data API Layer

Undo

# Official Server

* Server: steem-lightdb.com
* User: steem
* Pass: steem
* DBName: steem

# Contact Me

Email: work#domyself.me (replace # by @)

# Vote Me

My Steem Witness Vote URL: [https://steemconnect.com/sign/account_witness_vote?approve=1&witness=ety001](https://steemconnect.com/sign/account_witness_vote?approve=1&witness=ety001)

# Contribution

Currently code submission will not be accepted.

# License

MIT
