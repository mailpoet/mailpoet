<?php
namespace MailPoet\Config;

use MailPoet\Models;
use MailPoet\Cron\Supervisor;
use MailPoet\Router;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

class Initializer {
  function __construct($params = array(
    'file' => '',
    'version' => '1.0.0'
  )) {
    Env::init($params['file'], $params['version']);
  }

  function init() {
    $this->setupDB();

    register_activation_hook(Env::$file, array($this, 'runMigrator'));
    register_activation_hook(Env::$file, array($this, 'runPopulator'));

    add_action('plugins_loaded', array($this, 'setup'));
    add_action('init', array($this, 'onInit'));
    add_action('widgets_init', array($this, 'setupWidget'));
  }

  function setup() {
    try {
      $this->setupRenderer();
      $this->setupLocalizer();
      $this->setupMenu();
      $this->setupPermissions();
      $this->setupAnalytics();
      $this->setupChangelog();
      $this->setupShortcodes();
      $this->setupHooks();
      $this->setupImages();
      $this->runQueueSupervisor();
    } catch(\Exception $e) {
      // if anything goes wrong during init
      // automatically deactivate the plugin
      deactivate_plugins(Env::$file);
    }
  }

  function onInit() {
    $this->setupRouter();
    $this->setupPublicAPI();
    $this->setupPages();
  }

  function setupDB() {
    \ORM::configure(Env::$db_source_name);
    \ORM::configure('username', Env::$db_username);
    \ORM::configure('password', Env::$db_password);
    \ORM::configure('logging', WP_DEBUG);
    \ORM::configure('logger', function($query, $time) {
      // error_log("\n".$query."\n");
    });

    \ORM::configure('driver_options', array(
      \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
      \PDO::MYSQL_ATTR_INIT_COMMAND =>
        'SET TIME_ZONE = "' . Env::$db_timezone_offset. '"'
    ));

    $settings = Env::$db_prefix . 'settings';
    $segments = Env::$db_prefix . 'segments';
    $forms = Env::$db_prefix . 'forms';
    $custom_fields = Env::$db_prefix . 'custom_fields';
    $subscribers = Env::$db_prefix . 'subscribers';
    $subscriber_segment = Env::$db_prefix . 'subscriber_segment';
    $subscriber_custom_field = Env::$db_prefix . 'subscriber_custom_field';
    $newsletter_segment = Env::$db_prefix . 'newsletter_segment';
    $sending_queues = Env::$db_prefix . 'sending_queues';
    $newsletters = Env::$db_prefix . 'newsletters';
    $newsletter_templates = Env::$db_prefix . 'newsletter_templates';
    $newsletter_option_fields = Env::$db_prefix . 'newsletter_option_fields';
    $newsletter_option = Env::$db_prefix . 'newsletter_option';
    $newsletter_links = Env::$db_prefix . 'newsletter_links';
    $newsletter_posts = Env::$db_prefix . 'newsletter_posts';
    $statistics_newsletters = Env::$db_prefix . 'statistics_newsletters';
    $statistics_clicks = Env::$db_prefix . 'statistics_clicks';
    $statistics_opens = Env::$db_prefix . 'statistics_opens';
    $statistics_unsubscribes = Env::$db_prefix . 'statistics_unsubscribes';
    $statistics_forms = Env::$db_prefix . 'statistics_forms';

    define('MP_SETTINGS_TABLE', $settings);
    define('MP_SEGMENTS_TABLE', $segments);
    define('MP_FORMS_TABLE', $forms);
    define('MP_CUSTOM_FIELDS_TABLE', $custom_fields);
    define('MP_SUBSCRIBERS_TABLE', $subscribers);
    define('MP_SUBSCRIBER_SEGMENT_TABLE', $subscriber_segment);
    define('MP_SUBSCRIBER_CUSTOM_FIELD_TABLE', $subscriber_custom_field);
    define('MP_SENDING_QUEUES_TABLE', $sending_queues);
    define('MP_NEWSLETTERS_TABLE', $newsletters);
    define('MP_NEWSLETTER_TEMPLATES_TABLE', $newsletter_templates);
    define('MP_NEWSLETTER_SEGMENT_TABLE', $newsletter_segment);
    define('MP_NEWSLETTER_OPTION_FIELDS_TABLE', $newsletter_option_fields);
    define('MP_NEWSLETTER_LINKS_TABLE', $newsletter_links);
    define('MP_NEWSLETTER_POSTS_TABLE', $newsletter_posts);
    define('MP_NEWSLETTER_OPTION_TABLE', $newsletter_option);
    define('MP_STATISTICS_NEWSLETTERS_TABLE', $statistics_newsletters);
    define('MP_STATISTICS_CLICKS_TABLE', $statistics_clicks);
    define('MP_STATISTICS_OPENS_TABLE', $statistics_opens);
    define('MP_STATISTICS_UNSUBSCRIBES_TABLE', $statistics_unsubscribes);
    define('MP_STATISTICS_FORMS_TABLE', $statistics_forms);
  }

  function runMigrator() {
    $migrator = new Migrator();
    $migrator->up();
  }

  function runPopulator() {
    $populator = new Populator();
    $populator->up();
  }

  function setupRenderer() {
    $renderer = new Renderer();
    $this->renderer = $renderer->init();
  }

  function setupLocalizer() {
    $localizer = new Localizer($this->renderer);
    $localizer->init();
  }

  function setupMenu() {
    $menu = new Menu($this->renderer, Env::$assets_url);
    $menu->init();
  }

  function setupRouter() {
    $router = new Router\Router();
    $router->init();
  }

  function setupWidget() {
    $widget = new Widget($this->renderer);
    $widget->init();
  }

  function setupAnalytics() {
    $widget = new Analytics();
    $widget->init();
  }

  function setupPermissions() {
    $permissions = new Permissions();
    $permissions->init();
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

  function setupPublicAPI() {
    try {
      $publicAPI = new PublicAPI();
      $publicAPI->init();
    } catch(\Exception $e) {
      // continue execution
    }
  }

  function runQueueSupervisor() {
    if(php_sapi_name() === 'cli') return;
    try {
      $supervisor = new Supervisor();
      $supervisor->checkDaemon();
    } catch(\Exception $e) {
    }
  }

  function setupImages() {
    add_image_size('mailpoet_newsletter_max', 1320);
  }
}
