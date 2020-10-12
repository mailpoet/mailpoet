#!/bin/bash

wp() {
  command wp --allow-root "$@"
}

# wait for database container to be ready
while ! mysqladmin ping -hmysql --silent; do
  echo 'Waiting for the database'
  sleep 1
done

# wait for WordPress container to be ready (otherwise tests may
# try to run without 'wp-config.php' being properly configured)
while ! bash -c "echo > /dev/tcp/wordpress/80" &>/dev/null; do
  echo 'Waiting for WordPress'
  sleep 1
done

# make sure permissions are correct
cd /wp-core
chown www-data:www-data wp-content
chown www-data:www-data wp-content/plugins
chown www-data:www-data wp-content/uploads
chmod 755 wp-content/plugins
chmod -R 777 wp-content/uploads

# backup original 'wp-config.php' created by WordPress image
if [ ! -f wp-config-original.php ]; then
  cp wp-config.php wp-config-original.php
fi

# cleanup 'wp-config.php' and database
rm -f wp-config.php && cp wp-config-original.php wp-config.php
mysqladmin --host=mysql --user=root --password=wordpress drop wordpress --force
mysqladmin --host=mysql --user=root --password=wordpress create wordpress --force

# install WordPress
WP_CORE_INSTALL_PARAMS="--url=test.local --title=tests --admin_user=admin --admin_email=test@test.com --admin_password=password --skip-email"
if [[ -z "$MULTISITE" || "$MULTISITE" -eq "0" ]]; then
  echo 'Installing WordPress (single site mode)'
  wp core install $WP_CORE_INSTALL_PARAMS
else
  echo 'Installing WordPress (multisite mode)'
  wp core multisite-install $WP_CORE_INSTALL_PARAMS
fi

# install WooCommerce (activate & deactivate it to populate DB for backup)
wp plugin install woocommerce
wp plugin activate woocommerce
wp plugin deactivate woocommerce

# add configuration
CONFIG=''
CONFIG+="define('WP_DEBUG', true);\n"
CONFIG+="define('WP_DEBUG_DISPLAY', true);\n"
CONFIG+="define('WP_DEBUG_LOG', true);\n"
CONFIG+="define('COOKIE_DOMAIN', \$_SERVER['HTTP_HOST']);\n"
CONFIG+="define('WP_AUTO_UPDATE_CORE', false);\n"
CONFIG+="define('DISABLE_WP_CRON', true);\n"

# fix for WP CLI bug (https://core.trac.wordpress.org/ticket/44569)
CONFIG+="if (!isset(\$_SERVER['SERVER_NAME'])) \$_SERVER['SERVER_NAME'] = '';\n"

sed -i "s/define( *'WP_DEBUG', false *);/$CONFIG/" ./wp-config.php

# Load Composer dependencies
# Set SKIP_DEPS environment flag to not download them. E.g. you have downloaded them yourself
# Example: docker-compose run -e SKIP_DEPS=1 codeception ...
if [[ -z "${SKIP_DEPS}" ]]; then
  cd /project
  ./tools/vendor/composer.phar install
  cd - >/dev/null
fi

# activate MailPoet
wp plugin activate mailpoet/mailpoet.php

if [[ $CIRCLE_JOB == *"_with_premium_"* ]]; then
  # Softlink MailPoet Premium to plugin path
  ln -s /project/mp3premium /wp-core/wp-content/plugins/mailpoet-premium
  # Activate MailPoet Premium
  wp plugin activate mailpoet-premium
fi

cd /wp-core/wp-content/plugins/mailpoet

/project/vendor/bin/codecept run acceptance $@
exitcode=$?

exit $exitcode
