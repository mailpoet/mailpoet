<?php

namespace MailPoet\Config;

use MailPoet\API\JSON\API;
use MailPoet\Cron\CronTrigger;
use MailPoet\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\ConflictResolver;
use MailPoet\Util\Helpers;
use MailPoet\Util\Notices\PermanentNotices;
use MailPoet\WP\Notice as WPNotice;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

class Initializer {

  /** @var AccessControl */
  private $access_control;

  /** @var Renderer */
  private $renderer;

  /** @var RendererFactory */
  private $renderer_factory;

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
  private $cron_trigger;

  /** @var PermanentNotices */
  private $permanent_notices;

  /** @var Shortcodes */
  private $shortcodes;

  /** @var DatabaseInitializer */
  private $database_initializer;

  /** @var Session */
  private $session;

  const INITIALIZED = 'MAILPOET_INITIALIZED';

  function __construct(
    RendererFactory $renderer_factory,
    AccessControl $access_control,
    API $api,
    Activator $activator,
    SettingsController $settings,
    Router\Router $router,
    Hooks $hooks,
    Changelog $changelog,
    Menu $menu,
    CronTrigger $cron_trigger,
    PermanentNotices $permanent_notices,
    Shortcodes $shortcodes,
    DatabaseInitializer $database_initializer,
    Session $session
  ) {
      $this->renderer_factory = $renderer_factory;
      $this->access_control = $access_control;
      $this->api = $api;
      $this->activator = $activator;
      $this->settings = $settings;
      $this->router = $router;
      $this->hooks = $hooks;
      $this->changelog = $changelog;
      $this->menu = $menu;
      $this->cron_trigger = $cron_trigger;
      $this->permanent_notices = $permanent_notices;
      $this->shortcodes = $shortcodes;
      $this->database_initializer = $database_initializer;
      $this->session = $session;
  }

  function init() {
    $requirements_check_results = $this->checkRequirements();

    if (!$requirements_check_results[RequirementsChecker::TEST_PDO_EXTENSION] ||
      !$requirements_check_results[RequirementsChecker::TEST_VENDOR_SOURCE]
    ) {
      return;
    }

    // load translations
    $this->setupLocalizer();

    try {
      $this->database_initializer->initializeConnection();
    } catch (\Exception $e) {
      return WPNotice::displayError(Helpers::replaceLinkTags(
        WPFunctions::get()->__('Unable to connect to the database (the database is unable to open a file or folder), the connection is likely not configured correctly. Please read our [link] Knowledge Base article [/link] for steps how to resolve it.', 'mailpoet'),
        'https://kb.mailpoet.com/article/200-solving-database-connection-issues',
        ['target' => '_blank']
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
  }

  function checkRequirements() {
    $requirements = new RequirementsChecker();
    return $requirements->checkAllRequirements();
  }

  function runActivator() {
    return $this->activator->activate();
  }

  function preInitialize() {
    try {
      $this->renderer = $this->renderer_factory->getRenderer();
      $this->setupWidget();
      $this->hooks->init();
    } catch (\Exception $e) {
      $this->handleFailedInitialization($e);
    }
  }

  function setupWidget() {
    WPFunctions::get()->registerWidget('\MailPoet\Form\Widget');
  }

  function initialize() {
    try {
      $this->setupSession();
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

      WPFunctions::get()->doAction('mailpoet_initialized', MAILPOET_VERSION);
    } catch (\Exception $e) {
      return $this->handleFailedInitialization($e);
    }

    define(self::INITIALIZED, true);
  }

  function setupSession() {
    $this->session->init();
  }

  function maybeDbUpdate() {
    try {
      $current_db_version = $this->settings->get('db_version');
    } catch (\Exception $e) {
      $current_db_version = null;
    }

    // if current db version and plugin version differ
    if (version_compare($current_db_version, Env::$version) !== 0) {
      $this->runActivator();
    }
  }

  function setupInstaller() {
    $installer = new Installer(
      Installer::PREMIUM_PLUGIN_SLUG
    );
    $installer->init();
  }

  function setupUpdater() {
    $slug = Installer::PREMIUM_PLUGIN_SLUG;
    $plugin_file = Installer::getPluginFile($slug);
    if (empty($plugin_file) || !defined('MAILPOET_PREMIUM_VERSION')) {
      return false;
    }
    $updater = new Updater(
      $plugin_file,
      $slug,
      MAILPOET_PREMIUM_VERSION
    );
    $updater->init();
  }

  function setupLocalizer() {
    $localizer = new Localizer();
    $localizer->init();
  }

  function setupCapabilities() {
    $caps = new Capabilities($this->renderer);
    $caps->init();
  }

  function setupShortcodes() {
    $this->shortcodes->init();
  }

  function setupImages() {
    WPFunctions::get()->addImageSize('mailpoet_newsletter_max', Env::NEWSLETTER_CONTENT_WIDTH);
  }

  function setupCronTrigger() {
    // setup cron trigger only outside of cli environment
    if (php_sapi_name() !== 'cli') {
      $this->cron_trigger->init();
    }
  }

  function setupConflictResolver() {
    $conflict_resolver = new ConflictResolver();
    $conflict_resolver->init();
  }

  function postInitialize() {
    if (!defined(self::INITIALIZED)) return;
    try {
      $this->api->init();
      $this->router->init();
      $this->setupUserLocale();
    } catch (\Exception $e) {
      $this->handleFailedInitialization($e);
    }
  }

  function setupUserLocale() {
    if (get_user_locale() === WPFunctions::get()->getLocale()) return;
    WPFunctions::get()->unloadTextdomain(Env::$plugin_name);
    $localizer = new Localizer();
    $localizer->init();
  }

  function setupPages() {
    $pages = new \MailPoet\Settings\Pages();
    $pages->init();
  }

  function setupPrivacyPolicy() {
    $privacy_policy = new PrivacyPolicy();
    $privacy_policy->init();
  }

  function setupPersonalDataExporters() {
    $exporters = new PersonalDataExporters();
    $exporters->init();
  }

  function setupPersonalDataErasers() {
    $erasers = new PersonalDataErasers();
    $erasers->init();
  }

  function setupPermanentNotices() {
    $this->permanent_notices->init();
  }

  function handleFailedInitialization($exception) {
    // check if we are able to add pages at this point
    if (function_exists('wp_get_current_user')) {
      Menu::addErrorPage($this->access_control);
    }
    return WPNotice::displayError($exception);
  }

  function setupDeactivationSurvey() {
    $survey = new DeactivationSurvey($this->renderer);
    $survey->init();
  }
}
