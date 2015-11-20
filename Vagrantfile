# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure(2) do |config|
  config.vm.provider "virtualbox" do |v|
    v.name = "phoenix"
    v.memory = "2048"
  end

  config.vm.define :web do |web|
    web.vm.box = "ubuntu/trusty64"
    web.vm.hostname = "phoenix"
    web.vm.network "forwarded_port", guest: 80, host: 8080
    web.vm.synced_folder(
      ".",
      "/var/www/html/wp-content/plugins/wysija-newsletters",
      create: true,
      owner: "vagrant",
      group: "www-data"
    )

    web.vm.provision "shell", inline: <<-SHELL
    sudo apt-get update
    sudo apt-get install -y apache2 curl zip sendmail git build-essential

    sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password root'
    sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password root'
    sudo apt-get install -y mysql-server-5.5

    sudo apt-get install -y php5 libapache2-mod-php5 php5-curl php5-gd php5-mcrypt php5-readline mysql-server-5.5 php5-mysql php-apc
    sudo sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php5/apache2/php.ini
    sudo sed -i "s/display_errors = .*/display_errors = On/" /etc/php5/apache2/php.ini

    cd /var/www/html

    sudo wget https://github.com/calvinlough/sqlbuddy/raw/gh-pages/sqlbuddy.zip -O /var/www/html/sqlbuddy.zip
    sudo rm index.html
    unzip sqlbuddy.zip

    sudo curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    sudo chmod +x wp-cli.phar
    sudo mv wp-cli.phar /usr/local/bin/wp
    sudo wp core download --allow-root
    mysql -uroot -proot -e "create database wordpress"
    sudo wp core config --allow-root --dbname=wordpress --dbuser=root --dbpass=root
    sudo wp core install --allow-root --url="http://localhost:8080" --title=WordPress --admin_user=admin --admin_password=password --admin_email=test@mailpoet-container.com

    sudo sed -i "s/upload_max_filesize = .*/upload_max_filesize = 32M/" /etc/php5/apache2/php.ini
    sudo sed -i "s/post_max_size = .*/post_max_size = 32M/" /etc/php5/apache2/php.ini
    sudo chown -hR vagrant:www-data /var/www/html/
    sudo a2enmod rewrite > /dev/null 2>&1

    cd /var/www/html/wp-content/plugins/wysija-newsletters

    curl -sS https://getcomposer.org/installer | php

    sudo add-apt-repository -y ppa:chris-lea/node.js
    sudo apt-get update
    sudo apt-get install -y nodejs

    sudo sed -i "s/export APACHE_RUN_USER.*/export APACHE_RUN_USER=vagrant/" /etc/apache2/envvars
    sudo chown -R vagrant:www-data /var/lock/apache2

    sudo service apache2 restart
    SHELL
  end
end
