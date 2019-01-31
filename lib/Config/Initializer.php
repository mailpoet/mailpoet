<?php

namespace MailPoet\Config;

use MailPoet\API;
use MailPoet\Cron\CronTrigger;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Setting;
use MailPoet\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\ConflictResolver;
use MailPoet\Util\Helpers;
use MailPoet\Util\Notices\PermanentNotices;
use MailPoet\WP\Notice as WPNotice;
use MailPoetVendor\Psr\Container\ContainerInterface;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

class Initializer {
  private $access_control;
  private $renderer;
  /** @var ContainerInterface */
  private $container;

  const INITIALIZED = 'MAILPOET_INITIALIZED';

  function __construct($params = array(
    'file' => '',
    'version' => '1.0.0'
  )) {
    Env::init($params['file'], $params['version']);
  }

  function init() {
    $requirements_check_results = $this->checkRequirements();

    if(!$requirements_check_results[RequirementsChecker::TEST_PDO_EXTENSION] ||
      !$requirements_check_results[RequirementsChecker::TEST_VENDOR_SOURCE]
    ) {
      return;
    }

    // load translations
    $this->setupLocalizer();

    try {
      $this->setupDB();
    } catch(\Exception $e) {
      return WPNotice::displayError(Helpers::replaceLinkTags(
        __('Unable to connect to the database (the database is unable to open a file or folder), the connection is likely not configured correctly. Please read our [link] Knowledge Base article [/link] for steps how to resolve it.', 'mailpoet'),
        '//beta.docs.mailpoet.com/article/200-solving-database-connection-issues',
        array('target' => '_blank')
      ));
    }

    $this->loadContainer();

    // activation function
    register_activation_hook(
      Env::$file,
      array(
        $this,
        'runActivator'
      )
    );

    add_action('activated_plugin', array(
      new PluginActivatedHook(new DeferredAdminNotices),
      'action'
    ), 10, 2);

    add_action('init', array(
      $this,
      'preInitialize'
    ), 0);

    add_action('init', array(
      $this,
      'initialize'
    ));

    add_action('admin_init', array(
      $this,
      'setupPrivacyPolicy'
    ));

    add_action('wp_loaded', array(
      $this,
      'postInitialize'
    ));

    add_action('admin_init', array(
      new DeferredAdminNotices,
      'printAndClean'
    ));
  }

  function loadContainer() {
    $this->container = ContainerWrapper::getInstance(WP_DEBUG);
  }

  function checkRequirements() {
    $requirements = new RequirementsChecker();
    return $requirements->checkAllRequirements();
  }

  function runActivator() {
    $activator = $this->container->get(Activator::class);
    return $activator->activate();
  }

  function setupDB() {
    $database = new Database();
    $database->init();
  }

  function preInitialize() {
    try {
      $this->setupRenderer();
      $this->setupWidget();
    } catch(\Exception $e) {
      $this->handleFailedInitialization($e);
    }
  }

  function setupRenderer() {
    $this->renderer = $this->container->get(Renderer::class);
  }

  function setupWidget() {
    register_widget('\MailPoet\Form\Widget');
  }

  function initialize() {
    try {
      $this->setupAccessControl();

      $this->maybeDbUpdate();
      $this->setupInstaller();
      $this->setupUpdater();

      $this->setupCapabilities();
      $this->setupMenu();
      $this->setupShortcodes();
      $this->setupImages();
      $this->setupPersonalDataExporters();
      $this->setupPersonalDataErasers();

      $this->setupChangelog();
      $this->setupCronTrigger();
      $this->setupConflictResolver();

      $this->setupPages();

      $this->setupPermanentNotices();
      $this->setupDeactivationSurvey();

      do_action('mailpoet_initialized', MAILPOET_VERSION);
    } catch(\Exception $e) {
      return $this->handleFailedInitialization($e);
    }

    define(self::INITIALIZED, true);
  }

  function maybeDbUpdate() {
    try {
      $current_db_version = $this->container->get(SettingsController::class)->get('db_version');
    } catch(\Exception $e) {
      $current_db_version = null;
    }

    // if current db version and plugin version differ
    if(version_compare($current_db_version, Env::$version) !== 0) {
      $this->runActivator();
    }
  }

  function setupAccessControl() {
    $this->access_control = $this->container->get(AccessControl::class);
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
    if(empty($plugin_file) || !defined('MAILPOET_PREMIUM_VERSION')) {
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

  function setupMenu() {
    $menu = $this->container->get(Menu::class);
    $menu->init();
  }

  function setupShortcodes() {
    $shortcodes = new Shortcodes();
    $shortcodes->init();
  }

  function setupImages() {
    add_image_size('mailpoet_newsletter_max', Env::NEWSLETTER_CONTENT_WIDTH);
  }

  function setupChangelog() {
    $changelog = $this->container->get(Changelog::class);
    $changelog->init();
  }

  function setupCronTrigger() {
    // setup cron trigger only outside of cli environment
    if(php_sapi_name() !== 'cli') {
      $cron_trigger = $this->container->get(CronTrigger::class);
      $cron_trigger->init();
    }
  }

  function setupConflictResolver() {
    $conflict_resolver = new ConflictResolver();
    $conflict_resolver->init();
  }

  function postInitialize() {
    if(!defined(self::INITIALIZED)) return;
    try {
      $this->setupHooks();
      $this->setupJSONAPI();
      $this->setupRouter();
      $this->setupUserLocale();
    } catch(\Exception $e) {
      $this->handleFailedInitialization($e);
    }
  }

  function setupJSONAPI() {
    $this->container->get(API\JSON\API::class)->init();
  }

  function setupRouter() {
    $router = new Router\Router($this->access_control, $this->container);
    $router->init();
  }

  function setupUserLocale() {
    if(get_user_locale() === get_locale()) return;
    unload_textdomain(Env::$plugin_name);
    $localizer = new Localizer();
    $localizer->init();
  }

  function setupPages() {
    $pages = new \MailPoet\Settings\Pages();
    $pages->init();
  }

  function setupHooks() {
    $this->container->get(\MailPoet\Config\Hooks::class)->init();
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
    $notices = new PermanentNotices();
    $notices->init();
  }

  function handleFailedInitialization($exception) {
    // check if we are able to add pages at this point
    if(function_exists('wp_get_current_user')) {
      Menu::addErrorPage($this->access_control);
    }
    return WPNotice::displayError($exception);
  }

  function setupDeactivationSurvey() {
    $survey = new DeactivationSurvey($this->renderer);
    $survey->init();
  }
}
