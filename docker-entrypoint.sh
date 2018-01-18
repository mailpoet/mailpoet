#!/bin/bash

# Allows WP CLI to run with the right permissions.
wp-su() {
    sudo -E -u www-data wp "$@"
}

while ! mysqladmin ping -hmysql --silent; do
    echo 'Waiting for the database'
    sleep 1
done


# Make sure permissions are correct.
cd /wp-core
chown www-data:www-data wp-content/plugins
chown www-data:www-data wp-content/uploads
chmod 755 wp-content/plugins
chmod -R 777 wp-content/uploads

# Make sure WordPress is installed.
if ! $(wp-su core is-installed); then

    echo "Installing WordPress"

    if [ -z "${MULTISITE}" ]
    then

      echo "Running in single site mode"

      wp-su core install --url=wordpress --title=tests --admin_user=admin --admin_email=test@test.com

      cp /project/tests/_data/acceptanceDump.sql /project/tests/_data/acceptanceGenerated.sql

    else

      wp-su core multisite-install --url=wordpress --title=tests --admin_user=admin --admin_email=test@test.com

      cp /project/tests/_data/acceptanceMultisiteDump.sql /project/tests/_data/acceptanceGenerated.sql
      cat /project/tests/_data/acceptanceDump.sql >> /project/tests/_data/acceptanceGenerated.sql
      echo "Running in multi site mode"
      echo "
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]

# add a trailing slash to /wp-admin
RewriteRule ^wp-admin$ wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^(wp-(content|admin|includes).*) $1 [L]
RewriteRule ^(.*\.php)$ $1 [L]
RewriteRule . index.php [L]
" > .htaccess
    fi

else

    if [ -z "${MULTISITE}" ] &&  $(wp-su core is-installed --network)
    then
      echo "xyxicdufd"
      echo -e "\033[0;31m--------------------------------------------------------------------"
      echo -e "\033[0;31m--------------------------------------------------------------------"
      echo -e "\033[0;31m--------------------------------------------------------------------"
      echo -e "\033[0;31m-------------------------WARNING!!!!!!!!----------------------------"
      echo -e "\033[0;31m-            You are trying to run tests in single site mode       -"
      echo -e "\033[0;31m-  But the container has been already installed in multi site mode -"
      echo -e "\033[0;31m-    You need to delete your installation first. Use ./do d:d      -"
      echo -e "\033[0;31m--------------------------------------------------------------------"
      echo -e "\033[0;31m--------------------------------------------------------------------"
      echo -e "\033[0;31m--------------------------------------------------------------------"
      exit

    fi
    if [ ! -z "${MULTISITE}" ] && ( ! $(wp-su core is-installed --network) )
    then
      echo -e "\033[0;31m--------------------------------------------------------------------"
      echo -e "\033[0;31m--------------------------------------------------------------------"
      echo -e "\033[0;31m--------------------------------------------------------------------"
      echo -e "\033[0;31m-------------------------WARNING!!!!!!!!----------------------------"
      echo -e "\033[0;31m-            You are trying to run tests in multi site mode        -"
      echo -e "\033[0;31m- But the container has been already installed in single site mode -"
      echo -e "\033[0;31m-    You need to delete your installation first. Use ./do d:d      -"
      echo -e "\033[0;31m--------------------------------------------------------------------"
      echo -e "\033[0;31m--------------------------------------------------------------------"
      echo -e "\033[0;31m--------------------------------------------------------------------"
      exit
    fi

fi

sed -i "s/define( *'WP_DEBUG', false *);/define('WP_DEBUG', true);define('WP_DEBUG_DISPLAY', true);define('WP_DEBUG_LOG', true);/" ./wp-config.php

# Load Composer dependencies
# Set KEEP_DEPS environment flag to not redownload them on each run, only for the 1st time, useful for development.
# Example: docker-compose run -e KEEP_DEPS=1 codeception ...
# Don't forget to restore your original /vendor folder from /vendor_backup manually or by running acceptance tests without this flag.
if [[ -z "${KEEP_DEPS}" ]]; then
  rm -rf /project/vendor_backup
fi
if [ ! -d "/project/vendor_backup" ]; then
  mv /project/vendor /project/vendor_backup
  cd /project
  php composer.phar install
fi

cd /wp-core/wp-content/plugins/mailpoet

/project/vendor/bin/codecept run acceptance -c codeception.acceptance.yml $@
exitcode=$?

if [[ -z "${KEEP_DEPS}" ]]; then
  rm -rf /project/vendor
  mv /project/vendor_backup /project/vendor
fi

exit $exitcode
