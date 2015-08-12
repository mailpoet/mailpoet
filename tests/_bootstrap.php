<?php

use MailPoet\Config\Env;

$wordpress_path = getenv('WP_TEST_PATH');

if ($wordpress_path) {
  if (file_exists($wordpress_path . '/wp-load.php')) {
    require_once(getenv('WP_TEST_PATH') . '/wp-load.php');
  }
} else {
  throw new Exception("You need to specify the path to your WordPress installation\n`WP_TEST_PATH` in your .env file");
}

global $wpdb;

// clean database on each run
$truncate_commands = $wpdb->get_results("SELECT concat('TRUNCATE TABLE `', TABLE_NAME, '`;') as `truncate`
                              FROM INFORMATION_SCHEMA.TABLES
                              WHERE TABLE_SCHEMA = '" . Env::$db_name . "' AND TABLE_NAME LIKE '" . Env::$db_prefix . "%'");
foreach ($truncate_commands as $command) {
  $wpdb->query($command->truncate);
}

