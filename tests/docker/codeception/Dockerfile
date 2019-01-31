FROM mailpoet/wordpress:7.2-cli_20181215.1

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer global require --optimize-autoloader "hirak/prestissimo"

WORKDIR /wp-core/wp-content/plugins/mailpoet
ENV WP_ROOT=/wp-core

ADD tests/docker/codeception/docker-entrypoint.sh /

RUN ["chmod", "+x", "/docker-entrypoint.sh"]
