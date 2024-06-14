#!/bin/bash
set -e

# Install PHP extensions required by MailPoet
docker-php-ext-install pdo_mysql

# Execute the original command
exec "$@"
