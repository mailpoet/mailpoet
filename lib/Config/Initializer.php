<?php

namespace MailPoet\Config;

use MailPoet\API\JSON\API;
use MailPoet\AutomaticEmails\AutomaticEmails;
use MailPoet\Cron\CronTrigger;
use MailPoet\InvalidStateException;
use MailPoet\PostEditorBlocks\PostEditorBlock;
use MailPoet\PostEditorBlocks\WooCommerceBlocksIntegration;
use MailPoet\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\ConflictResolver;
use MailPoet\Util\Helpers;
use MailPoet\Util\Notices\PermanentNotices;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\TransactionalEmailHooks as WCTransactionalEmails;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

class Initializer {
  /** @var AccessControl */
  private $accessControl;

  /** @var Renderer */
  private $renderer;

  /** @var RendererFactory */
  private $rendererFactory;

  /** @var API */
  private $api;

  /** @var Activator */
  private $activator;

  /** @var SettingsController */
  private $settings;

  /** @var Router\Router */
  private $router;

  /** @var Hooks */
  private $hooks;

  /** @var Changelog */
  private $changelog;

  /** @var Menu */
  private $menu;

  /** @var CronTrigger */
  private $cronTrigger;

  /** @var PermanentNotices */
  private $permanentNotices;

  /** @var Shortcodes */
  private $shortcodes;

  /** @var DatabaseInitializer */
  private $databaseInitializer;

  /** @var WCTransactionalEmails */
  private $wcTransactionalEmails;

  /** @var WooCommerceHelper */
  private $wcHelper;

  /** @var \MailPoet\PostEditorBlocks\PostEditorBlock */
  private $postEditorBlock;

  /** @var \MailPoet\PostEditorBlocks\WooCommerceBlocksIntegration */
  private $woocommerceBlocksIntegration;

  /** @var Localizer */
  private $localizer;

  /** @var AutomaticEmails */
  private $automaticEmails;

  /** @var AssetsLoader */
  private $assetsLoader;

  const INITIALIZED = 'MAILPOET_INITIALIZED';

  public function __construct(
    RendererFactory $rendererFactory,
    AccessControl $accessControl,
    API $api,
    Activator $activator,
    SettingsController $settings,
    Router\Router $router,
    Hooks $hooks,
    Changelog $changelog,
    Menu $menu,
    CronTrigger $cronTrigger,
    PermanentNotices $permanentNotices,
    Shortcodes $shortcodes,
    DatabaseInitializer $databaseInitializer,
    WCTransactionalEmails $wcTransactionalEmails,
    PostEditorBlock $postEditorBlock,
    WooCommerceBlocksIntegration $woocommerceBlocksIntegration,
    WooCommerceHelper $wcHelper,
    Localizer $localizer,
    AutomaticEmails $automaticEmails,
    AssetsLoader $assetsLoader
  ) {
    $this->rendererFactory = $rendererFactory;
    $this->accessControl = $accessControl;
    $this->api = $api;
    $this->activator = $activator;
    $this->settings = $settings;
    $this->router = $router;
    $this->hooks = $hooks;
    $this->changelog = $changelog;
    $this->menu = $menu;
    $this->cronTrigger = $cronTrigger;
    $this->permanentNotices = $permanentNotices;
    $this->shortcodes = $shortcodes;
    $this->databaseInitializer = $databaseInitializer;
    $this->wcTransactionalEmails = $wcTransactionalEmails;
    $this->wcHelper = $wcHelper;
    $this->postEditorBlock = $postEditorBlock;
    $this->woocommerceBlocksIntegration = $woocommerceBlocksIntegration;
    $this->localizer = $localizer;
    $this->automaticEmails = $automaticEmails;
    $this->assetsLoader = $assetsLoader;
  }

  public function init() {
    // load translations
    $this->setupLocalizer();

    try {
      $this->databaseInitializer->initializeConnection();
    } catch (\Exception $e) {
      return WPNotice::displayError(Helpers::replaceLinkTags(
        WPFunctions::get()->__('Unable to connect to the database (the database is unable to open a file or folder), the connection is likely not configured correctly. Please read our [link] Knowledge Base article [/link] for steps how to resolve it.', 'mailpoet'),
        'https://kb.mailpoet.com/article/200-solving-database-connection-issues',
        [
          'target' => '_blank',
          'data-beacon-article' => '596de7db2c7d3a73488b2f8d',
        ]
      ));
    }

    // activation function
    WPFunctions::get()->registerActivationHook(
      Env::$file,
      [
        $this,
        'runActivator',
      ]
    );

    WPFunctions::get()->addAction('activated_plugin', [
      new PluginActivatedHook(new DeferredAdminNotices),
      'action',
    ], 10, 2);

    WPFunctions::get()->addAction('init', [
      $this,
      'preInitialize',
    ], 0);

    WPFunctions::get()->addAction('init', [
      $this,
      'initialize',
    ]);

    WPFunctions::get()->addAction('admin_init', [
      $this,
      'setupPrivacyPolicy',
    ]);

    WPFunctions::get()->addAction('wp_loaded', [
      $this,
      'postInitialize',
    ]);

    WPFunctions::get()->addAction('admin_init', [
      new DeferredAdminNotices,
      'printAndClean',
    ]);

    WPFunctions::get()->addFilter('wpmu_drop_tables', [
      $this,
      'multisiteDropTables',
    ]);

    $this->hooks->initEarlyHooks();
  }

  public function runActivator() {
    try {
      $this->activator->activate();
    } catch (InvalidStateException $e) {
      return $this->handleRunningMigration($e);
    } catch (\Exception $e) {
      return $this->handleFailedInitialization($e);
    }
  }

