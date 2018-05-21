FROM ety001/php7-ubuntu:7.2.5
ENV CHAIN_DB="mysql://steem:steem@steem-lightdb.com:3306/steemdb"
ENV DATABASE_URL="mysql://root:123456@127.0.0.1:3306/steem"
ENV APP_ENV="prod"
ADD . /app
WORKDIR /app
RUN composer update
#RUN /usr/bin/php bin/console doctrine:migrations:migrate
CMD ["/usr/bin/php", "bin/console", "transfer:run"]
