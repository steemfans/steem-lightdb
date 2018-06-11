FROM alpine:3.7
WORKDIR /app
RUN apk --no-cache add python3 \
        && pip3 install pymysql \
        && pip3 install aiomysql \
        && pip3 install diff_match_patch
ENV WORKER=5 \
           BLOCK_STEP=1000 \
           SLICE_STEP=50 \
           UNDO_LIMIT=50 \
           UNDO_COUNT=50 \
           STEEMDB_CONFIG='{"host": "steem-lightdb.com", "port": 3306, "user": "steem", "pass": "steem", "db": "steemdb"}' \
           STEEM_CONFIG='{"host": "172.20.0.4", "port": 3306, "user": "root", "pass": "123456", "db": "steem"}'
COPY . /app
CMD ["/bin/ash"]
