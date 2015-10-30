<?php

$console = new \Codeception\Lib\Console\Output([]);

$console->writeln('Loading WP core...');
require_once(getenv('WP_TEST_PATH') . '/wp-load.php');

$console->writeln('Cleaning up database...');
$models = array(
  'CustomField',
  'Newsletter',
  'NewsletterSegment',
  'NewsletterTemplate',
  'Segment',
  'Setting',
  'Subscriber',
  'SubscriberCustomField',
  'SubscriberSegment'
);
$destroy = function ($model) {
  $class = new \ReflectionClass('\MailPoet\Models\\' . $model);
  $table = $class->getStaticPropertyValue('_table');
  $db = ORM::getDb();
  $db->beginTransaction();
  $db->exec('TRUNCATE '.$table);
  $db->commit();
};
array_map($destroy, $models);
