<?php

$console = new \Codeception\Lib\Console\Output([]);

$console->writeln('Loading WP core...');
$wordpress_path = getenv('WP_TEST_PATH');
if ($wordpress_path) {
  if (file_exists($wordpress_path . '/wp-load.php')) {
    require_once(getenv('WP_TEST_PATH') . '/wp-load.php');
  }
} else {
  throw new Exception("You need to specify the path to your WordPress installation\n`WP_TEST_PATH` in your .env file");
}

$console->writeln('Cleaning up database...');
$models = array(
    "Subscriber",
    "Setting"
);
$destroy = array_map(function ($model) {
  Model::factory("\MailPoet\Models\\" . $model)
       ->delete_many();
}, $models);
