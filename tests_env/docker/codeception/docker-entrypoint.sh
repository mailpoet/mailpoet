#!/bin/bash

wp() {
  command wp --allow-root "$@"
}

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

if [[ $LATEST_BETA != "" ]]; then
  echo "Installing WordPress beta version: $LATEST_BETA"
  wp core update --version=$LATEST_BETA
fi
echo "WORDPRESS VERSION:"
wp core version

# Load Composer dependencies
# Set SKIP_DEPS environment flag to not download them. E.g. you have downloaded them yourself
# Example: docker-compose run -e SKIP_DEPS=1 codeception ...
if [[ -z "${SKIP_DEPS}" ]]; then
  cd /project
  ./tools/vendor/composer.phar install
  cd - >/dev/null
fi

# Install, activate and print info about plugins that we want to use in tests runtime.
# The plugin activation could be skipped by setting env. variable SKIP_PLUGINS
# E.g. we want to run some tests without the plugins to make sure we are not dependent on those
if [[ $SKIP_PLUGINS != "1" ]]; then
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

  # Install AutomateWoo
  if [[ ! -d "/wp-core/wp-content/plugins/automatewoo" ]]; then
    AUTOMATEWOO_ZIP="/wp-core/wp-content/plugins/mailpoet/tests/plugins/automatewoo.zip"
    if [ ! -f "$AUTOMATEWOO_ZIP" ]; then
      echo "AutomateWoo plugin zip not found. Downloading AutomateWoo plugin latest zip"
      cd /project
      ./do download:automate-woo-zip latest
      cd /wp-core/wp-content/plugins
    fi
    echo "Unzip AutomateWoo plugin from $AUTOMATEWOO_ZIP"
    unzip -q -o "$AUTOMATEWOO_ZIP" -d /wp-core/wp-content/plugins/
  fi

  ACTIVATION_CONTEXT=$HTTP_HOST
  # For integration tests in multisite environment we need to activate the plugin for correct site that is loaded in tests
  # The acceptance tests activate/deactivate plugins using a helper.
  # We still need to activate them here so that we can access WooCommerce code in tests
  if [[ $MULTISITE == "1" && $TEST_TYPE == "integration" ]]; then
    ACTIVATION_CONTEXT="$HTTP_HOST/$WP_TEST_MULTISITE_SLUG"
  fi

  # activate all plugins
  wp plugin activate woocommerce --url=$ACTIVATION_CONTEXT
  wp plugin activate woocommerce-subscriptions --url=$ACTIVATION_CONTEXT
  wp plugin activate woocommerce-memberships --url=$ACTIVATION_CONTEXT
  wp plugin activate automatewoo --url=$ACTIVATION_CONTEXT

  # print info about activated plugins
  wp plugin get woocommerce --url=$ACTIVATION_CONTEXT
  wp plugin get woocommerce-subscriptions --url=$ACTIVATION_CONTEXT
  wp plugin get woocommerce-memberships --url=$ACTIVATION_CONTEXT
  wp plugin get automatewoo --url=$ACTIVATION_CONTEXT

  # Enable HPOS to use (recommended) order storage
  if [[ $ENABLE_HPOS == "1" ]]; then
    wp wc cot enable
    echo "WooCommerce HPOS is enabled!";
  fi

  # Enable Sync of HPOS and posts tables
  if [[ $ENABLE_HPOS_SYNC == "1" ]]; then
    wp wc cot enable --with-sync
    echo "WooCommerce HPOS Synchronization is enabled!";
  fi

  # Disable HPOS and use (legacy) WP posts storage
  if [[ $DISABLE_HPOS == "1" ]]; then
    wp wc cot disable
    echo "WooCommerce HPOS is disabled!";
  fi
fi

# Set constants in wp-config.php
wp config set WP_DEBUG true --raw
wp config set WP_DEBUG_DISPLAY true --raw
wp config set WP_DEBUG_LOG true --raw
wp config set COOKIE_DOMAIN \$_SERVER[\'HTTP_HOST\'] --raw
wp config set DISABLE_WP_CRON true --raw
wp config set MAILPOET_USE_CDN false --raw

# activate theme
wp theme install twentytwentyone --activate
if [[ $MULTISITE == "1" ]]; then
  wp theme install twentytwentyone --url=$HTTP_HOST/$WP_TEST_MULTISITE_SLUG --activate
fi
if [[ $BLOCKBASED_THEME == "1" ]]; then
  wp theme install twentytwentyfour --activate
fi

# Remove Doctrine Annotations (they are not needed since generated metadata are packed)
# We want to remove them for tests to make sure they are really not needed
if [[ $TEST_TYPE == "acceptance" ]] && [[ $CIRCLE_JOB ]]; then
  rm -rf /wp-core/wp-content/plugins/mailpoet/vendor-prefixed/doctrine/annotations
  /wp-core/wp-content/plugins/mailpoet/tools/vendor/composer.phar --working-dir=/wp-core/wp-content/plugins/mailpoet dump-autoload
fi

# activate MailPoet
wp plugin activate mailpoet/mailpoet.php || { echo "MailPoet plugin activation failed!" ; exit 1; }
if [[ $MULTISITE == "1" ]]; then
  wp plugin activate mailpoet/mailpoet.php --url=$HTTP_HOST/$WP_TEST_MULTISITE_SLUG
fi

if [[ $CIRCLE_JOB == *"_with_premium_"* || $WITH_PREMIUM == "1" ]]; then
  # Copy MailPoet Premium to plugin path
#  cp -r -n /project/mailpoet-premium /wp-core/wp-content/plugins/mailpoet-premium
  chown www-data:www-data /wp-core/wp-content/plugins/mailpoet-premium/generated
  chmod -R 755 /wp-core/wp-content/plugins/mailpoet-premium/generated
  # Activate MailPoet Premium
  wp plugin activate mailpoet-premium/mailpoet-premium.php || { echo "MailPoet Premium plugin activation failed!" ; exit 1; }
fi

# WP installs translations into the `lang` folder, and it should be writable, this change has been added in WP 6.2
# make sure folders exist
cd /wp-core
[[ -d wp-content/plugins/mailpoet/lang ]] || mkdir -p wp-content/plugins/mailpoet/lang
[[ -d wp-content/plugins/mailpoet-premium/lang ]] || mkdir -p wp-content/plugins/mailpoet-premium/lang
[[ -d wp-content/languages ]] || mkdir wp-content/languages
[[ -d wp-content/upgrade ]] || mkdir wp-content/upgrade
chown www-data:www-data wp-content/upgrade
chmod -R 777 wp-content/plugins/mailpoet/lang
chmod -R 777 wp-content/plugins/mailpoet-premium/lang
chmod -R 777 wp-content/languages
chmod -R 777 wp-content/upgrade

echo "MySQL Configuration";
# print sql_mode
mysql -u wordpress -pwordpress wordpress -h mysql -e "SELECT @@global.sql_mode"
# print tables info
mysql -u wordpress -pwordpress wordpress -h mysql -e "SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'wordpress'"

if [[ $WITH_PREMIUM == "1" ]]; then
  cd /wp-core/wp-content/plugins/mailpoet-premium
else
  cd /wp-core/wp-content/plugins/mailpoet
fi

/wp-core/wp-content/plugins/mailpoet/vendor/bin/codecept run $TEST_TYPE $@ -vvv
exitcode=$?

exit $exitcode
