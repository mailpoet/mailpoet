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


if [[ $WORDPRESS_VERSION != "" ]]; then
  echo "Downloading WordPress version: $WORDPRESS_VERSION"
  wp core download --version=$WORDPRESS_VERSION --force
fi

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

echo "WORDPRESS VERSION:"
wp core version

echo "TEST RUNNER PHP VERSION:"
php --version

if [[ -n "$GUTENBERG_VERSION" ]]; then
  echo "Installing Gutenberg plugin version: $GUTENBERG_VERSION"
  if [[ "$GUTENBERG_VERSION" == "latest" ]]; then
    wp plugin install gutenberg --activate --force
  else
    wp plugin install gutenberg --version="$GUTENBERG_VERSION" --activate --force
  fi
  wp plugin get gutenberg --fields=name,status,version
fi

# Force Action Scheduler to use the DB store. Tests deactivate WC often, which
# wipes the migration_status option and reverts AS to the HybridStore — whose
# claim path races on the legacy wpPostStore when async queue-runner requests
# overlap during a test ("Unable to claim actions. Database error.").
mkdir -p /wp-core/wp-content/mu-plugins
cat > /wp-core/wp-content/mu-plugins/mailpoet-test-action-scheduler-store.php <<'PHP'
<?php
add_filter('action_scheduler_store_class', static function () {
    return 'ActionScheduler_DBStore';
}, 200);
add_filter('action_scheduler_logger_class', static function () {
    return 'ActionScheduler_DBLogger';
}, 200);
PHP

# Load Composer dependencies
# Set SKIP_DEPS environment flag to not download them. E.g. you have downloaded them yourself
# Example: docker compose run -e SKIP_DEPS=1 codeception ...
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
      ./do download:woo-commerce-memberships-zip
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

  # Install MU plugin that disables blocks patterns caching – it's needed for acceptance tests
  # caching is dependent on the path, however it differs for the test run and wp-cli so it produces notices and tests fail
  if [[ ! -f "/wp-core/wp-content/mu-plugins/woo-cache-disable.php" ]]; then
    mkdir -p /wp-core/wp-content/mu-plugins
    echo "<?php add_filter('site_transient_woocommerce_blocks_patterns', '__return_false');" > "/wp-core/wp-content/mu-plugins/woo-cache-disable.php"
  fi

  # Install MU plugin that disables WooCommerce's transactional email log (WC 10.9.0+, log source: transactional-emails)
  # It writes a wc-logs file on every order email sent during checkout. Here wp-cli runs as root and Apache as www-data
  # on a shared volume, so that log file can be owned by a different user than the web request; PHP touch() then fails
  # with "Utime failed: Operation not permitted" and the warning fails acceptance tests. The log is irrelevant to the
  # assertions. See WooCommerce PR #64491.
  if [[ ! -f "/wp-core/wp-content/mu-plugins/woo-email-log-disable.php" ]]; then
    mkdir -p /wp-core/wp-content/mu-plugins
    echo "<?php add_filter('woocommerce_email_log_enabled', '__return_false');" > "/wp-core/wp-content/mu-plugins/woo-email-log-disable.php"
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

  # patch WooCommerce CLI issue https://github.com/woocommerce/woocommerce/pull/57291
  # This can be removed once WooCommerce 9.9.0 is released
  WOO_CLI_FILE="/wp-core/wp-content/plugins/woocommerce/includes/class-wc-cli.php"
  sed -i "s/FeaturesUtil::feature_is_enabled( 'blueprint' )/false/g" "$WOO_CLI_FILE"

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

# Always treat submissions as human-like in the test environment. Selenium
# completes form interactions faster than the production thresholds and
# integration tests subscribe directly without ever populating signals, so the
# baseline check would otherwise escalate every form-driven test to the inline
# CAPTCHA. Installed regardless of SKIP_PLUGINS because integration tests run
# without the WooCommerce plugins but still hit the subscribe endpoint.
# Production thresholds stay untouched.
mkdir -p /wp-core/wp-content/mu-plugins
cat > /wp-core/wp-content/mu-plugins/mailpoet-test-behavioral-signals.php <<'PHP'
<?php
add_filter('mailpoet_behavioral_signals_looks_human', '__return_true');
PHP

# Set constants in wp-config.php
wp config set WP_DEBUG true --raw
wp config set WP_DEBUG_DISPLAY true --raw
wp config set WP_DEBUG_LOG true --raw
wp config set COOKIE_DOMAIN \$_SERVER[\'HTTP_HOST\'] --raw
wp config set DISABLE_WP_CRON true --raw
wp config set MAILPOET_USE_CDN false --raw
wp config set FS_METHOD \'direct\' --raw

# activate theme
wp theme install twentytwentyone --activate
if [[ $MULTISITE == "1" ]]; then
  wp theme install twentytwentyone --url=$HTTP_HOST/$WP_TEST_MULTISITE_SLUG --activate
fi
if [[ $BLOCKBASED_THEME == "1" ]]; then
  wp theme install twentytwentyfour --activate
fi

# Install German language for testing in foreign languages
wp language core install de_DE

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

if [[ $PACKAGE_NAME == "email-editor" ]]; then
  cd /wp-core/wp-content/plugins/packages/php/email-editor
else
  if [[ $WITH_PREMIUM == "1" ]]; then
    cd /wp-core/wp-content/plugins/mailpoet-premium
  else
    cd /wp-core/wp-content/plugins/mailpoet
  fi
fi

/tests_env/vendor/bin/codecept run $TEST_TYPE $@ -vvv
exitcode=$?

exit $exitcode
