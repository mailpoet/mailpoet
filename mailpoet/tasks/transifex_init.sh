#!/bin/bash -e

# Write ~/.transifexrc file if not exists
if [ ! -f ~/.transifexrc ]; then
  {
    echo "[https://www.transifex.com]"
    echo "hostname = https://www.transifex.com"
    echo "username = api"
    echo "password = ${WP_TRANSIFEX_API_TOKEN}"
    echo "token ="
  } > ~/.transifexrc
fi
