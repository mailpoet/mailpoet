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
      define('MP_SETTINGS_TABLE', Env::$db_prefix . 'settings');
      define('MP_SEGMENTS_TABLE', Env::$db_prefix . 'segments');
      define('MP_FORMS_TABLE', Env::$db_prefix . 'forms');
      define('MP_CUSTOM_FIELDS_TABLE', Env::$db_prefix . 'custom_fields');
      define('MP_SUBSCRIBERS_TABLE', Env::$db_prefix . 'subscribers');
      define('MP_SUBSCRIBER_SEGMENT_TABLE', Env::$db_prefix . 'subscriber_segment');
      define('MP_SUBSCRIBER_CUSTOM_FIELD_TABLE', Env::$db_prefix . 'subscriber_custom_field');
      define('MP_SUBSCRIBER_IPS_TABLE', Env::$db_prefix . 'subscriber_ips');
      define('MP_NEWSLETTER_SEGMENT_TABLE', Env::$db_prefix . 'newsletter_segment');
      define('MP_SCHEDULED_TASKS_TABLE', Env::$db_prefix . 'scheduled_tasks');
      define('MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE', Env::$db_prefix . 'scheduled_task_subscribers');
      define('MP_SENDING_QUEUES_TABLE', Env::$db_prefix . 'sending_queues');
      define('MP_NEWSLETTERS_TABLE', Env::$db_prefix . 'newsletters');
      define('MP_NEWSLETTER_TEMPLATES_TABLE', Env::$db_prefix . 'newsletter_templates');
      define('MP_NEWSLETTER_OPTION_FIELDS_TABLE', Env::$db_prefix . 'newsletter_option_fields');
      define('MP_NEWSLETTER_OPTION_TABLE', Env::$db_prefix . 'newsletter_option');
      define('MP_NEWSLETTER_LINKS_TABLE', Env::$db_prefix . 'newsletter_links');
      define('MP_NEWSLETTER_POSTS_TABLE', Env::$db_prefix . 'newsletter_posts');
      define('MP_STATISTICS_NEWSLETTERS_TABLE', Env::$db_prefix . 'statistics_newsletters');
      define('MP_STATISTICS_CLICKS_TABLE', Env::$db_prefix . 'statistics_clicks');
      define('MP_STATISTICS_OPENS_TABLE', Env::$db_prefix . 'statistics_opens');
      define('MP_STATISTICS_UNSUBSCRIBES_TABLE', Env::$db_prefix . 'statistics_unsubscribes');
      define('MP_STATISTICS_FORMS_TABLE', Env::$db_prefix . 'statistics_forms');
      define('MP_STATISTICS_WOOCOMMERCE_ORDERS_TABLE', Env::$db_prefix . 'statistics_woocommerce_orders');
      define('MP_MAPPING_TO_EXTERNAL_ENTITIES_TABLE', Env::$db_prefix . 'mapping_to_external_entities');
      define('MP_LOG_TABLE', Env::$db_prefix . 'log');
      define('MP_STATS_NOTIFICATIONS_TABLE', Env::$db_prefix . 'stats_notifications');
      define('MP_USER_FLAGS_TABLE', Env::$db_prefix . 'user_flags');
    }
  }
}
