# MailPoet
The **MailPoet** plugin monorepo.

To use our Docker-based development environment (recommended), continue with the steps below.
If you'd like to use the plugin code directly, see details in [the plugin's readme](mailpoet/README.md).

## 🔌 Initial setup
1) Run `./do setup` to pull everything and install necessary dependencies.
2) Add secrets to `.env` files in `mailpoet` and `mailpoet-premium` directories. Go to the Secret Store and look for "MailPoet: plugin .env"
3) Run `./do start` to start the stack.
4) Go to http://localhost:8888 to see the dashboard of the dev environment.


## 🔍 PHPStorm setup for XDebug
In `Languages & Preferences > PHP > Servers` set path mappings:

```shell
wordpress        -> /var/www/html
mailpoet         -> /var/www/html/wp-content/plugins/mailpoet
mailpoet-premium -> /var/www/html/wp-content/plugins/mailpoet-premium
```

For PHP 8 and XDebug 3 we support **browser debugging extension**.
You can choose extension by your browser in [JetBrains documentation](https://www.jetbrains.com/help/phpstorm/browser-debugging-extensions.html).

To use XDebug inside the **cron**, you need to pass a URL argument `&XDEBUG_TRIGGER=yes`
[in the cron request](https://github.com/mailpoet/mailpoet/blob/bf7bd6d2d9090ed6ec7b8b575bb7d6b08e663a52/lib/Cron/CronHelper.php#L155-L166).
Alternatively, you can add `XDEBUG_TRIGGER: yes` to the `wordpress` service in `docker-compose.yml` and restart it (which will run XDebug also for all other requests).

## 💾 NFS volume sharing for Mac
NFS volumes can bring more stability and performance on Docker for Mac. To setup NFS volume sharing run:
```shell
sudo sh dev/mac-nfs-setup.sh
```

Then create a Docker Compose override file with NFS settings and restart containers:
```shell
cp docker-compose.override.macos-sample.yml docker-compose.override.yml

docker-compose down -v --remove-orphans
docker-compose up -d
```

## 🕹 Commands
The `./do` script define aliases for most of the commands you will need while working on plugins:

```shell
./do setup                           Setup the environment.
./do start                           Start the docker containers.
./do stop                            Stop the docker containers.
./do ssh [--test]                    Run an interactive bash shell inside the plugin directory.
./do run [--test] <command>          Run a custom bash command in the wordpress container.
./do acceptance [--premium]          Run acceptance tests.
./do build [--premium]               Builds a .zip for the plugin.
./do templates                       Generates templates classes and assets.
./do [--test] [--premium] <command>  Run './do <command>' inside the plugin directory.

Options:
   --test     Run the command using the 'test_wordpress' service.
   --premium  Run the command inside the premium plugin.
```

You can access this help in your command line running `./do` without parameters.

## ✉️ Adding new templates to the plugin
[Read the article.](https://mailpoet.atlassian.net/wiki/spaces/MAILPOET/pages/629374977/Adding+new+templates+to+the+plugin)

## 🚥 Testing with PHP 7.4 or PHP 8.1
To switch the environment to PHP 7.4/8.1:
1) Configure the `wordpress` service in `docker-compose.override.yml` to build from the php74 Dockerfile:

   ```yaml
   wordpress:
     build:
       context: .
       dockerfile: docker/php74/Dockerfile # OR docker/php81/Dockerfile
   ```
3) Run `docker-compose build wordpress`.
4) Start the stack with `./do start`.

To switch back to PHP 8 remove what was added in 1) and, run `docker-compose build wordpress` for application container and `docker-compose build test_wordpress` for tests container,
and start the stack using `./do start`.

## ✅ TODO
- install woo commerce, members and other useful plugins by default
