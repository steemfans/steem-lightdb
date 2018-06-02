FROM alpine:3.7
COPY . /app
WORKDIR /app
ENV WORKER=5
ENV BLOCK_STEP=1000
ENV SLICE_STEP=50
ENV STEEMDB_CONFIG='{"host": "steem-lightdb.com", "port": 3306, "user": "steem", "pass": "steem", "db": "steemdb"}'
ENV STEEM_CONFIG='{"host": "172.20.0.4", "port": 3306, "user": "root", "pass": "123456", "db": "steem"}'
RUN apk --no-cache add python3 && pip3 install pymysql && pip3 install aiomysql
