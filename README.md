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

### Import DB structure

```
# git clone https://github.com/ety001/steem-lightdb.git
# cd steem-lightdb
# docker cp dbMerge/201805020838.sql mysql:/tmp/data.sql
# docker exec -it mysql mysql -uroot -p
Enter password:
Welcome to the MariaDB monitor.  Commands end with ; or \g.
Your MariaDB connection id is 9
Server version: 10.2.14-MariaDB-10.2.14+maria~jessie mariadb.org binary distribution

Copyright (c) 2000, 2018, Oracle, MariaDB Corporation Ab and others.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

MariaDB [(none)]> source /tmp/data.sql

MariaDB [(none)]> exit
```

### Run the Base Data container

```
# docker run -it -d --name steem-lightdb-base -e DB_HOST=172.19.0.2 -e DB_NAME=steemdb -e DB_USER=root -e DB_PASS=123456 --restart always --network lightdb --ip "172.19.0.3" ety001/steem-lightdb-base:latest
```

If you want to get notifications by Discord, add `-e DISCORD=YOUR_DISCORD_WEBHOOK` in the command.

### Run the Base Data Filled container

Sometimes the base script will not get data from Blockchain
because of the unstable network. So we need to run another script
to keep the data same as the Blockchain.

```
# docker run -it -d --name steem-lightdb-base-fill-db -e DB_HOST=172.19.0.2 -e DB_NAME=steemdb -e DB_USER=root -e DB_PASS=123456 --restart always --network lightdb --ip "172.19.0.4" ety001/steem-lightdb-base:latest /app/fill.py
```

Add a task into crond

```
# crontab –e
0 0 * * * /usr/bin/docker restart steem-lightdb-base-fill-db
```

### Import Steem Database

```
# docker pull ety001/lightdb-transfer

# mkdir -p /data/transfer/var

# docker run -it --rm --network lightdb -e CHAIN_DB="mysql://root:123456@172.19.0.2/steemdb" -e DATABASE_URL="mysql://root:123456@172.19.0.2/steem" -e APP_ENV=prod -v /data/transfer/var:/app/var ety001/lightdb-transfer php bin/console doctrine:database:create

# docker run -it --rm --network lightdb -e CHAIN_DB="mysql://root:123456@172.19.0.2/steemdb" -e DATABASE_URL="mysql://root:123456@172.19.0.2/steem" -e APP_ENV=prod -v /data/transfer/var:/app/var ety001/lightdb-transfer php bin/console doctrine:migrations:migrate
```

### Run transfer

```
docker run -itd --name lightdb-transfer --restart always --network main --ip "172.20.0.202" -e CHAIN_DB="mysql://steem:steem@172.20.0.2:3306/steemdb" -e DATABASE_URL="mysql://steemwrite:8R4nQKrytP27@172.20.0.2:3306/steem" -e APP_ENV=prod -v /data/transfer/var:/app/var ety001/lightdb-transfer php bin/console transfer:run --step=500
```

### Recycle Tasks

Undo

### Data API Layer

Undo

# Official Server

* Server: steem-lightdb.com
* User: steem
* Pass: steem
* DBName: steem

You can get current block num by this SQL:

```
select * from config where param = "current_head";
```

# Contact Me

Email: work#domyself.me (replace # by @)

# Vote Me

My Steem Witness Vote URL: [https://steemconnect.com/sign/account_witness_vote?approve=1&witness=ety001](https://steemconnect.com/sign/account_witness_vote?approve=1&witness=ety001)

# Contribution

Currently code submission will not be accepted.

# License

MIT
