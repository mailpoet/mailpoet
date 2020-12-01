<?php

use MailPoet\DI\ContainerWrapper;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\EntityManager;

if ((boolean)getenv('MULTISITE') === true) {
  // REQUEST_URI needs to be set for WP to load the proper subsite where MailPoet is activated
  $_SERVER['REQUEST_URI'] = '/' . getenv('WP_TEST_MULTISITE_SLUG');
  $wpLoadFile = getenv('WP_ROOT_MULTISITE') . '/wp-load.php';
} else {
  $wpLoadFile = getenv('WP_ROOT') . '/wp-load.php';
}
require_once($wpLoadFile);

$console = new \Codeception\Lib\Console\Output([]);
$console->writeln('Loading WP core... (' . $wpLoadFile . ')');

$console->writeln('Cleaning up database...');
$models = [
  'CustomField',
  'Form',
  'Newsletter',
  'NewsletterLink',
  'NewsletterLink',
  'NewsletterPost',
  'NewsletterSegment',
  'NewsletterOption',
  'NewsletterOptionField',
  'Segment',
  'Log',
  'ScheduledTask',
  'ScheduledTaskSubscriber',
  'SendingQueue',
  'Subscriber',
  'SubscriberCustomField',
  'SubscriberSegment',
  'SubscriberIP',
  'StatisticsOpens',
  'StatisticsClicks',
  'StatisticsNewsletters',
  'StatisticsUnsubscribes',
];

$entities = [
  MailPoet\Entities\NewsletterTemplateEntity::class,
  MailPoet\Entities\SettingEntity::class,
];

$connection = ContainerWrapper::getInstance(WP_DEBUG)->get(Connection::class);
$destroy = function($model) use ($connection) {
  $modelName = '\MailPoet\Models\\' . $model;
  if (!class_exists($modelName)) {
    throw new \RuntimeException("Class $modelName doesn't exist.");
  }
  $class = new \ReflectionClass($modelName);
  $table = $class->getStaticPropertyValue('_table');
  $connection->executeUpdate("TRUNCATE $table");
};
array_map($destroy, $models);

$entityManager = ContainerWrapper::getInstance(WP_DEBUG)->get(EntityManager::class);
foreach ($entities as $entity) {
  $tableName = $entityManager->getClassMetadata($entity)->getTableName();
  $connection->query('SET FOREIGN_KEY_CHECKS=0');
  $connection->executeUpdate("TRUNCATE $tableName");
  $connection->query('SET FOREIGN_KEY_CHECKS=1');
}

// save plugin version to avoid running migrations (that cause $GLOBALS serialization errors)
$settings = SettingsController::getInstance();
$settings->set('db_version', \MailPoet\Config\Env::$version);

$cacheDir = '/tmp';
if (is_dir((string)getenv('WP_TEST_CACHE_PATH'))) {
  $cacheDir = getenv('WP_TEST_CACHE_PATH');
}

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
  'plugins_loaded',
  'rest_api_init',
  'admin_menu',
  'admin_notices',
  'activated_plugin',
  'activate_woocommerce-admin/woocommerce-admin.php',
  'deactivate_woocommerce-admin/woocommerce-admin.php',
  'deactivated_plugin',
  'woocommerce_admin_features',
];
foreach ($woocommerceBlacklistFilters as $woocommerceBlacklistFilter) {
  unset($GLOBALS['wp_filter'][$woocommerceBlacklistFilter]);
};

if (isset($GLOBALS['GLOBALS']['_wp_registered_theme_features']['post-formats']['show_in_rest'])) {
  unset($GLOBALS['GLOBALS']['_wp_registered_theme_features']['post-formats']['show_in_rest']);
}

/**
 * @property IntegrationTester $tester
 */
abstract class MailPoetTest extends \Codeception\TestCase\Test { // phpcs:ignore
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
  protected $diContainer;

  /** @var Connection */
  protected $connection;

  /** @var EntityManager */
  protected $entityManager;

  public function setUp() {
    $this->diContainer = ContainerWrapper::getInstance(WP_DEBUG);
    $this->connection = $this->diContainer->get(Connection::class);
    $this->entityManager = $this->diContainer->get(EntityManager::class);
    $this->diContainer->get(SettingsController::class)->resetCache();
    $this->entityManager->clear();
    parent::setUp();
  }

  public function tearDown() {
    $this->entityManager->clear();
    parent::tearDown();
  }

  /**
   * Call protected/private method of a class.
   *
   * @param object $object Instantiated object that we will run method on.
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

  public static function markTestSkipped(string $message = ''): void {
    $branchName = getenv('CIRCLE_BRANCH');
    if ($branchName === 'master' || $branchName === 'release') {
      self::fail('Cannot skip tests on this branch.');
    } else {
      parent::markTestSkipped($message);
    }
  }

  public function truncateEntity(string $entityName) {
    $classMetadata = $this->entityManager->getClassMetadata($entityName);
    $tableName = $classMetadata->getTableName();
    $connection = $this->entityManager->getConnection();
    $connection->query('SET FOREIGN_KEY_CHECKS=0');
    $connection->executeUpdate("TRUNCATE $tableName");
    $connection->query('SET FOREIGN_KEY_CHECKS=1');
  }
}

function asCallable($fn) {
  return function() use(&$fn) {
    return call_user_func_array($fn, func_get_args());
  };
}

// this is needed since it is not possible to mock __unset on non-existing class
// (PHPUnit creates empty __unset method without parameters which is a PHP error)
if (!class_exists(WC_Session::class)) {
  // phpcs:ignore
  class WC_Session {
    public function __unset($name) {
    }
  }
}

if (!function_exists('WC')) {
  class WC_Mailer { // phpcs:ignore
    public function email_header() { // phpcs:ignore
    }

    public function email_footer() { // phpcs:ignore
    }
  }
  class WooCommerce { // phpcs:ignore
    public function mailer() {
      return new WC_Mailer;
    }
  }

  function WC() {
    return new WooCommerce;
  }

  class WC_Order_Item_Product { // phpcs:ignore
    public function get_product_id() { // phpcs:ignore
    }
  }
}

require_once '_fixtures.php';
if (!function_exists('get_woocommerce_currency')) {
  function get_woocommerce_currency() {
    return 'USD';
  }
}
