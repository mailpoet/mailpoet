<?php

ini_set("display_errors", "1");
error_reporting(E_ALL);

if (!isset($argv[1]) || !$argv[1]) {
  echo 'You need to pass a WordPress root as an argument.';
  exit(1);
}

$wp_load_file = $argv[1] . '/wp-load.php';
if (!file_exists($wp_load_file)) {
  echo 'WordPress root argument is not valid.';
  exit(1);
}

if (!defined('ABSPATH')) {
  /** Set up WordPress environment */
  require_once($wp_load_file);
}

if (!is_plugin_active('mailpoet/mailpoet.php')) {
  echo 'MailPoet plugin is not active';
  exit(1);
}

// Check for minimum supported PHP version
if (version_compare(phpversion(), '5.6.0', '<')) {
  echo 'MailPoet requires PHP version 5.6 or newer (version 7.2 recommended).';
  exit(1);
}

if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
  set_time_limit(0);
}

$data = \MailPoet\Cron\CronHelper::createDaemon(null);
$container = \MailPoet\DI\ContainerWrapper::getInstance(WP_DEBUG);
$trigger = $container->get(\MailPoet\Cron\Daemon::class);
$trigger->run($data);
