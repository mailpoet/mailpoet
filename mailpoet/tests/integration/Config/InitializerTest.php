<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use Codeception\Stub\Expected;
use MailPoet\Config\ActivationInProgressException;
use MailPoet\Config\Activator;
use MailPoet\Config\Env;
use MailPoet\Config\Hooks;
use MailPoet\Config\Initializer;
use MailPoet\Config\RendererFactory;
use MailPoet\Config\SchemaNotReadyResponder;
use MailPoet\Config\SchemaState;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor as MailpoetEmailEditorIntegration;
use MailPoet\Entities\SettingEntity;
use MailPoet\InvalidStateException;
use MailPoet\Migrator\Cli as MigratorCli;
use MailPoet\Migrator\MigratorException;
use MailPoet\Newsletter\Sharing\PublicEmailRoute;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ImportExport\Import\Cli as ImportCli;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;
use WP_REST_Request;

class InitializerTest extends \MailPoetTest {
  private SettingsController $settings;

  private SchemaState $schemaState;

  private SchemaNotReadyResponder $responder;

  public function _before(): void {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    // fresh instances: the container's shared SchemaState would carry a failure across tests
    $this->schemaState = new SchemaState($this->settings);
    $this->responder = new SchemaNotReadyResponder($this->schemaState, $this->diContainer->get(WPFunctions::class));
  }

