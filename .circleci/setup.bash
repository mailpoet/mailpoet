#!/usr/bin/env bash

function setup {
  local script_dir="$(cd -P -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
  local root_dir="$(dirname "$script_dir")"

	local version=$1
	local wp_cli_wordpress_path="--path=$root_dir/wordpress"
	local wp_cli_allow_root="--allow-root"

	# Add a fake sendmail mailer
	sudo cp "$script_dir/fake-sendmail.php" /usr/local/bin/

	# configure Apache
	sudo cp "$script_dir/mailpoet_php.ini" /usr/local/etc/php/conf.d/
	sudo cp "$script_dir/apache/mailpoet.loc.conf" /etc/apache2/sites-available
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

  # Disable WP Cron so that it doesn't interfere with tests
  wp config set DISABLE_WP_CRON true --raw $wp_cli_wordpress_path $wp_cli_allow_root

	# Change default table prefix
	sed -i "s/\$table_prefix = 'wp_';/\$table_prefix = 'mp_';/" "$root_dir/wordpress/wp-config.php"

	# Install WordPress
    if [[ $version == "php7_multisite" ]]; then
        # Configure multisite environment
    	wp core multisite-install --admin_name=admin --admin_password=admin --admin_email=admin@mailpoet.loc --url=http://mailpoet.loc --title="WordPress MultiSite" $wp_cli_wordpress_path $wp_cli_allow_root
    	cp "$script_dir/wordpress/.htaccess" "$root_dir/wordpress/"

    	# Add a second blog
    	wp site create --slug=php7_multisite $wp_cli_wordpress_path $wp_cli_allow_root
    	echo "WP_TEST_MULTISITE_SLUG=php7_multisite" >> "$root_dir/mailpoet/.env"
    	echo "WP_ROOT_MULTISITE=/home/circleci/mailpoet/wordpress" >> "$root_dir/mailpoet/.env"
    	echo "HTTP_HOST=mailpoet.loc" >> "$root_dir/mailpoet/.env"

    	# Add a third dummy blog
        wp site create --slug=dummy_multisite $wp_cli_wordpress_path $wp_cli_allow_root
    else
    	wp core install --admin_name=admin --admin_password=admin --admin_email=admin@mailpoet.loc --url=http://mailpoet.loc --title="WordPress Single" $wp_cli_wordpress_path $wp_cli_allow_root
    	echo "WP_ROOT=/home/circleci/mailpoet/wordpress" >> "$root_dir/mailpoet/.env"
    fi

	# Softlink plugin to plugin path
	ln -s ../../../mailpoet ../wordpress/wp-content/plugins/mailpoet

	# Activate plugin
	if [[ $version == "php7_multisite" ]]; then
	    wp plugin activate mailpoet --url=http://mailpoet.loc/php7_multisite/ $wp_cli_wordpress_path $wp_cli_allow_root
	else
	    wp plugin activate mailpoet $wp_cli_wordpress_path $wp_cli_allow_root
	fi

  if [[ $CIRCLE_JOB == *"_with_premium_"* ]]; then
    # Softlink MailPoet Premium to plugin path
    ln -s ../../../mailpoet-premium ../wordpress/wp-content/plugins/mailpoet-premium
    # Activate MailPoet Premium
    wp plugin activate mailpoet-premium --path="$root_dir/wordpress"
  fi
}
