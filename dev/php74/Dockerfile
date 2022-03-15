FROM wordpress:php7.4

ARG UID=1000
ARG GID=1000

# additinal extensions
RUN apt-get update \
	&& apt-get install -y git zlib1g-dev wget gnupg msmtp gettext zip subversion \
	&& docker-php-ext-install pdo_mysql \
	&& pecl install xdebug-2.9.8 && \
	\
    # Install NodeJS + NPM
    curl -sL https://deb.nodesource.com/setup_16.x | bash - && \
    apt-get install -y nodejs build-essential && \
	\
	# Install WP-CLI
	curl -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
	chmod +x /usr/local/bin/wp && \
	\
	# Clean up
	apt-get clean && \
	rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY dev/php.ini /usr/local/etc/php/conf.d/php_user.ini

# msmtp config
RUN printf "account default\nhost smtp\nport 1025" > /etc/msmtprc

# xdebug config
ENV XDEBUGINI_PATH=/usr/local/etc/php/conf.d/xdebug.ini
RUN echo "zend_extension="`find /usr/local/lib/php/extensions/ -iname 'xdebug.so'` > $XDEBUGINI_PATH
COPY dev/php74/xdebug.ini /tmp/xdebug.ini
RUN cat /tmp/xdebug.ini >> $XDEBUGINI_PATH

# allow .htaccess files (between <Directory /var/www/> and </Directory>, which is WordPress installation)
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# ensure existing content in /var/www/html respects UID and GID
RUN chown -R ${UID}:${GID} /var/www/html
