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

    wp-su core install --url=wordpress --title=tests --admin_user=admin --admin_email=test@test.com

    echo "Configuring WordPress"
    # The development version of Gravity Flow requires SCRIPT_DEBUG
    wp-su core config --dbhost=mysql --dbname=wordpress --dbuser=wordpress --dbpass=wordpress --extra-php="define( 'SCRIPT_DEBUG', true );" --force

fi

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
