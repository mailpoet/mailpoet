FROM mailpoet/wordpress:5.6-cli_20181009.1

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer global require --optimize-autoloader "hirak/prestissimo"

WORKDIR /wp-core/wp-content/plugins/mailpoet
ENV WP_TEST_PATH=/wp-core

ADD docker-entrypoint.sh /

RUN ["chmod", "+x", "/docker-entrypoint.sh"]
