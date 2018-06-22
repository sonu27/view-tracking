FROM eu.gcr.io/the-dots/infrastructure-php:0.5

WORKDIR /app

ADD . /app/

ENV APP_ENV=prod

RUN composer install --no-interaction --no-dev --no-progress --no-suggest --classmap-authoritative && \
    chown -R www-data:www-data /app
