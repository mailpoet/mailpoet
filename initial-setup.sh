#!/bin/bash

git clone git@github.com:mailpoet/mailpoet.git
git clone git@github.com:mailpoet/mailpoet-premium.git

# Save current UID and GID to .env so we can run images with current user
# to avoid any potential problems with file permissions (mainly on Linux).
cat <<EOT > .env
UID=$(id -u)
UID=$(id -g)
EOT

cat <<EOT > mailpoet/.env

WP_ROOT="/var/www/html"
WP_TEST_CACHE_PATH="/tmp"
WP_TEST_MAILER_ENABLE_SENDING="true"
WP_TEST_ENABLE_NETWORK_TESTS="true"

# get following secrets from the Secret Store. Look for "MailPoet plugin .env"
WP_TRANSIFEX_API_TOKEN=
WP_TEST_IMPORT_MAILCHIMP_API=
WP_TEST_IMPORT_MAILCHIMP_LISTS=
WP_TEST_MAILER_AMAZON_ACCESS=
WP_TEST_MAILER_AMAZON_SECRET=
WP_TEST_MAILER_AMAZON_REGION=
WP_TEST_MAILER_MAILPOET_API=
WP_TEST_MAILER_SENDGRID_API=
WP_TEST_MAILER_SMTP_HOST=
WP_TEST_MAILER_SMTP_LOGIN=
WP_TEST_MAILER_SMTP_PASSWORD=
WP_SVN_USERNAME=
WP_SVN_PASSWORD=
WP_SLACK_WEBHOOK_URL=
WP_CIRCLECI_USERNAME=
WP_CIRCLECI_TOKEN=

# get GitHub token from https://github.com/settings/tokens with repo access
WP_GITHUB_USERNAME=
WP_GITHUB_TOKEN=

# get Jira token from https://id.atlassian.com/manage/api-tokens
WP_JIRA_USER=
WP_JIRA_TOKEN=

EOT
cp mailpoet/.env mailpoet-premium
echo "MAILPOET_FREE_PATH=/var/www/html/wp-content/plugins/mailpoet" >> mailpoet-premium/.env

# create Docker mount endpoints beforehand with current user (Docker would create them as root)
mkdir -p wordpress/wp-content/plugins/mailpoet
mkdir -p wordpress/wp-content/plugins/mailpoet-premium
mkdir -p fake_mail_output

for plugin in "mailpoet" "mailpoet-premium"; do
  docker-compose run --rm wordpress /bin/sh -c "
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
