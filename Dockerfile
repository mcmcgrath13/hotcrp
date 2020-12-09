FROM php:7.4-cli

RUN apt-get update && apt-get install -y mariadb-client

RUN docker-php-ext-install mysqli

COPY . /usr/src/hotcrp
WORKDIR /usr/src/hotcrp

# --host=db --grant-host=db --dbuser=test_user,example

ENTRYPOINT mysql -h db hotcrp_testdb < src/schema.sql && php test/setup.php && mysql -h db hotcrp_testdb < test/post_setup.sql
