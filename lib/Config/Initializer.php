<?php
namespace MailPoet\Config;

use MailPoet\Models;
use MailPoet\Cron\Supervisor;
use MailPoet\Router;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Initializer {
  function __construct($params = array(
    'file' => '',
    'version' => '1.0.0'
  )) {
    Env::init($params['file'], $params['version']);
  }

  function init() {
    $this->setupDB();
    $this->setupActivator();
    $this->setupRenderer();
    $this->setupLocalizer();
    $this->setupMenu();
    $this->setupRouter();
    $this->setupWidget();
    $this->setupAnalytics();
    $this->setupPermissions();
    $this->setupChangelog();
    $this->setupPublicAPI();
    $this->runQueueSupervisor();
    $this->setupShortcodes();
    $this->setupHooks();
    $this->setupImages();
  }

  function setupDB() {
    \ORM::configure(Env::$db_source_name);
    \ORM::configure('username', Env::$db_username);
    \ORM::configure('password', Env::$db_password);
    \ORM::configure('logging', WP_DEBUG);
    \ORM::configure('driver_options', array(
      \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
      \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET TIME_ZONE = "+00:00"'
    ));

    $subscribers = Env::$db_prefix . 'subscribers';
    $settings = Env::$db_prefix . 'settings';
    $newsletters = Env::$db_prefix . 'newsletters';
    $newsletter_templates = Env::$db_prefix . 'newsletter_templates';
    $segments = Env::$db_prefix . 'segments';
    $filters = Env::$db_prefix . 'filters';
    $segment_filter = Env::$db_prefix . 'segment_filter';
    $forms = Env::$db_prefix . 'forms';
    $subscriber_segment = Env::$db_prefix . 'subscriber_segment';
    $newsletter_segment = Env::$db_prefix . 'newsletter_segment';
    $custom_fields = Env::$db_prefix . 'custom_fields';
    $subscriber_custom_field = Env::$db_prefix . 'subscriber_custom_field';
    $newsletter_option_fields = Env::$db_prefix . 'newsletter_option_fields';
    $newsletter_option = Env::$db_prefix . 'newsletter_option';
    $sending_queues = Env::$db_prefix . 'sending_queues';
    $newsletter_statistics = Env::$db_prefix . 'newsletter_statistics';

    define('MP_SUBSCRIBERS_TABLE', $subscribers);
    define('MP_SETTINGS_TABLE', $settings);
    define('MP_NEWSLETTERS_TABLE', $newsletters);
    define('MP_SEGMENTS_TABLE', $segments);
    define('MP_FILTERS_TABLE', $filters);
    define('MP_SEGMENT_FILTER_TABLE', $segment_filter);
    define('MP_FORMS_TABLE', $forms);
    define('MP_SUBSCRIBER_SEGMENT_TABLE', $subscriber_segment);
    define('MP_NEWSLETTER_TEMPLATES_TABLE', $newsletter_templates);
    define('MP_NEWSLETTER_SEGMENT_TABLE', $newsletter_segment);
    define('MP_CUSTOM_FIELDS_TABLE', $custom_fields);
    define('MP_SUBSCRIBER_CUSTOM_FIELD_TABLE', $subscriber_custom_field);
    define('MP_NEWSLETTER_OPTION_FIELDS_TABLE', $newsletter_option_fields);
    define('MP_NEWSLETTER_OPTION_TABLE', $newsletter_option);
    define('MP_SENDING_QUEUE_TABLE', $sending_queues);
    define('MP_NEWSLETTER_STATISTICS_TABLE', $newsletter_statistics);
  }

  function setupActivator() {
    $activator = new Activator();
    $activator->init();
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
    $menu = new Menu(
      $this->renderer,
      Env::$assets_url
    );
    $menu->init();
  }

  function setupRouter() {
    $router = new Router\Router();
    $router->init();
  }

  function setupWidget() {
    $widget = new Widget();
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

  function setupShortcodes() {
    $shortcodes = new Shortcodes();
    $shortcodes->init();
  }
  function setupHooks() {
    $hooks = new Hooks();
    $hooks->init();
  }

  function setupPublicAPI() {
    $publicAPI = new PublicAPI();
    $publicAPI->init();
  }

  function runQueueSupervisor() {
    try {
      $supervisor = new Supervisor();
      $supervisor->checkDaemon();
    } catch (\Exception $e) {}
  }

  function setupImages() {
    add_image_size('mailpoet_newsletter_max', 1320);
  }
}