  public function _after(): void {
    $this->settings->set('db_version', Env::$version);
    delete_transient(Activator::TRANSIENT_ACTIVATE_KEY);
    delete_option(Initializer::PLUGIN_ACTIVATED);
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

  public function testItWiresPluginsLoadedHooksWhenTheSchemaIsReady(): void {
    $initializer = $this->createInitializer([
      'hooks' => $this->makeEmpty(Hooks::class, ['init' => Expected::once()]),
      'publicEmailRoute' => $this->makeEmpty(PublicEmailRoute::class, ['init' => Expected::once()]),
    ]);
    $initializer->pluginsLoaded();
  }

  public function testItDefersPluginsLoadedHooksUntilThisRequestMigrates(): void {
    $this->settings->set('db_version', '0.0.1');
    $initializer = $this->createInitializer([
      'hooks' => $this->makeEmpty(Hooks::class, ['init' => Expected::once()]),
      'publicEmailRoute' => $this->makeEmpty(PublicEmailRoute::class, ['init' => Expected::once()]),
      'activator' => $this->makeEmpty(Activator::class, [
        'activate' => Expected::once(function () {
          $this->settings->set('db_version', Env::$version);
        }),
      ]),
    ]);
    $initializer->pluginsLoaded();
    $initializer->maybeRunActivator();
    verify($this->schemaState->getStatus())->equals(SchemaState::STATUS_READY);
    verify(has_action('wp_ajax_mailpoet', [$this->responder, 'sendJsonResponse']))->false();
  }

  public function testItStaysUnwiredWhileAnotherRequestHoldsTheActivationLock(): void {
    $this->settings->set('db_version', '0.0.1');
    $initializer = $this->createGatedInitializer([
      'activate' => Expected::once(function () {
        throw new ActivationInProgressException('MailPoet version update is in progress, please refresh the page in a minute.');
      }),
    ]);
    $initialized = did_action('mailpoet_initialized');

    $initializer->pluginsLoaded();
    $initializer->maybeRunActivator();
    $initializer->preInitialize();
    $initializer->initialize();
    $initializer->setupEmailEditorIntegrations();
    $initializer->setupMarketingConfirmationEmail();

    verify($this->schemaState->getStatus())->equals(SchemaState::STATUS_UPDATING);
    verify(did_action('mailpoet_initialized'))->equals($initialized);
    verify(has_action('wp_ajax_mailpoet', [$this->responder, 'sendJsonResponse']))->notEmpty();
    verify(has_action('wp_ajax_nopriv_mailpoet', [$this->responder, 'sendJsonResponse']))->notEmpty();
    verify(has_filter('rest_pre_dispatch', [$this->responder, 'rejectRestRequest']))->notEmpty();
  }

  public function testItContinuesWhenTheOtherRequestFinishedMigratingMeanwhile(): void {
    $this->settings->set('db_version', '0.0.1');
    $this->settings->get('db_version'); // the settings cache now holds the old version
    $initializer = $this->createInitializer([
      'hooks' => $this->makeEmpty(Hooks::class, ['init' => Expected::once()]),
      'publicEmailRoute' => $this->makeEmpty(PublicEmailRoute::class, ['init' => Expected::once()]),
      'activator' => $this->makeEmpty(Activator::class, [
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
      ]),
    ]);

    $initializer->maybeRunActivator();

    verify($this->schemaState->getStatus())->equals(SchemaState::STATUS_READY);
    verify(has_action('wp_ajax_mailpoet', [$this->responder, 'sendJsonResponse']))->false();
  }

  public function testItReportsAFailedMigrationAsAFailure(): void {
    $this->settings->set('db_version', '0.0.1');
    $initializer = $this->createGatedInitializer([
      'activate' => Expected::once(function () {
        throw MigratorException::migrationFailed('Migration_1', new \Exception('Unknown column'));
      }),
    ]);
    $initialized = did_action('mailpoet_initialized');

    $initializer->maybeRunActivator();
    $initializer->preInitialize();
    $initializer->initialize();
    $initializer->setupEmailEditorIntegrations();
    $initializer->setupMarketingConfirmationEmail();

    verify($this->schemaState->getStatus())->equals(SchemaState::STATUS_FAILED);
    verify($this->schemaState->getMessage())->stringContainsString('Unknown column');
    verify(did_action('mailpoet_initialized'))->equals($initialized);
    verify(has_action('wp_ajax_mailpoet', [$this->responder, 'sendJsonResponse']))->notEmpty();
  }

  public function testOnlyTheLockExceptionCountsAsInProgress(): void {
    $this->settings->set('db_version', '0.0.1');
    $initializer = $this->createGatedInitializer([
      'activate' => Expected::once(function () {
        throw InvalidStateException::create()->withMessage('No database table prefix was set.');
      }),
    ]);

    $initializer->maybeRunActivator();
    $initializer->initialize();

    verify($this->schemaState->getStatus())->equals(SchemaState::STATUS_FAILED);
    verify($this->schemaState->getMessage())->stringContainsString('No database table prefix was set.');
  }

  public function testItTreatsAPhpErrorInAMigrationAsAFailure(): void {
    $this->settings->set('db_version', '0.0.1');
    $initializer = $this->createGatedInitializer([
      'activate' => Expected::once(function () {
        throw new \TypeError('Argument 1 must be of type string');
      }),
    ]);

    $initializer->maybeRunActivator();
    $initializer->initialize();

    verify($this->schemaState->getStatus())->equals(SchemaState::STATUS_FAILED);
    verify($this->schemaState->getMessage())->stringContainsString('Argument 1 must be of type string');
  }

  public function testTheActivationHookTellsALockApartFromAFailure(): void {
    $lockedInitializer = $this->createInitializer([
      'activator' => $this->makeEmpty(Activator::class, [
        'activate' => Expected::once(function () {
          throw new ActivationInProgressException('MailPoet version update is in progress, please refresh the page in a minute.');
        }),
      ]),
    ]);
    verify($this->renderNotice($lockedInitializer->runActivator()))->stringContainsString('notice-warning');

    $failingInitializer = $this->createInitializer([
      'activator' => $this->makeEmpty(Activator::class, [
        'activate' => Expected::once(function () {
          throw InvalidStateException::create()->withMessage('No database table prefix was set.');
        }),
      ]),
    ]);
    verify($this->renderNotice($failingInitializer->runActivator()))->stringContainsString('notice-error');
  }

  public function testARequestDuringTheLockGetsA503FromTheRealWiring(): void {
    $this->settings->set('db_version', '0.0.1');
    set_transient(Activator::TRANSIENT_ACTIVATE_KEY, '1', 120);
    $initializer = $this->diContainer->get(Initializer::class);
    $initialized = did_action('mailpoet_initialized');

    $initializer->pluginsLoaded();
    $initializer->maybeRunActivator();
    $initializer->preInitialize();
    $initializer->initialize();
    $initializer->setupEmailEditorIntegrations();

    verify(did_action('mailpoet_initialized'))->equals($initialized);
    $response = rest_do_request(new WP_REST_Request('GET', '/mailpoet/v1/subscribers'));
    verify($response->get_status())->equals(503);
    $data = $response->get_data();
    $this->assertIsArray($data);
    verify($data['code'])->equals('mailpoet_update_in_progress');
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
   * An Initializer whose collaborators fail the test if a gated phase runs them.
   */
  private function createGatedInitializer(array $activatorStubs): Initializer {
    return $this->createInitializer([
      'wcHelper' => $this->makeEmpty(WooCommerceHelper::class, ['isWooCommerceActive' => Expected::never()]),
      'hooks' => $this->makeEmpty(Hooks::class, ['init' => Expected::never()]),
      'publicEmailRoute' => $this->makeEmpty(PublicEmailRoute::class, ['init' => Expected::never()]),
      'activator' => $this->makeEmpty(Activator::class, $activatorStubs),
      'migratorCli' => $this->makeEmpty(MigratorCli::class, ['initialize' => Expected::once()]),
      'importCli' => $this->makeEmpty(ImportCli::class, ['initialize' => Expected::never()]),
      'rendererFactory' => $this->makeEmpty(RendererFactory::class, ['getRenderer' => Expected::never()]),
      'mailpoetEmailEditorIntegration' => $this->makeEmpty(MailpoetEmailEditorIntegration::class, ['initialize' => Expected::never()]),
    ]);
  }

  private function createInitializer(array $overrides): Initializer {
    $overrides['schemaState'] = $this->schemaState;
    $overrides['schemaNotReadyResponder'] = $this->responder;
    return $this->getServiceWithOverrides(Initializer::class, $overrides);
  }
}
