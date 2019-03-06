<?php
namespace MailPoet\Config;

use ORM as ORM;
use PDO as PDO;

if (!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

class Database {
  public $driver_option_wait_timeout = 60;

  function init() {
    $this->setupConnection();
    $this->setupLogging();
    $this->setupDriverOptions();
    $this->defineTables();
  }

  function setupConnection() {
    ORM::configure(Env::$db_source_name);
    ORM::configure('username', Env::$db_username);
    ORM::configure('password', Env::$db_password);
  }

  function setupLogging() {
    ORM::configure('logging', WP_DEBUG);
  }

  function setupDriverOptions() {
    $driver_options = array(
      'TIME_ZONE = "' . Env::$db_timezone_offset . '"',
      'sql_mode=(SELECT REPLACE(@@sql_mode,"ONLY_FULL_GROUP_BY",""))',
    );

    if (!empty(Env::$db_charset)) {
      $character_set = 'NAMES ' . Env::$db_charset;
      if (!empty(Env::$db_collation)) {
        $character_set .= ' COLLATE ' . Env::$db_collation;
      }
      $driver_options[] = $character_set;
    }

    ORM::configure('driver_options', array(
      PDO::MYSQL_ATTR_INIT_COMMAND => 'SET ' . implode(', ', $driver_options)
    ));

    try {
      $current_options = ORM::for_table("")
        ->raw_query('SELECT @@session.wait_timeout as wait_timeout')
        ->findOne();
      if ($current_options && (int)$current_options->wait_timeout < $this->driver_option_wait_timeout) {
        ORM::raw_execute('SET SESSION wait_timeout = ' . $this->driver_option_wait_timeout);
      }
    } catch (\PDOException $e) {
      // Rethrow PDOExceptions to prevent exposing sensitive data in stack traces
      throw new \Exception($e->getMessage());
    }
  }

  function defineTables() {
    if (!defined('MP_SETTINGS_TABLE')) {
      $tables = [
        'MP_SETTINGS_TABLE' => 'settings',
        'MP_SEGMENTS_TABLE' => 'segments',
        'MP_FORMS_TABLE' => 'forms',
        'MP_CUSTOM_FIELDS_TABLE' => 'custom_fields',
        'MP_SUBSCRIBERS_TABLE' => 'subscribers',
        'MP_SUBSCRIBER_SEGMENT_TABLE' => 'subscriber_segment',
        'MP_SUBSCRIBER_CUSTOM_FIELD_TABLE' => 'subscriber_custom_field',
        'MP_SUBSCRIBER_IPS_TABLE' => 'subscriber_ips',
        'MP_NEWSLETTER_SEGMENT_TABLE' => 'newsletter_segment',
        'MP_SCHEDULED_TASKS_TABLE' => 'scheduled_tasks',
        'MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE' => 'scheduled_task_subscribers',
        'MP_SENDING_QUEUES_TABLE' => 'sending_queues',
        'MP_NEWSLETTERS_TABLE' => 'newsletters',
        'MP_NEWSLETTER_TEMPLATES_TABLE' => 'newsletter_templates',
        'MP_NEWSLETTER_OPTION_FIELDS_TABLE' => 'newsletter_option_fields',
        'MP_NEWSLETTER_OPTION_TABLE' => 'newsletter_option',
        'MP_NEWSLETTER_LINKS_TABLE' => 'newsletter_links',
        'MP_NEWSLETTER_POSTS_TABLE' => 'newsletter_posts',
        'MP_STATISTICS_NEWSLETTERS_TABLE' => 'statistics_newsletters',
        'MP_STATISTICS_CLICKS_TABLE' => 'statistics_clicks',
        'MP_STATISTICS_OPENS_TABLE' => 'statistics_opens',
        'MP_STATISTICS_UNSUBSCRIBES_TABLE' => 'statistics_unsubscribes',
        'MP_STATISTICS_FORMS_TABLE' => 'statistics_forms',
        'MP_MAPPING_TO_EXTERNAL_ENTITIES_TABLE' => 'mapping_to_external_entities',
        'MP_LOG_TABLE' => 'log',
        'MP_STATS_NOTIFICATIONS_TABLE' => 'stats_notifications',
        'MP_USER_FLAGS_TABLE' => 'user_flags',
      ];
      foreach ($tables as $constant => $name) {
        define($constant, Env::$db_prefix . $name);
      }
    }
  }
}
