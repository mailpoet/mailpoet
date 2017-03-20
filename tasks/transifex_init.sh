#!/bin/bash

# Write ~/.transifexrc file if not exists
if [ ! -f ~/.transifexrc ]; then
  echo "[https://www.transifex.com]" > ~/.transifexrc
  echo "hostname = https://www.transifex.com" >> ~/.transifexrc
  echo "username = api" >> ~/.transifexrc
  echo "password = $WP_TRANSIFEX_API_TOKEN" >> ~/.transifexrc
  echo "token =" >> ~/.transifexrc
fi
