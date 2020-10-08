#!/usr/bin/env bash

function setup {
	local version=$1
	local wp_cli_wordpress_path="--path=wordpress"
	local wp_cli_allow_root="--allow-root"

	# Add a fake sendmail mailer
	sudo cp ./.circleci/fake-sendmail.php /usr/local/bin/

	# configure Apache
	sudo cp ./.circleci/mailpoet_php.ini /usr/local/etc/php/conf.d/
	sudo cp ./.circleci/apache/mailpoet.loc.conf /etc/apache2/sites-available
	sudo a2dissite 000-default.conf
	sudo a2ensite mailpoet.loc
	sudo a2enmod rewrite
	sudo service apache2 restart

	until mysql -h 127.0.0.1 -u root -e "select 1"; do
        >&2 echo "Mysql is starting up ... will try again momentarily"
        sleep 1
    done

	# Set up WordPress
	mysql -h 127.0.0.1 -u root -e "create database wordpress"
	wp core download $wp_cli_wordpress_path $wp_cli_allow_root --version=${2:-latest}

	# Generate `wp-config.php` file with debugging enabled
	echo "define(\"WP_DEBUG\", true);" | wp core config --dbname=wordpress --dbuser=root --dbhost=127.0.0.1 --extra-php $wp_cli_wordpress_path $wp_cli_allow_root

	# Change default table prefix
	sed -i "s/\$table_prefix = 'wp_';/\$table_prefix = 'mp_';/" ./wordpress/wp-config.php

	# Install WordPress
    if [[ $version == "php7_multisite" ]]; then
        # Configure multisite environment
    	wp core multisite-install --admin_name=admin --admin_password=admin --admin_email=admin@mailpoet.loc --url=http://mailpoet.loc --title="WordPress MultiSite" $wp_cli_wordpress_path $wp_cli_allow_root
    	cp ./.circleci/wordpress/.htaccess ./wordpress/

    	# Add a second blog
    	wp site create --slug=php7_multisite $wp_cli_wordpress_path $wp_cli_allow_root
    	echo "WP_TEST_MULTISITE_SLUG=php7_multisite" >> .env
    	echo "WP_ROOT_MULTISITE=/home/circleci/mailpoet/wordpress" >> .env
    	echo "HTTP_HOST=mailpoet.loc" >> .env

    	# Add a third dummy blog
        wp site create --slug=dummy_multisite $wp_cli_wordpress_path $wp_cli_allow_root
    else
    	wp core install --admin_name=admin --admin_password=admin --admin_email=admin@mailpoet.loc --url=http://mailpoet.loc --title="WordPress Single" $wp_cli_wordpress_path $wp_cli_allow_root
    	echo "WP_ROOT=/home/circleci/mailpoet/wordpress" >> .env
    fi

	# Softlink plugin to plugin path
	ln -s ../../.. wordpress/wp-content/plugins/mailpoet

	# Activate plugin
	if [[ $version == "php7_multisite" ]]; then
	    wp plugin activate mailpoet --url=http://mailpoet.loc/php7_multisite/ $wp_cli_wordpress_path $wp_cli_allow_root
	else
	    wp plugin activate mailpoet $wp_cli_wordpress_path $wp_cli_allow_root
	fi

  if [[ $CIRCLE_JOB == *"_with_premium_"* ]]; then
    # Softlink MailPoet Premium to plugin path
    ln -s ../../../mp3premium wordpress/wp-content/plugins/mailpoet-premium
    # Activate MailPoet Premium
    wp plugin activate mailpoet-premium --path=wordpress
  fi
}
