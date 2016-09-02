FROM php:5.6-cli

RUN apt-get update && apt-get install -yqq zlib1g-dev
RUN curl -Os https://phar.phpunit.de/phpunit.phar && chmod +x phpunit.phar && mv phpunit.phar /usr/local/bin/phpunit
RUN docker-php-ext-install mysqli zip

COPY upload.png /tmp/upload.png

VOLUME ["/usr/src/wordpress-plugin-tests"]
WORKDIR /usr/src/wordpress-plugin-tests

CMD ["phpunit", "integration"]
