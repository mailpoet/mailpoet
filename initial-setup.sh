#!/bin/bash

# try to clone mailpoet-premium, skip on failure (i.e. no access rights)
git clone git@github.com:mailpoet/mailpoet-premium.git 2>/dev/null \
  || echo "Skipped cloning 'mailpoet-premium' (check your access rights)"

# Save current UID and GID to .env so we can run images with current user
# to avoid any potential problems with file permissions (mainly on Linux).
cat <<EOT > .env
UID=$(id -u)
UID=$(id -g)
EOT

# create plugin .env files if they don't exist
cp -n mailpoet/.env.sample mailpoet/.env
[[ -f mailpoet-premium/.env.sample ]] && cp -n mailpoet-premium/.env.sample mailpoet-premium/.env

# create Docker mount endpoints beforehand with current user (Docker would create them as root)
mkdir -p wordpress/wp-content/plugins/mailpoet
mkdir -p wordpress/wp-content/plugins/mailpoet-premium
mkdir -p fake_mail_output

for plugin in "mailpoet" "mailpoet-premium"; do
  docker-compose run --rm wordpress /bin/sh -c "
    [ -d /var/www/html/wp-content/plugins/$plugin ] &&
    cd /var/www/html/wp-content/plugins/$plugin &&
    ./do install &&
    ./do compile:all
  "
done

docker-compose run --rm wordpress /bin/sh -c "
  cd /var/www/templates &&
  mkdir assets classes exported
"
echo 'Don‘t forget to configure environment variables in .env files in ./mailpoet and ./mailpoet-premium'
echo 'You can run the environment by executing "./do start" and visiting http://localhost:8002'
