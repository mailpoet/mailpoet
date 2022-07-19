FROM wordpress:php8.1-apache

ARG UID=1000
ARG GID=1000

# additinal extensions
RUN apt-get update \
	&& apt-get install -y git zlib1g-dev libzip-dev zip wget gnupg msmtp libpng-dev gettext subversion \
	&& \
    # Install NodeJS, enable Corepack
    curl -sL https://deb.nodesource.com/setup_17.x | bash - && \
    apt-get install -y nodejs build-essential && \
    corepack enable && \
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

# xdebug build an config
ENV XDEBUGINI_PATH=/usr/local/etc/php/conf.d/xdebug.ini
RUN git clone -b "3.1.1" --depth 1 https://github.com/xdebug/xdebug.git /usr/src/php/ext/xdebug \
    && docker-php-ext-configure xdebug --enable-xdebug-dev \
    && docker-php-ext-install xdebug \
    && mkdir /tmp/debug
COPY dev/xdebug.ini /tmp/xdebug.ini
RUN cat /tmp/xdebug.ini >> $XDEBUGINI_PATH

# php extensions
RUN docker-php-ext-install pdo_mysql

# allow .htaccess files (between <Directory /var/www/> and </Directory>, which is WordPress installation)
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# ensure existing content in /var/www/html respects UID and GID, give Node permissions for Corepack
RUN chown -R ${UID}:${GID} /var/www/html && \
  mkdir -p /.node && chown -R ${UID}:${GID} /.node