  public function preInitialize() {
    try {
      $this->renderer = $this->rendererFactory->getRenderer();
      $this->setupWidget();
      $this->hooks->init();
      $this->setupWoocommerceTransactionalEmails();
      $this->assetsLoader->loadStyles();
    } catch (\Exception $e) {
      $this->handleFailedInitialization($e);
    }
  }

  public function setupWidget() {
    WPFunctions::get()->registerWidget('\MailPoet\Form\Widget');
  }

  public function initialize() {
    try {
      $this->maybeDbUpdate();
      $this->setupInstaller();
      $this->setupUpdater();

      $this->setupCapabilities();
      $this->menu->init();
      $this->setupShortcodes();
      $this->setupImages();
      $this->setupPersonalDataExporters();
      $this->setupPersonalDataErasers();

      $this->changelog->init();
      $this->setupCronTrigger();
      $this->setupConflictResolver();

      $this->setupPages();

      $this->setupPermanentNotices();
      $this->setupAutomaticEmails();
      $this->setupWoocommerceBlocksIntegration();
      $this->postEditorBlock->init();

      WPFunctions::get()->doAction('mailpoet_initialized', MAILPOET_VERSION);
    } catch (InvalidStateException $e) {
      return $this->handleRunningMigration($e);
    } catch (\Exception $e) {
      return $this->handleFailedInitialization($e);
    }

    define(self::INITIALIZED, true);
  }

  public function maybeDbUpdate() {
    try {
      $currentDbVersion = $this->settings->get('db_version');
    } catch (\Exception $e) {
      $currentDbVersion = null;
    }

    // if current db version and plugin version differ
    if (version_compare($currentDbVersion, Env::$version) !== 0) {
      $this->activator->activate();
    }
  }

  public function setupInstaller() {
    $installer = new Installer(
      Installer::PREMIUM_PLUGIN_SLUG
    );
    $installer->init();
  }

  public function setupUpdater() {
    $slug = Installer::PREMIUM_PLUGIN_SLUG;
    $pluginFile = Installer::getPluginFile($slug);
    if (empty($pluginFile) || !defined('MAILPOET_PREMIUM_VERSION')) {
      return false;
    }
    $updater = new Updater(
      $pluginFile,
      $slug,
      MAILPOET_PREMIUM_VERSION
    );
    $updater->init();
  }

  public function setupLocalizer() {
    $this->localizer->init();
  }

  public function setupCapabilities() {
    $caps = new Capabilities($this->renderer);
    $caps->init();
  }

  public function setupShortcodes() {
    $this->shortcodes->init();
  }

  public function setupImages() {
    WPFunctions::get()->addImageSize('mailpoet_newsletter_max', Env::NEWSLETTER_CONTENT_WIDTH);
  }

  public function setupCronTrigger() {
    // setup cron trigger only outside of cli environment
    if (php_sapi_name() !== 'cli') {
      $this->cronTrigger->init();
    }
  }

  public function setupConflictResolver() {
    $conflictResolver = new ConflictResolver();
    $conflictResolver->init();
  }

  public function postInitialize() {
    if (!defined(self::INITIALIZED)) return;
    try {
      $this->api->init();
      $this->router->init();
      $this->setupUserLocale();
    } catch (\Exception $e) {
      $this->handleFailedInitialization($e);
    }
  }

  public function setupUserLocale() {
    if (get_user_locale() === WPFunctions::get()->getLocale()) return;
    WPFunctions::get()->unloadTextdomain(Env::$pluginName);
    $this->localizer->init();
  }

  public function setupPages() {
    $pages = new \MailPoet\Settings\Pages();
    $pages->init();
  }

  public function setupPrivacyPolicy() {
    $privacyPolicy = new PrivacyPolicy();
    $privacyPolicy->init();
  }

  public function setupPersonalDataExporters() {
    $exporters = new PersonalDataExporters();
    $exporters->init();
  }

  public function setupPersonalDataErasers() {
    $erasers = new PersonalDataErasers();
    $erasers->init();
  }

  public function setupPermanentNotices() {
    $this->permanentNotices->init();
  }

  public function handleFailedInitialization($exception) {
    // check if we are able to add pages at this point
    if (function_exists('wp_get_current_user')) {
      Menu::addErrorPage($this->accessControl);
    }
    return WPNotice::displayError($exception);
  }

  private function handleRunningMigration(InvalidStateException $exception) {
    if (function_exists('wp_get_current_user')) {
      Menu::addErrorPage($this->accessControl);
    }
    return WPNotice::displayWarning($exception->getMessage());
  }

  public function setupAutomaticEmails() {
    $this->automaticEmails->init();
    $this->automaticEmails->getAutomaticEmails();
  }

  public function multisiteDropTables($tables) {
    global $wpdb;
    $tablePrefix = $wpdb->prefix . Env::$pluginPrefix;
    $mailpoetTables = $wpdb->get_col("SHOW TABLES LIKE '$tablePrefix%'");
    return array_merge($tables, $mailpoetTables);
  }

  private function setupWoocommerceTransactionalEmails() {
    $wcEnabled = $this->wcHelper->isWooCommerceActive();
    $optInEnabled = $this->settings->get('woocommerce.use_mailpoet_editor', false);
    if ($wcEnabled && $optInEnabled) {
      $this->wcTransactionalEmails->overrideStylesForWooEmails();
      $this->wcTransactionalEmails->useTemplateForWoocommerceEmails();
    }
  }

  private function setupWoocommerceBlocksIntegration() {
    $wcEnabled = $this->wcHelper->isWooCommerceActive();
    $wcBlocksEnabled = $this->wcHelper->isWooCommerceBlocksActive('6.3.0-dev');
    if ($wcEnabled && $wcBlocksEnabled) {
      $this->woocommerceBlocksIntegration->init();
    }
  }
}
