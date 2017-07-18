FROM php:5.6-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && \
    apt-get -y install \
            git \
            zlib1g-dev \
            libssl-dev \
            mysql-client \
            sudo less  \
        --no-install-recommends && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
    docker-php-ext-install bcmath zip mysqli pdo pdo_mysql && \
    echo "date.timezone = UTC" >> /usr/local/etc/php/php.ini && \
    curl -sS https://getcomposer.org/installer | php -- \
        --filename=composer \
        --install-dir=/usr/local/bin && \
    composer global require --optimize-autoloader "hirak/prestissimo" && \
    curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x wp-cli.phar && \
    mv wp-cli.phar /usr/local/bin/wp

# Prepare application
WORKDIR /repo

# Install vendor
COPY ./composer.json /repo/composer.json

# Add source-code
COPY . /repo

WORKDIR /wp-core/wp-content/plugins/mailpoet
ENV WP_TEST_PATH=/wp-core

ADD docker-entrypoint.sh /

RUN ["chmod", "+x", "/docker-entrypoint.sh"]