<?php

namespace MailPoet\Config;

use MailPoet\API\JSON\API;
use MailPoet\AutomaticEmails\AutomaticEmails;
use MailPoet\Cron\CronTrigger;
use MailPoet\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\ConflictResolver;
use MailPoet\Util\Helpers;
use MailPoet\Util\Notices\PermanentNotices;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\TransactionalEmails as WCTransactionalEmails;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

class Initializer {
  public $automaticEmails;

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

  /** @var PostEditorBlock */
  private $postEditorBlock;

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
    WooCommerceHelper $wcHelper
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
    $this->hooks->initEarlyHooks();
  }

  public function runActivator() {
    return $this->activator->activate();
  }

  public function preInitialize() {
    try {
      $this->renderer = $this->rendererFactory->getRenderer();
      $this->setupWidget();
      $this->hooks->init();
      $this->setupWoocommerceTransactionalEmails();
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
      $this->setupDeactivationSurvey();
      $this->setupAutomaticEmails();
      $this->postEditorBlock->init();

      WPFunctions::get()->doAction('mailpoet_initialized', MAILPOET_VERSION);
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
      $this->runActivator();
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
    $localizer = new Localizer();
    $localizer->init();
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
    $localizer = new Localizer();
    $localizer->init();
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

  public function setupDeactivationSurvey() {
    $survey = new DeactivationSurvey($this->renderer);
    $survey->init();
  }

  public function setupAutomaticEmails() {
    $automaticEmails = new AutomaticEmails();
    $automaticEmails->init();
    $this->automaticEmails = $automaticEmails->getAutomaticEmails();

    WPFunctions::get()->addAction(
      'mailpoet_newsletters_translations_after',
      [$this, 'includeAutomaticEmailsData']
    );

    WPFunctions::get()->addAction(
      'mailpoet_newsletter_editor_after_javascript',
      [$this, 'includeAutomaticEmailsData']
    );
  }

  private function setupWoocommerceTransactionalEmails() {
    $wcEnabled = $this->wcHelper->isWooCommerceActive();
    $optInEnabled = $this->settings->get('woocommerce.use_mailpoet_editor', false);
    if ($wcEnabled) {
      $this->wcTransactionalEmails->enableEmailSettingsSyncToWooCommerce();
      if ($optInEnabled) {
        $this->wcTransactionalEmails->useTemplateForWoocommerceEmails();
      }
    }
  }

  public function includeAutomaticEmailsData() {
    $data = [
      'automatic_emails' => $this->automaticEmails,
      'woocommerce_optin_on_checkout' => $this->settings->get('woocommerce.optin_on_checkout.enabled', false),
    ];

    echo $this->renderer->render('automatic_emails.html', $data);
  }
}
