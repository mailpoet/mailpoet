<?php

use MailPoet\Cache\TransientCache;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Features\FeaturesController;
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

if (!defined('MP_SETTINGS_TABLE')) {
  die('MailPoet must be activated in the tests site (usually http://localhost:8003) before running the integration tests');
}

$console = new \Codeception\Lib\Console\Output([]);
$console->writeln('Loading WP core... (' . $wpLoadFile . ')');

$console->writeln('Cleaning up database...');
$models = [
  'CustomField',
  'Form',
  'Newsletter',
  'NewsletterLink',
  'NewsletterLink',
  'NewsletterSegment',
  'NewsletterOption',
  'NewsletterOptionField',
  'Segment',
  'ScheduledTask',
  'ScheduledTaskSubscriber',
  'SendingQueue',
  'Subscriber',
  'SubscriberCustomField',
  'SubscriberSegment',
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

/**
 * @property IntegrationTester $tester
 */
abstract class MailPoetTest extends \Codeception\TestCase\Test { // phpcs:ignore
  private const BACKUP_GLOBALS_NAMES = [
    'wp_filter',
    'wp_actions',
    'wp_current_filter',
    '_SESSION',
    '_ENV',
    '_POST',
    '_GET',
    '_COOKIE',
    '_FILES',
    '_REQUEST',
    '_SERVER',
  ];

  protected $backupGlobals = false;
  protected $backupStaticAttributes = false;
  protected $runTestInSeparateProcess = false;
  protected $preserveGlobalState = false;

  protected static $savedGlobals;

  /** @var ContainerWrapper */
  protected $diContainer;

  /** @var Connection */
  protected $connection;

  /** @var EntityManager */
  protected $entityManager;

  public function setUp(): void {
    $this->diContainer = ContainerWrapper::getInstance(WP_DEBUG);
    $this->connection = $this->diContainer->get(Connection::class);
    $this->entityManager = $this->diContainer->get(EntityManager::class);
    $this->diContainer->get(FeaturesController::class)->resetCache();
    $this->diContainer->get(SettingsController::class)->resetCache();

    // Cleanup scheduled tasks from previous tests
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->entityManager->clear();
    $this->clearSubscribersCountCache();
    if (!self::$savedGlobals) {
      $this->backupGlobals();
    }
    parent::setUp();
  }

  public function tearDown(): void {
    $this->entityManager->clear();
    $this->restoreGlobals();
    wp_set_current_user(0);
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
    $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
    $connection->executeStatement("TRUNCATE $tableName");
    $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');
  }

  public function clearSubscribersCountCache() {
    $cache = $this->diContainer->get(TransientCache::class);
    $cache->invalidateItems(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY);
    $cache->invalidateItems(TransientCache::SUBSCRIBERS_GLOBAL_STATUS_STATISTICS_COUNT_KEY);
  }

  protected function backupGlobals(): void {
    self::$savedGlobals = [];
    foreach (self::BACKUP_GLOBALS_NAMES as $globalName) {
      foreach ($GLOBALS[$globalName] ?? [] as $key => $value) {
        self::$savedGlobals[$globalName][$key] = is_object($value) ? clone $value : $value;
      }
    }
  }

  protected function restoreGlobals(): void {
    if (empty(self::$savedGlobals)) {
      return;
    }

    foreach (self::BACKUP_GLOBALS_NAMES as $globalName) {
      $GLOBALS[$globalName] = [];
      foreach (self::$savedGlobals[$globalName] ?? [] as $key => $value) {
        $GLOBALS[$globalName][$key] = is_object($value) ? clone $value : $value;
      }
    }
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

    public function get($key) {
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
    public $session;

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

  class WC_Order { // phpcs:ignore
    public function get_billing_first_name() { // phpcs:ignore
    }

    public function get_billing_last_name() { // phpcs:ignore
    }

    public function get_billing_email() { // phpcs:ignore
    }

    public function get_id() { // phpcs:ignore
    }
  }

  class WC_Emails {} // phpcs:ignore
}

require_once '_fixtures.php';
if (!function_exists('get_woocommerce_currency')) {
  function get_woocommerce_currency() {
    return 'USD';
  }
}
