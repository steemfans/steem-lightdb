FROM alpine:latest
WORKDIR /app
RUN apk --no-cache add python3 python3-dev gcc git musl-dev postgresql-dev libffi-dev\
        && pip3 install steem \
        && pip3 install redis \
        && pip3 install psycopg2 \
        && pip3 install diff_match_patch
ENV BASE_SLEEP=0 \
        BLOCK_STEP=100 \
        POSTGRES_CONFIG='{"host": "172.20.0.4", "port": 5432, "user": "root", "pass": "123456", "db": "steem"}' \
        REDIS_CONFIG='{"host":"172.20.0.5", "port": 6379}'
COPY . /app
CMD ["/app/entry.py"]