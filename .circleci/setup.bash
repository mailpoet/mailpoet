#!/usr/bin/env bash

function setup {
	local version=$1
	local wp_cli_wordpress_path="--path=wordpress"
	local wp_cli_allow_root="--allow-root"

	# install PHP dependencies for WordPress
	if [[ $version == "php7" ]] || [[ $version == "php7_multisite" ]]; then
		echo "deb http://packages.dotdeb.org jessie all" | sudo tee -a /etc/apt/sources.list.d/dotdeb.list
		echo "deb-src http://packages.dotdeb.org jessie all" | sudo tee -a /etc/apt/sources.list.d/dotdeb.list
		wget -qO - http://www.dotdeb.org/dotdeb.gpg | sudo apt-key add -
		sudo apt-get update
		sudo apt-get install mysql-client php7.0-mysql zlib1g-dev
		sudo docker-php-ext-install mysqli pdo pdo_mysql zip
	else
		sudo apt-get update
		sudo apt-get install mysql-client php5-mysql zlib1g-dev
		sudo docker-php-ext-install mysql mysqli pdo pdo_mysql zip
	fi

	# Add a fake sendmail mailer
	sudo cp ./.circleci/fake-sendmail.php /usr/local/bin/

	# configure Apache
	sudo cp ./.circleci/mailpoet_php.ini /usr/local/etc/php/conf.d/
	sudo cp ./.circleci/apache/mailpoet.loc.conf /etc/apache2/sites-available
	sudo a2dissite 000-default.conf
	sudo a2ensite mailpoet.loc
	sudo a2enmod rewrite
	sudo service apache2 restart

	# Install NodeJS+NPM
	curl -sL https://deb.nodesource.com/setup_6.x | sudo -E bash -
	sudo apt-get install nodejs build-essential

	# install plugin dependencies
	curl -sS https://getcomposer.org/installer | php
	./composer.phar install
	./do install

	# Set up WordPress
	mysql -h 127.0.0.1 -u root -e "create database wordpress"
	curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	chmod +x wp-cli.phar
	sudo mv wp-cli.phar /usr/local/bin/wp
	wp core download $wp_cli_wordpress_path $wp_cli_allow_root

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
    	echo "WP_TEST_PATH_MULTISITE=/home/circleci/mailpoet/wordpress" >> .env
    	echo "HTTP_HOST=mailpoet.loc" >> .env

    	# Add a third dummy blog
        wp site create --slug=dummy_multisite $wp_cli_wordpress_path $wp_cli_allow_root
    else
    	wp core install --admin_name=admin --admin_password=admin --admin_email=admin@mailpoet.loc --url=http://mailpoet.loc --title="WordPress Single" $wp_cli_wordpress_path $wp_cli_allow_root
    	echo "WP_TEST_PATH=/home/circleci/mailpoet/wordpress" >> .env
    fi

	# Softlink plugin to plugin path
	ln -s ../../.. wordpress/wp-content/plugins/mailpoet

	# Activate plugin
	if [[ $version == "php7_multisite" ]]; then
	    wp plugin activate mailpoet --url=http://mailpoet.loc/php7_multisite/ $wp_cli_wordpress_path $wp_cli_allow_root
	else
	    wp plugin activate mailpoet $wp_cli_wordpress_path $wp_cli_allow_root
	fi
}