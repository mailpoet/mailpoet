<?php

if (file_exists($dotenv = new Dotenv\Dotenv (__DIR__ . '/../..'))) {
  $dotenv->load();
}
