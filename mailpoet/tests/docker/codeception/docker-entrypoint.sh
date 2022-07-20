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
chmod -R 777 /mailhog-data

# deleting configs in case are set in previous run
wp config delete MULTISITE > /dev/null 2>&1
wp config delete WP_ALLOW_MULTISITE > /dev/null 2>&1
wp config delete SUBDOMAIN_INSTALL > /dev/null 2>&1
wp config delete DOMAIN_CURRENT_SITE > /dev/null 2>&1
wp config delete PATH_CURRENT_SITE > /dev/null 2>&1
wp config delete SITE_ID_CURRENT_SITE > /dev/null 2>&1
wp config delete BLOG_ID_CURRENT_SITE > /dev/null 2>&1

# disable automatic updates
wp config set WP_AUTO_UPDATE_CORE false --raw

# cleanup database
mysqladmin --host=mysql --user=root --password=wordpress drop wordpress --force
mysqladmin --host=mysql --user=root --password=wordpress create wordpress --force

# install WordPress
WP_CORE_INSTALL_PARAMS="--url=$HTTP_HOST --title=tests --admin_user=admin --admin_email=test@test.com --admin_password=password --skip-email"
if [[ -z "$MULTISITE" || "$MULTISITE" -eq "0" ]]; then
  echo 'Installing WordPress (single site mode)'
  wp core install $WP_CORE_INSTALL_PARAMS
else
  echo 'Installing WordPress (multisite mode)'
  wp core multisite-install $WP_CORE_INSTALL_PARAMS
  wp site create --slug=$WP_TEST_MULTISITE_SLUG
fi

# Load Composer dependencies
# Set SKIP_DEPS environment flag to not download them. E.g. you have downloaded them yourself
# Example: docker-compose run -e SKIP_DEPS=1 codeception ...
if [[ -z "${SKIP_DEPS}" ]]; then
  cd /project
  ./tools/vendor/composer.phar install
  cd - >/dev/null
fi

# extra plugins are not supported for integration tests yest
if [[ $TEST_TYPE == "acceptance" ]]; then
# Install WooCommerce
if [[ ! -d "/wp-core/wp-content/plugins/woocommerce" ]]; then
  cd /wp-core/wp-content/plugins
  WOOCOMMERCE_CORE_ZIP="/wp-core/wp-content/plugins/mailpoet/tests/plugins/woocommerce.zip"
  if [ ! -f "$WOOCOMMERCE_CORE_ZIP" ]; then
    echo "WooCommerce plugin zip not found. Downloading WooCommerce plugin latest zip"
    cd /project
    ./do download:woo-commerce-zip latest
    cd /wp-core/wp-content/plugins
  fi

  echo "Unzip Woocommerce plugin from $WOOCOMMERCE_CORE_ZIP"
  unzip -q -o "$WOOCOMMERCE_CORE_ZIP" -d /wp-core/wp-content/plugins/
fi

# Install WooCommerce Subscriptions
if [[ ! -d "/wp-core/wp-content/plugins/woocommerce-subscriptions" ]]; then
  WOOCOMMERCE_SUBS_ZIP="/wp-core/wp-content/plugins/mailpoet/tests/plugins/woocommerce-subscriptions.zip"
  if [ ! -f "$WOOCOMMERCE_SUBS_ZIP" ]; then
    echo "WooCommerce Subscriptions plugin zip not found. Downloading WooCommerce Subscription plugin latest zip"
    cd /project
    ./do download:woo-commerce-subscriptions-zip latest
    cd /wp-core/wp-content/plugins
  fi
  echo "Unzip Woocommerce Subscription plugin from $WOOCOMMERCE_SUBS_ZIP"
  unzip -q -o "$WOOCOMMERCE_SUBS_ZIP" -d /wp-core/wp-content/plugins/
fi

