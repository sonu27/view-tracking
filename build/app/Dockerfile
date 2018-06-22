FROM eu.gcr.io/the-dots/infrastructure-php:0.5

ADD ./composer.json ./composer.lock /app/

WORKDIR /app

RUN composer install --no-interaction --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress --no-suggest --quiet

ADD . /app/

RUN chown -R www-data:www-data /app && \
    composer dump-autoload --no-interaction --quiet --classmap-authoritative && \
    chown -R www-data:www-data /app
