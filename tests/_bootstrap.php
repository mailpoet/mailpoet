<?php

$wp_load_file = getenv('WP_TEST_PATH').'/wp-load.php';
require_once($wp_load_file);

$console = new \Codeception\Lib\Console\Output([]);
$console->writeln('Loading WP core... ('.$wp_load_file.')');

$console->writeln('Cleaning up database...');
$models = array(
  'CustomField',
  'Form',
  'Newsletter',
  'NewsletterLink',
  'NewsletterPost',
  'NewsletterSegment',
  'NewsletterTemplate',
  'NewsletterOption',
  'NewsletterOptionField',
  'Segment',
  'SendingQueue',
  'Setting',
  'Subscriber',
  'SubscriberCustomField',
  'SubscriberSegment',
  'StatisticsOpens',
  'StatisticsClicks',
  'StatisticsNewsletters',
  'StatisticsUnsubscribes'
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

abstract class MailPoetTest extends \Codeception\TestCase\Test {
  protected $backupGlobals = true;
  protected $backupGlobalsBlacklist = array(
    'app',
    'post',
    'authordata',
    'currentday',
    'currentmonth',
    'page',
    'pages',
    'multipage',
    'more',
    'numpages',
    'is_iphone',
    'is_chrome',
    'is_safari',
    'is_NS4',
    'is_opera',
    'is_macIE',
    'is_winIE',
    'is_gecko',
    'is_lynx',
    'is_IE',
    'is_apache',
    'is_IIS',
    'is_iis7',
    'wp_version',
    'wp_db_version',
    'tinymce_version',
    'manifest_version',
    'required_php_version',
    'required_mysql_version',
    'super_admins',
    'wp_query',
    'wp_rewrite',
    'wp',
    'wpdb',
    'wp_locale',
    'wp_admin_bar',
    'wp_roles',
    'wp_meta_boxes',
    'wp_registered_sidebars',
    'wp_registered_widgets',
    'wp_registered_widget_controls',
    'wp_registered_widget_updates',
    'pagenow',
    'post_type',
    'allowedposttags',
    'allowedtags',
    'menu'
  );
  protected $backupStaticAttributes = false;
  protected $runTestInSeparateProcess = false;
  protected $preserveGlobalState = false;
  protected $inIsolation = false;
}