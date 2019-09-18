<?php

use MailPoet\DI\ContainerWrapper;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\EntityManager;

if ((boolean)getenv('MULTISITE') === true) {
  // REQUEST_URI needs to be set for WP to load the proper subsite where MailPoet is activated
  $_SERVER['REQUEST_URI'] = '/' . getenv('WP_TEST_MULTISITE_SLUG');
  $wp_load_file = getenv('WP_ROOT_MULTISITE') . '/wp-load.php';
} else {
  $wp_load_file = getenv('WP_ROOT') . '/wp-load.php';
}
require_once($wp_load_file);

$console = new \Codeception\Lib\Console\Output([]);
$console->writeln('Loading WP core... (' . $wp_load_file . ')');

$console->writeln('Cleaning up database...');
$models = [
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
  'Log',
  'ScheduledTask',
  'ScheduledTaskSubscriber',
  'SendingQueue',
  'Setting',
  'Subscriber',
  'SubscriberCustomField',
  'SubscriberSegment',
  'SubscriberIP',
  'StatisticsOpens',
  'StatisticsClicks',
  'StatisticsNewsletters',
  'StatisticsUnsubscribes',
];

$connection = ContainerWrapper::getInstance(WP_DEBUG)->get(Connection::class);
$destroy = function($model) use ($connection) {
  $class = new \ReflectionClass('\MailPoet\Models\\' . $model);
  $table = $class->getStaticPropertyValue('_table');
  $connection->executeUpdate("TRUNCATE $table");
};
array_map($destroy, $models);

// save plugin version to avoid running migrations (that cause $GLOBALS serialization errors)
$settings = new \MailPoet\Settings\SettingsController();
$settings->set('db_version', \MailPoet\Config\Env::$version);

$cacheDir = '/tmp';
if (is_dir(getenv('WP_TEST_CACHE_PATH'))) {
  $cacheDir = getenv('WP_TEST_CACHE_PATH');
}

$console->writeln('Clearing AspectMock cache...');
exec('rm -rf ' . $cacheDir . '/_transformation.cache');

$console->writeln('Initializing AspectMock library...');
$kernel = \AspectMock\Kernel::getInstance();
$kernel->init(
  [
    'debug' => true,
    'appDir' => __DIR__ . '/../../',
    'cacheDir' => $cacheDir,
    'includePaths' => [__DIR__ . '/../../lib'],
  ]
);

// This hook throws an 'Undefined index: SERVER_NAME' error in CLI mode,
// the action is called in ConflictResolverTest
remove_filter('admin_print_styles', 'wp_resource_hints', 1);

// Unset filters, which woocommerce hooks onto and causes integration tests
// to fail, because some WC's functions can't be serialized
$woocommerceBlacklistFilters = [
  'init',
  'after_switch_theme',
  'after_setup_theme',
  'switch_blog',
  'shutdown',
];
foreach ($woocommerceBlacklistFilters as $woocommerceBlacklistFilter) {
  unset($GLOBALS['wp_filter'][$woocommerceBlacklistFilter]);
};

abstract class MailPoetTest extends \Codeception\TestCase\Test {
  protected $backupGlobals = true;
  protected $backupGlobalsBlacklist = [
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
    'menu',
    'woocommerce',
  ];
  protected $backupStaticAttributes = false;
  protected $runTestInSeparateProcess = false;
  protected $preserveGlobalState = false;
  protected $inIsolation = false;

  /** @var ContainerWrapper */
  protected $di_container;

  /** @var Connection */
  protected $connection;

  /** @var EntityManager */
  protected $entity_manager;

  function setUp() {
    $this->di_container = ContainerWrapper::getInstance(WP_DEBUG);
    $this->connection = $this->di_container->get(Connection::class);
    $this->entity_manager = $this->di_container->get(EntityManager::class);
    \MailPoet\Settings\SettingsController::resetCache();
    parent::setUp();
  }

  function tearDown() {
    $this->entity_manager->clear();
    parent::tearDown();
  }

  /**
   * Call protected/private method of a class.
   *
   * @param object &$object Instantiated object that we will run method on.
   * @param string $methodName Method name to call
   * @param array $parameters Array of parameters to pass into method.
   *
   * @return mixed Method return.
   */
  public function invokeMethod(&$object, $methodName, array $parameters = []) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
  }

  public static function markTestSkipped($message = '')
  {
    $branch_name = getenv('CIRCLE_BRANCH');
    if ($branch_name === 'master' || $branch_name === 'release') {
      self::fail('Cannot skip tests on this branch.');
    } else {
      parent::markTestSkipped($message);
    }
  }
}

function asCallable($fn) {
  return function() use(&$fn) {
    return call_user_func_array($fn, func_get_args());
  };
}

include '_fixtures.php';
