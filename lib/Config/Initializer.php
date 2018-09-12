<?php

namespace MailPoet\Config;

use MailPoet\API;
use MailPoet\Cron\CronTrigger;
use MailPoet\Dependencies\Symfony\Component\DependencyInjection\Container;
use MailPoet\Models\Setting;
use MailPoet\Router;
use MailPoet\Settings\Pages;
use MailPoet\Util\ConflictResolver;
use MailPoet\Util\Helpers;
use MailPoet\Util\Notices\PermanentNotices;
use MailPoet\Util\Notices\PHPVersionWarnings;
use MailPoet\WP\Notice as WPNotice;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

class Initializer {
  /** @var Container */
  private $container;

  const INITIALIZED = 'MAILPOET_INITIALIZED';

  function __construct(Container $container, $params = array(
    'file' => '',
    'version' => '1.0.0'
  )) {
    $this->container = $container;
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

  function checkRequirements() {
    return $this->container->get(RequirementsChecker::class)->checkAllRequirements();
  }

  function runActivator() {
    return $this->container->get(Activator::class)->activate();
  }

  function setupDB() {
    $this->container->get(Database::class)->init();
  }

  function preInitialize() {
    try {
      $this->setupWidget();
    } catch(\Exception $e) {
      $this->handleFailedInitialization($e);
    }
  }

  function setupWidget() {
    register_widget('\MailPoet\Form\Widget');
  }

  function initialize() {
    try {
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
      $current_db_version = Setting::getValue('db_version');
    } catch(\Exception $e) {
      $current_db_version = null;
    }

    // if current db version and plugin version differ
    if(version_compare($current_db_version, Env::$version) !== 0) {
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
    $this->container->get(Localizer::class)->init();
  }

  function setupCapabilities() {
    $this->container->get(Capabilities::class)->init();
  }

  function setupMenu() {
    $this->container->get(Menu::class)->init();
  }

  function setupShortcodes() {
    $this->container->get(Shortcodes::class)->init();
  }

  function setupImages() {
    add_image_size('mailpoet_newsletter_max', Env::NEWSLETTER_CONTENT_WIDTH);
  }

  function setupChangelog() {
    $this->container->get(Changelog::class)->init();
  }

  function setupCronTrigger() {
    // setup cron trigger only outside of cli environment
    if(php_sapi_name() !== 'cli') {
      $this->container->get(CronTrigger::class)->init();
    }
  }

  function setupConflictResolver() {
    $this->container->get(ConflictResolver::class)->init();
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
    $this->container->get(Router\Router::class)->init();
  }

  function setupUserLocale() {
    if(get_user_locale() === get_locale()) return;
    unload_textdomain(Env::$plugin_name);
    $this->container->get(Localizer::class)->init();
  }

  function setupPages() {
    $this->container->get(Pages::class)->init();
  }

  function setupHooks() {
    $this->container->get(Hooks::class)->init();
  }

  function setupPrivacyPolicy() {
    $this->container->get(PrivacyPolicy::class)->init();
  }

  function setupPersonalDataExporters() {
    $this->container->get(PersonalDataExporters::class)->init();
  }

  function setupPersonalDataErasers() {
    $this->container->get(PersonalDataErasers::class)->init();
  }

  function setupPermanentNotices() {
    $notices = new PermanentNotices();
    $notices->init();
  }

  function setupPHPVersionWarnings() {
    $php_version_warnings =$this->container->get(PHPVersionWarnings::class);
    $php_version_warnings->init(phpversion(), Menu::isOnMailPoetAdminPage());
  }

  function handleFailedInitialization($exception) {
    // check if we are able to add pages at this point
    if(function_exists('wp_get_current_user')) {
      Menu::addErrorPage($this->container->get(AccessControl::class));
    }
    return WPNotice::displayError($exception);
  }

  function setupDeactivationSurvey() {
    $this->container->get(DeactivationSurvey::class)->init();
  }
}
