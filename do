#!/usr/bin/env bash

function syntax {
  cat << EOF
  ./do setup                           Setup the dev environment.
  ./do start                           Start the docker containers (docker-compose up -d).
  ./do stop                            Stop the docker containers (docker-compose stop).
  ./do ssh [--test]                    Run an interactive bash shell inside the plugin directory.
  ./do run [--test] <command>          Run a custom bash command in the wordpress container.
  ./do acceptance [--premium]          Run acceptance tests.
  ./do build [--premium]               Builds a .zip for the plugin.
  ./do templates                       Generates templates classes and assets.
  ./do [--test] [--premium] <command>  Run './do <command>' inside the plugin directory.

  Options:
     --test     Run the command using the 'test_wordpress' service.
     --premium  Run the command inside the premium plugin.
EOF
}

function ssh_and_run {
  params=("$@")
  params=("${params[@]:1}")
  docker-compose exec $1 bash -c "${params[@]}"
}

if [ "$1" = "" -o "$1" = "--help" ]; then
  syntax

elif [ "$1" = "setup" ]; then
  ./dev/initial-setup.sh

elif [ "$1" = "start" ]; then
  docker-compose up -d

elif [ "$1" = "stop" ]; then
  docker-compose stop

elif [ "$1" = "run" ]; then
  params=("$@")
  params=("${params[@]:1}")
  if [ "$2" = "--test" ]; then
    params=("${params[@]:1}")
    ssh_and_run test_wordpress "${params[@]}"
  else
    ssh_and_run wordpress "${params[@]}"
  fi

elif [ "$1" = "ssh" ]; then
  if [ "$2" = "--premium" ] || [ "$3" = "--premium" ]; then
    dir=/var/www/html/wp-content/plugins/mailpoet-premium
  else
    dir=/var/www/html/wp-content/plugins/mailpoet
  fi

  if [ "$2" = "--test" ] || [ "$3" = "--test" ]; then
    docker-compose exec --workdir $dir test_wordpress bash
  else
    docker-compose exec --workdir $dir wordpress bash
  fi

elif [ "$1" = "acceptance" ]; then
  if [ "$2" = "--premium" ]; then
    cd mailpoet-premium
  else
    cd mailpoet
  fi
  COMPOSE_HTTP_TIMEOUT=200 docker-compose run codeception_acceptance -e KEEP_DEPS=1 --steps --debug -vvv
  cd ..

elif [ "$1" = "build" ]; then
  if [ "$2" = "--premium" ]; then
    ssh_and_run wordpress "cd wp-content/plugins/mailpoet-premium && ./build.sh"
  else
    ssh_and_run wordpress "cd wp-content/plugins/mailpoet && ./build.sh"
  fi

elif [ "$1" = "templates" ]; then
  ssh_and_run wordpress "cd ../templates && php generate.php"

else
  docker_service="wordpress"
  plugin_directory="mailpoet"
  params=("$@")

  if [ "$1" = "--test" -o "$2" = "--test"  ]; then
    params=("${params[@]:1}")
    cd mailpoet
   ./do ${params[*]}
   exit 1
  fi
  if [ "$1" = "--premium" -o "$2" = "--premium" ]; then
    plugin_directory="mailpoet-premium"
    params=("${params[@]:1}")
  fi
  ssh_and_run $docker_service "cd wp-content/plugins/$plugin_directory && ./do ${params[*]}"
fi
