<?php declare(strict_types = 1);

use Codeception\Stub;
use MailPoet\Cache\TransientCache;
use MailPoet\Cron\CronTrigger;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Features\FeaturesController;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\Persistence\Mapping\ClassMetadata;

if ((boolean)getenv('MULTISITE') === true) {
  // REQUEST_URI needs to be set for WP to load the proper subsite where MailPoet is activated
  $_SERVER['REQUEST_URI'] = '/' . getenv('WP_TEST_MULTISITE_SLUG');
  $wpLoadFile = getenv('WP_ROOT_MULTISITE') . '/wp-load.php';
} else {
  $wpLoadFile = getenv('WP_ROOT') . '/wp-load.php';
}
require_once($wpLoadFile);

/**
 * Setting env from .evn file
 * Note that the following are override in the docker-compose file
 * WP_ROOT, WP_ROOT_MULTISITE, WP_TEST_MULTISITE_SLUG
 */
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../..');
$dotenv->load();

$console = new \Codeception\Lib\Console\Output([]);
$console->writeln('Loading WP core... (' . $wpLoadFile . ')');

$console->writeln('Cleaning up database...');

$connection = ContainerWrapper::getInstance(WP_DEBUG)->get(Connection::class);
$entityManager = ContainerWrapper::getInstance(WP_DEBUG)->get(EntityManager::class);
$entitiesMeta = $entityManager->getMetadataFactory()->getAllMetadata();
foreach ($entitiesMeta as $entityMeta) {
  $tableName = $entityMeta->getTableName();
  $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
  $connection->executeStatement("TRUNCATE $tableName");
  $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');
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
    'wp_rest_server',
    '_SESSION',
    '_ENV',
    '_POST',
    '_GET',
    '_COOKIE',
    '_FILES',
    '_REQUEST',
    '_SERVER',
    'HTTP_RAW_POST_DATA',
  ];

  protected $backupGlobals = false;
  protected $backupStaticAttributes = false;
  protected $runTestInSeparateProcess = false;
  protected $preserveGlobalState = false;

  protected static $savedGlobals;

  protected $tester;

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
    // switch cron to Linux method
    $this->diContainer->get(\MailPoet\Cron\DaemonActionSchedulerRunner::class)->deactivate();
    $this->diContainer->get(SettingsController::class)->set('cron_trigger.method', CronTrigger::METHOD_LINUX_CRON);
    // Reset caches
    $this->diContainer->get(FeaturesController::class)->resetCache();
    $this->diContainer->get(SettingsController::class)->resetCache();

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
    $this->cleanUpCustomEntities();
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

  /**
   * Retrieve a clone of a DI service with properties overridden by name, including
   * protected and private properties.
   *
   * @template T of object
   * @param class-string<T> $id
   * @param array<string, mixed> $overrides
   *  string = property name
   *  Object = replacement
   * @return T
   */
  public function getServiceWithOverrides(string $id, array $overrides) {
    $instance = $this->diContainer->get($id);
    return Stub::copy($instance, $overrides);
  }

  public function truncateEntity(string $entityName) {
    $classMetadata = $this->entityManager->getClassMetadata($entityName);
    $tableName = $classMetadata->getTableName();
    $connection = $this->entityManager->getConnection();
    $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
    $connection->executeStatement("TRUNCATE $tableName");
    $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');
  }

  /**
   * This is a helper function to update the updated_at column of an entity.
   * The updatedAt column is automatically updated in MailPoet\Doctrine\EventListeners\TimestampListener
   * so it is not possible to set it manually using a setter.
   */
  public function setUpdatedAtForEntity($entity, DateTimeInterface $updatedAt) {
    $className = (string)get_class($entity);
    $classMetadata = $this->entityManager->getClassMetadata($className);
    if (!$classMetadata instanceof ClassMetadata) {
      throw new \Exception("Entity $className not found");
    }
    $tableName = $classMetadata->getTableName();
    $connection = $this->entityManager->getConnection();
    $connection->executeQuery("
        UPDATE $tableName
        SET updated_at = '{$updatedAt->format('Y-m-d H:i:s')}'
        WHERE id = {$entity->getId()}
      ");
    $this->entityManager->refresh($entity);
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

  protected function cleanUpCustomEntities() {
    if (post_type_exists('product')) {
      unregister_post_type('product');
    }
  }
}

function asCallable($fn) {
  return function() use(&$fn) {
    return call_user_func_array($fn, func_get_args());
  };
}

require_once '_fixtures.php';
