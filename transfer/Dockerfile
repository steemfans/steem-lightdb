FROM alpine:3.7
WORKDIR /app
RUN apk --no-cache add python3 python3-dev gcc git musl-dev libffi-dev openssl-dev \
        && pip3 install steem \
        && pip3 install pymysql \
        && pip3 install aiomysql \
        && pip3 install diff_match_patch
ENV WORKER=5 \
           BLOCK_STEP=1000 \
           SLICE_STEP=50 \
           UNDO_LIMIT=1000 \
           UNDO_COUNT=50 \
           UNDO_SLEEP=60 \
           UNDO_THREAD_COUNT=20 \
           BASE_SLEEP=3 \
           BASE_STEP=10 \
           BASE_SLICE_STEP=50 \
           BASE_THREAD_COUNT=20 \
           STEEM_CONFIG='{"host": "172.20.0.4", "port": 3306, "user": "root", "pass": "123456", "db": "steem"}'
COPY . /app
CMD ["/bin/ash"]
