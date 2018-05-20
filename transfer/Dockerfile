FROM ety001/php:7.1.17
ENV CHAIN_DB="mysql://steem:steem@steem-lightdb.com:3306/steemdb"
ENV DATABASE_URL="mysql://root:123456@127.0.0.1:3306/steem"
ENV APP_ENV="prod"
VOLUME /app
WORKDIR /app
#RUN /usr/bin/php bin/console doctrine:migrations:migrate
CMD ["/usr/bin/php", "bin/console", "transfer:run"]
