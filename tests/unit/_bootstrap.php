<?php
$wordpress_path = getenv('WP_TEST_PATH');

if($wordpress_path) {
  if(file_exists($wordpress_path.'/wp-load.php')) {
    require_once(getenv('WP_TEST_PATH').'/wp-load.php');
  }
} else {
  throw new Exception("You need to specify the path to your WordPress installation\n`WP_TEST_PATH` in your .env file");
}

\MailPoet\Config\Env::init();
