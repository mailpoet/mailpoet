<?php declare(strict_types = 1);

use Codeception\Stub;
use MailPoet\Automation\Integrations\WooCommerce\Fields\TermOptionsBuilder;
use MailPoet\Cache\TransientCache;
use MailPoet\Cron\CronTrigger;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\Persistence\Mapping\ClassMetadata;

// Load Composer autoload - we need to load it explicitly because we run the tests from the root of the project via /tests_env/vendor/bin/codecept
require_once(__DIR__ . '/../../vendor/autoload.php');
if ((bool)getenv('MULTISITE') === true) {
  // REQUEST_URI needs to be set for WP to load the proper subsite where MailPoet is activated
  $_SERVER['REQUEST_URI'] = '/' . getenv('WP_TEST_MULTISITE_SLUG');
  $wpLoadFile = getenv('WP_ROOT_MULTISITE') . '/wp-load.php';
} else {
  $wpLoadFile = getenv('WP_ROOT') . '/wp-load.php';
}
// Loading plugins can print PHP deprecations (e.g. compile-time ones from third-party plugins on new PHP versions).
// Sending them to stderr keeps them visible without flushing output, which would make header() calls in tests no-ops.
ob_start();
require_once($wpLoadFile);
$wpLoadOutput = (string)ob_get_clean();
if ($wpLoadOutput !== '') {
  fwrite(STDERR, $wpLoadOutput);
}
unset($wpLoadOutput);

/**
 * Setting env from .evn file
 * Note that the following are override in the docker-compose file
 * WP_ROOT, WP_ROOT_MULTISITE, WP_TEST_MULTISITE_SLUG
 */
$dotenv = \Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../..');
$dotenv->load();

$console = new \Codeception\Lib\Console\Output([]);
$console->writeln('Loading WP core... (' . $wpLoadFile . ')');

// Every user or customer a test creates hashes a password. bcrypt at WordPress's default cost
// takes tens of milliseconds, the minimum cost under a millisecond; verification still works.
add_filter('wp_hash_password_options', function ($options, $algorithm) {
  return $algorithm === PASSWORD_BCRYPT ? ['cost' => 4] : $options;
}, 10, 2);

$console->writeln('Cleaning up database...');

$connection = ContainerWrapper::getInstance(WP_DEBUG)->get(Connection::class);
$entityManager = ContainerWrapper::getInstance(WP_DEBUG)->get(EntityManager::class);
$entitiesMeta = $entityManager->getMetadataFactory()->getAllMetadata();
foreach ($entitiesMeta as $entityMeta) {
  if ($entityMeta->getName() === NewsletterOptionFieldEntity::class) {
    continue;
  }
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
    // Carbon datetime
    Carbon::setTestNow();
    // Reset WPFunctions
    WPFunctions::set(new WPFunctions());
    // switch cron to Linux method
    $this->diContainer->get(\MailPoet\Cron\DaemonActionSchedulerRunner::class)->deactivate();
    $this->diContainer->get(SettingsController::class)->set('cron_trigger.method', CronTrigger::METHOD_LINUX_CRON);
    // Reset caches
    $this->diContainer->get(FeaturesController::class)->resetCache();
    $this->diContainer->get(SettingsController::class)->resetCache();
    $this->diContainer->get(TermOptionsBuilder::class)->resetCache();

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
    if (PHP_VERSION_ID < 80100) {
      $method->setAccessible(true);
    }

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
    /** @var T $copy */
    $copy = Stub::copy($instance, $overrides);
    return $copy;
  }

  /**
   * @param class-string $entityName
   */
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
    /** @var class-string $className */
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
    $cache->invalidateAllItems();
  }

  protected function backupGlobals(): void {
    self::$savedGlobals = [];
    foreach (self::BACKUP_GLOBALS_NAMES as $globalName) {
      $current = $GLOBALS[$globalName] ?? [];
      if (!is_array($current)) {
        continue;
      }
      foreach ($current as $key => $value) {
        self::$savedGlobals[$globalName][$key] = is_object($value) ? clone $value : $value;
      }
    }
  }

  protected function restoreGlobals(): void {
    if (empty(self::$savedGlobals)) {
      return;
    }

    foreach (self::BACKUP_GLOBALS_NAMES as $globalName) {
      $restored = [];
      foreach (self::$savedGlobals[$globalName] ?? [] as $key => $value) {
        $restored[$key] = is_object($value) ? clone $value : $value;
      }
      $GLOBALS[$globalName] = $restored;
    }
  }

  protected function checkValidHTML(string $html): void {
    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);

    // Check for errors during parsing
    $errors = libxml_get_errors();
    libxml_clear_errors();

    $this->assertEmpty($errors, 'HTML is not valid: ' . $html);
  }

  /**
   * Checks that the HTML has a tag with these attribute values, in any order and quoting.
   * WordPress 7.2 re-serializes markup that passes through wp_kses().
   *
   * @param array<string, string|true> $attributes
   */
  protected function assertHtmlHasTag(string $html, string $tagName, array $attributes): void {
    $processor = new \WP_HTML_Tag_Processor($html);
    while ($processor->next_tag($tagName)) {
      $matches = true;
      foreach ($attributes as $name => $value) {
        if ($processor->get_attribute($name) !== $value) {
          $matches = false;
          break;
        }
      }
      if ($matches) {
        $this->addToAssertionCount(1);
        return;
      }
    }
    $this->fail("No <$tagName> tag with attributes " . (string)json_encode($attributes) . ' in: ' . $html);
  }

  /**
   * Compares HTML by meaning: the same tags, attribute values and text, regardless of attribute
   * order, quoting, void-element slashes or how characters in text are encoded.
   */
  protected function assertSameHtml(string $expected, string $actual): void {
    $this->assertSame($this->normalizeHtml($expected), $this->normalizeHtml($actual), 'Actual HTML: ' . $actual);
  }

  private function normalizeHtml(string $html): string {
    $processor = new \WP_HTML_Tag_Processor($html);
    $normalized = '';
    while ($processor->next_token()) {
      $type = $processor->get_token_type();
      if ($type === '#text') {
        $normalized .= htmlspecialchars($processor->get_modifiable_text(), ENT_NOQUOTES);
        continue;
      }
      if ($type !== '#tag') {
        $normalized .= "<!--$type:" . $processor->get_modifiable_text() . '-->';
        continue;
      }
      $tagName = strtolower((string)$processor->get_tag());
      if ($processor->is_tag_closer()) {
        $normalized .= "</$tagName>";
        continue;
      }
      $attributeNames = $processor->get_attribute_names_with_prefix('') ?? [];
      sort($attributeNames);
      $normalized .= "<$tagName";
      foreach ($attributeNames as $name) {
        $value = $processor->get_attribute($name);
        $normalized .= $value === true ? " $name" : " $name=\"" . htmlspecialchars((string)$value, ENT_QUOTES) . '"';
      }
      $normalized .= '>' . htmlspecialchars($processor->get_modifiable_text(), ENT_NOQUOTES);
    }
    $this->assertFalse($processor->paused_at_incomplete_token(), 'Incomplete HTML: ' . $html);
    return $normalized;
  }

  public function _after() {
    parent::_after();
    $this->tester->cleanup();
    // Reset any overrides
    Carbon::setTestNow();
  }
}

function asCallable($fn) {
  return function() use(&$fn) {
    return call_user_func_array($fn, func_get_args());
  };
}

require_once '_fixtures.php';
