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
chmod 755 wp-content/plugins

# Make sure WordPress is installed.
if ! $(wp-su core is-installed); then

    echo "Installing WordPress"

    wp-su core install --url=wordpress --title=tests --admin_user=admin --admin_email=test@test.com

    echo "Configuring WordPress"
    # The development version of Gravity Flow requires SCRIPT_DEBUG
    wp-su core config --dbhost=mysql --dbname=wordpress --dbuser=wordpress --dbpass=wordpress --extra-php="define( 'SCRIPT_DEBUG', true );" --force

fi

cd /wp-core/wp-content/plugins/mailpoet

/project/vendor/bin/codecept run acceptance -c codeception.acceptance.yml $@