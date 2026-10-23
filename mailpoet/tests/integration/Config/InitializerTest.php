<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use Codeception\Stub\Expected;
use MailPoet\Config\ActivationInProgressException;
use MailPoet\Config\Activator;
use MailPoet\Config\Env;
use MailPoet\Config\Initializer;
use MailPoet\Config\UpdateFailureNotifier;
use MailPoet\Entities\SettingEntity;
use MailPoet\InvalidStateException;
use MailPoet\Migrator\Migrator;
use MailPoet\Migrator\MigratorException;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class InitializerTest extends \MailPoetTest {
  private SettingsController $settings;

  /** @var string|null */
  private $originalRequestUri;

  /** @var array */
  private $originalGet;

  public function _before(): void {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    wp_set_current_user(1);
    $uri = $_SERVER['REQUEST_URI'] ?? null;
    $this->originalRequestUri = is_string($uri) ? $uri : null;
    $this->originalGet = $_GET;
  }

  public function _after(): void {
    $this->settings->set('db_version', Env::$version);
    delete_option(Initializer::PLUGIN_ACTIVATED);
    remove_filter('wp_doing_ajax', '__return_true');
    if (function_exists('set_current_screen')) {
      set_current_screen('front');
    }
    if ($this->originalRequestUri === null) {
      unset($_SERVER['REQUEST_URI']);
    } else {
      $_SERVER['REQUEST_URI'] = $this->originalRequestUri;
    }
    $_GET = $this->originalGet;
    parent::_after();
  }

  public function testItConfiguresHooks() {
    global $wp_filter; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $isHooked = false;
    // mailpoet should hook to 'wp_loaded' with priority of 10
    foreach ($wp_filter['wp_loaded'][10] as $name => $hook) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      if (preg_match('/postInitialize/', $name)) $isHooked = true;
    }
    verify($isHooked)->true();
  }

  public function testActivationFlagSurvivesAdminAjaxRequests() {
    if (!defined(Initializer::INITIALIZED)) define(Initializer::INITIALIZED, true);
    $initializer = $this->diContainer->get(Initializer::class);
    add_option(Initializer::PLUGIN_ACTIVATED, true);
    if (!function_exists('set_current_screen')) {
      require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
      require_once ABSPATH . 'wp-admin/includes/screen.php';
    }
    set_current_screen('plugins');

    $_SERVER['REQUEST_URI'] = '/wp-admin/admin-ajax.php';
    add_filter('wp_doing_ajax', '__return_true');
    $initializer->afterPluginActivation();
    verify(get_option(Initializer::PLUGIN_ACTIVATED))->notEmpty();

    remove_filter('wp_doing_ajax', '__return_true');
    $_SERVER['REQUEST_URI'] = '/wp-admin/index.php';
    $initializer->afterPluginActivation();
    verify(get_option(Initializer::PLUGIN_ACTIVATED))->empty();
  }

  public function testItDoesNotActivateWhenTheDatabaseIsUpToDate(): void {
    $this->settings->set('db_version', Env::$version);
    $initializer = $this->createInitializer(['activate' => Expected::never()]);
    $initializer->maybeRunActivator();
    verify($this->renderMailPoetNotices())->equals('');
  }

  public function testItWarnsWhileAnotherRequestHoldsTheActivationLock(): void {
    $this->settings->set('db_version', '0.0.1');
    $initializer = $this->createInitializer([
      'activate' => Expected::once(function () {
        throw new ActivationInProgressException('MailPoet version update is in progress, please refresh the page in a minute.');
      }),
    ]);

    $initializer->maybeRunActivator();

    $html = $this->renderMailPoetNotices();
    verify($html)->stringContainsString('notice-warning');
    verify($html)->stringContainsString('update is in progress');
  }

  public function testItStaysQuietWhenTheLockHolderFinishedMeanwhile(): void {
    $this->settings->set('db_version', '0.0.1');
    $this->settings->get('db_version'); // the settings cache now holds the old version
    $initializer = $this->createInitializer([
      'activate' => Expected::once(function () {
        // the lock holder completes from another process: the row changes, this process's
        // settings cache and Doctrine identity map do not
        $table = $this->entityManager->getClassMetadata(SettingEntity::class)->getTableName();
        $this->entityManager->getConnection()->executeStatement(
          "UPDATE $table SET value = :value WHERE name = 'db_version'",
          ['value' => Env::$version]
        );
        throw new ActivationInProgressException('MailPoet version update is in progress, please refresh the page in a minute.');
      }),
    ]);

    $initializer->maybeRunActivator();

    verify($this->renderMailPoetNotices())->equals('');
  }

  public function testItReportsARecordedFailureInsteadOfInProgressWhileTheLockIsHeld(): void {
    $this->settings->set('db_version', '0.0.1');
    $migrator = $this->makeEmpty(Migrator::class, ['getFailedMigrations' => [
      ['name' => 'Migration_1', 'status' => Migrator::MIGRATION_STATUS_FAILED, 'retries' => 0, 'error' => 'Allowed memory size of 134217728 bytes exhausted in /plugin/Migration_1.php:12', 'unknown' => false],
    ]]);
    $initializer = $this->getServiceWithOverrides(Initializer::class, [
      'activator' => $this->make(Activator::class, [
        'settings' => $this->settings,
        'activate' => Expected::once(function () {
          throw new ActivationInProgressException('MailPoet version update is in progress, please refresh the page in a minute.');
        }),
      ]),
      'updateFailureNotifier' => new UpdateFailureNotifier($migrator, $this->diContainer->get(WPFunctions::class)),
    ]);

    $initializer->maybeRunActivator();

    $html = $this->renderMailPoetNotices();
    verify($html)->stringContainsString('notice-error');
    verify($html)->stringContainsString('Allowed memory size');
    verify($html)->stringNotContainsString('update is in progress');
  }

  public function testItReportsAFailedMigrationAsAnError(): void {
    $this->settings->set('db_version', '0.0.1');
    $initializer = $this->createInitializer([
      'activate' => Expected::once(function () {
        throw MigratorException::migrationFailed('Migration_1', new \Exception('Unknown column'));
      }),
    ]);

    $initializer->maybeRunActivator();

    $html = $this->renderMailPoetNotices();
    verify($html)->stringContainsString('notice-error');
    verify($html)->stringContainsString('Unknown column');
    verify($html)->stringNotContainsString('update is in progress');
  }

  public function testOnlyTheLockExceptionCountsAsInProgress(): void {
    $this->settings->set('db_version', '0.0.1');
    $initializer = $this->createInitializer([
      'activate' => Expected::once(function () {
        throw InvalidStateException::create()->withMessage('No database table prefix was set.');
      }),
    ]);

    $initializer->maybeRunActivator();

    $html = $this->renderMailPoetNotices();
    verify($html)->stringContainsString('notice-error');
    verify($html)->stringContainsString('No database table prefix was set.');
  }

  public function testItTreatsAPhpErrorDuringActivationAsAFailure(): void {
    $this->settings->set('db_version', '0.0.1');
    $initializer = $this->createInitializer([
      'activate' => Expected::once(function () {
        throw new \TypeError('Argument 1 must be of type string');
      }),
    ]);

    $initializer->maybeRunActivator();

    $html = $this->renderMailPoetNotices();
    verify($html)->stringContainsString('notice-error');
    verify($html)->stringContainsString('Argument 1 must be of type string');
  }

  public function testOnlyAdministratorsSeeWhyInitializationFailed(): void {
    $initializer = $this->createInitializer([]);

    $html = $this->renderNotice($initializer->handleFailedInitialization(new \Exception('Unknown column wp_mailpoet_x.y')));
    verify($html)->stringContainsString('MailPoet could not start.');
    verify($html)->stringContainsString('Unknown column wp_mailpoet_x.y');
    verify($html)->stringContainsString('<summary>Full error</summary>');
    verify($html)->stringContainsString(esc_html(__FILE__));

    wp_set_current_user(0);
    $html = $this->renderNotice($initializer->handleFailedInitialization(new \Exception('Unknown column wp_mailpoet_x.y')));
    verify($html)->stringNotContainsString('Unknown column');
    verify($html)->stringContainsString('contact the site administrator');
  }

  public function testTheActivationHookTellsALockApartFromAFailure(): void {
    $lockedInitializer = $this->createInitializer([
      'activate' => Expected::once(function () {
        throw new ActivationInProgressException('MailPoet version update is in progress, please refresh the page in a minute.');
      }),
    ]);
    verify($this->renderNotice($lockedInitializer->runActivator()))->stringContainsString('notice-warning');

    $failingInitializer = $this->createInitializer([
      'activate' => Expected::once(function () {
        throw InvalidStateException::create()->withMessage('No database table prefix was set.');
      }),
    ]);
    verify($this->renderNotice($failingInitializer->runActivator()))->stringContainsString('notice-error');
  }

  /**
   * activate() is stubbed; the db_version reads stay real so the tests exercise them.
   */
  private function createInitializer(array $activatorStubs): Initializer {
    return $this->getServiceWithOverrides(Initializer::class, [
      'activator' => $this->make(Activator::class, $activatorStubs + ['settings' => $this->settings]),
    ]);
  }

  /**
   * @param mixed $notice
   */
  private function renderNotice($notice): string {
    $this->assertInstanceOf(Notice::class, $notice);
    ob_start();
    $notice->displayWPNotice();
    return (string)ob_get_clean();
  }

  /**
   * Renders only MailPoet's own notices; other plugins' admin_notices callbacks stay untouched.
   */
  private function renderMailPoetNotices(): string {
    $filters = $GLOBALS['wp_filter'];
    $this->assertIsArray($filters);
    $hook = $filters['admin_notices'] ?? null;
    if (!$hook instanceof \WP_Hook) {
      return '';
    }
    ob_start();
    foreach ($hook->callbacks as $callbacks) {
      foreach ($callbacks as $callback) {
        $function = $callback['function'];
        if (is_array($function) && ($function[0] ?? null) instanceof Notice && is_callable($function)) {
          $function();
        }
      }
    }
    return (string)ob_get_clean();
  }
}
