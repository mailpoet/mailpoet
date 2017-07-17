#!/usr/bin/env bash

function setup {
	local version=$1
	# install PHP dependencies for WordPress
	if [[ $version == "php7" ]]; then
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
	# Set up Wordpress
	mysql -h 127.0.0.1 -u root -e "create database wordpress"
	curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	chmod +x wp-cli.phar
	./wp-cli.phar core download --allow-root --path=wordpress
	# Generate `wp-config.php` file with debugging enabled
	echo "define(\"WP_DEBUG\", true);" | ./wp-cli.phar core config --allow-root --dbname=wordpress --dbuser=root --dbhost=127.0.0.1 --path=wordpress --extra-php
	# Install WordPress
	./wp-cli.phar core install --allow-root --admin_name=admin --admin_password=admin --admin_email=admin@mailpoet.loc --url=http://mailpoet.loc:8080 --title=WordPress --path=wordpress
	# Softlink plugin to plugin path
	ln -s ../../.. wordpress/wp-content/plugins/mailpoet
	./wp-cli.phar plugin activate mailpoet --path=wordpress
	# Create .env file with correct path to WP installation
	# TODO: Remove this line after PR gets merged and CircleCI env variables change
	echo "WP_TEST_PATH=\"/home/circleci/mailpoet/wordpress\"" > .env
}