# Install WooCommerce Memberships
if [[ ! -d "/wp-core/wp-content/plugins/woocommerce-memberships" ]]; then
  WOOCOMMERCE_MEMBERSHIPS_ZIP="/wp-core/wp-content/plugins/mailpoet/tests/plugins/woocommerce-memberships.zip"
  if [ ! -f "$WOOCOMMERCE_MEMBERSHIPS_ZIP" ]; then
    echo "WooCommerce Memberships plugin zip not found. Downloading WooCommerce Memberships plugin latest zip"
    cd /project
    ./do download:woo-commerce-memberships-zip latest
    cd /wp-core/wp-content/plugins
  fi
  echo "Unzip Woocommerce Memberships plugin from $WOOCOMMERCE_MEMBERSHIPS_ZIP"
  unzip -q -o "$WOOCOMMERCE_MEMBERSHIPS_ZIP" -d /wp-core/wp-content/plugins/
fi

# Install WooCommerce Blocks
if [[ ! -d "/wp-core/wp-content/plugins/woo-gutenberg-products-block" ]]; then
  WOOCOMMERCE_BLOCKS_ZIP="/wp-core/wp-content/plugins/mailpoet/tests/plugins/woo-gutenberg-products-block.zip"
  if [ ! -f "$WOOCOMMERCE_BLOCKS_ZIP" ]; then
    echo "WooCommerce Blocks plugin zip not found. Downloading WooCommerce Blocks plugin latest zip"
    cd /project
    ./do download:woo-commerce-blocks-zip latest
    cd /wp-core/wp-content/plugins
  fi
  echo "Unzip Woocommerce Blocks plugin from $WOOCOMMERCE_BLOCKS_ZIP"
  unzip -q -o "$WOOCOMMERCE_BLOCKS_ZIP" -d /wp-core/wp-content/plugins/
fi

# Install a fix plugin for PHPMailer on WP 5.6
cp /project/tests/docker/codeception/wp-56-phpmailer-fix.php /wp-core/wp-content/plugins/wp-56-phpmailer-fix.php
wp plugin activate wp-56-phpmailer-fix

# activate all plugins which source code want to access in tests runtime
wp plugin activate woocommerce
wp plugin activate woocommerce-subscriptions
wp plugin activate woocommerce-memberships
wp plugin activate woo-gutenberg-products-block
fi # end of check for enabling extra plugins

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

sed -i "s/define( *'WP_DEBUG', false *);/$CONFIG/" /wp-core/wp-config.php

# activate theme
wp theme activate twentytwentyone

if [[ $CIRCLE_JOB == *"_oldest"* ]]; then
  wp theme activate twentynineteen
fi

if [[ $TEST_TYPE == "acceptance" ]]; then
  # print info about installed plugins
  wp plugin get woocommerce
  wp plugin get woocommerce-subscriptions
  wp plugin get woocommerce-memberships
  wp plugin get woo-gutenberg-products-block
fi
# activate MailPoet
wp plugin activate mailpoet/mailpoet.php
if [[ $MULTISITE == "1" ]]; then
  wp plugin activate mailpoet/mailpoet.php --url=http://test.local/php7_multisite/
fi

if [[ $CIRCLE_JOB == *"_with_premium_"* ]]; then
  # Copy MailPoet Premium to plugin path
  cp -r -n /project/mp3premium /wp-core/wp-content/plugins/mailpoet-premium
  # Activate MailPoet Premium
  wp plugin activate mailpoet-premium
fi

cd /wp-core/wp-content/plugins/mailpoet
# Remove Doctrine Annotations (no need since generated metadata are packed)
if [[ $TEST_TYPE == "acceptance" ]] && [[ $CIRCLE_JOB ]]; then
  rm -rf ./vendor-prefixed/doctrine/annotations
  ./tools/vendor/composer.phar dump-autoload
fi
/project/vendor/bin/codecept run $TEST_TYPE $@
exitcode=$?

exit $exitcode
