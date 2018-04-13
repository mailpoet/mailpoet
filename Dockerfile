FROM mailpoet/wordpress:5.6-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer global require --optimize-autoloader "hirak/prestissimo"

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
