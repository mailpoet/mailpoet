<?php
namespace MailPoet\Config;

use MailPoet\API;
use MailPoet\Cron\CronTrigger;
use MailPoet\Router;
use MailPoet\Util\ConflictResolver;
use MailPoet\WP\Notice as WPNotice;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

class Initializer {

  protected $plugin_initialized = false;

  function __construct($params = array(
    'file' => '',
    'version' => '1.0.0'
  )) {
    Env::init($params['file'], $params['version']);
  }

  function init() {
    $requirements_check_results = $this->checkRequirements();

    // abort initialization if PDO extension is missing
    if(!$requirements_check_results[RequirementsChecker::TEST_PDO_EXTENSION] ||
      !$requirements_check_results[RequirementsChecker::TEST_VENDOR_SOURCE]
    ) {
      return;
    }

    $this->setupDB();

    // activation function
    register_activation_hook(
      Env::$file,
      array(
        'MailPoet\Config\Activator',
        'activate'
      )
    );

    add_action('plugins_loaded', array(
      $this,
      'setup'
    ));
    add_action('init', array(
      $this,
      'onInit'
    ));
    add_action('widgets_init', array(
      $this,
      'setupWidget'
    ));
    add_action('wp_loaded', array(
      $this,
      'setupHooks'
    ));
  }

  function checkRequirements() {
    $requrements = new RequirementsChecker();
    return $requrements->checkAllRequirements();
  }

  function setupDB() {
    $database = new Database();
    $database->init();
  }

  function setup() {
    try {
      $this->maybeDbUpdate();
      $this->setupRenderer();
      $this->setupInstaller();
      $this->setupLocalizer();
      $this->setupMenu();
      $this->setupAnalytics();
      $this->setupChangelog();
      $this->setupShortcodes();
      $this->setupImages();
      $this->setupCronTrigger();
      $this->setupConflictResolver();

      $this->plugin_initialized = true;
      do_action('mailpoet_initialized', MAILPOET_VERSION);
    } catch(\Exception $e) {
      $this->handleFailedInitialization($e);
    }
  }

  function onInit() {
    if(!$this->plugin_initialized) {
      define('MAILPOET_INITIALIZED', false);
      return;
    }

    try {
      $this->setupJSONAPI();
      $this->setupRouter();
      $this->setupPages();
    } catch(\Exception $e) {
      $this->handleFailedInitialization($e);
    }

    define('MAILPOET_INITIALIZED', true);
  }

  function maybeDbUpdate() {
    $current_db_version = get_option('mailpoet_db_version', false);

    // if current db version and plugin version differ
    if(version_compare($current_db_version, Env::$version) !== 0) {
      Activator::activate();
    }
  }

  function setupWidget() {
    if(!$this->plugin_initialized) {
      return;
    }

    try {
      $widget = new Widget($this->renderer);
      $widget->init();
    } catch(\Exception $e) {
      $this->handleFailedInitialization($e);
    }
  }

  function setupRenderer() {
    $caching = !WP_DEBUG;
    $debugging = WP_DEBUG;
    $this->renderer = new Renderer($caching, $debugging);
  }

  function setupInstaller() {
    $installer = new Installer(
      Installer::PREMIUM_PLUGIN_SLUG
    );
    $installer->init();
  }

  function setupLocalizer() {
    $localizer = new Localizer($this->renderer);
    $localizer->init();
  }

  function setupMenu() {
    $menu = new Menu($this->renderer, Env::$assets_url);
    $menu->init();
  }

  function setupAnalytics() {
    $analytics = new Analytics();
    $analytics->init();
  }

  function setupChangelog() {
    $changelog = new Changelog();
    $changelog->init();
  }

  function setupPages() {
    $pages = new \MailPoet\Settings\Pages();
    $pages->init();
  }

  function setupShortcodes() {
    $shortcodes = new Shortcodes();
    $shortcodes->init();
  }

  function setupHooks() {
    $hooks = new Hooks();
    $hooks->init();
  }

  function setupJSONAPI() {
    API\API::JSON()->init();
  }

  function setupRouter() {
    $router = new Router\Router();
    $router->init();
  }

  function setupCronTrigger() {
    // setup cron trigger only outside of cli environment
    if(php_sapi_name() !== 'cli') {
      $cron_trigger = new CronTrigger();
      $cron_trigger->init();
    }
  }

  function setupImages() {
    add_image_size('mailpoet_newsletter_max', 1320);
  }

  function setupConflictResolver() {
    $conflict_resolver = new ConflictResolver();
    $conflict_resolver->init();
  }

  function handleFailedInitialization($message) {
    return WPNotice::displayError($message);
  }
}