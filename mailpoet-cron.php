<?php

ini_set("display_errors", "1");
error_reporting(E_ALL);

if(!isset($argv[1]) || !$argv[1]) {
  echo 'You need to pass a WordPress root as an argument.';
  die;
}

$wp_load_file = $argv[1] . '/wp-load.php';
if(!file_exists($wp_load_file)) {
  echo 'WordPress root argument is not valid.';
  die;
}

if(!defined('ABSPATH')) {
  /** Set up WordPress environment */
  require_once($wp_load_file);
}

$trigger = new \MailPoet\Cron\Triggers\MailPoet();
$trigger->run();
