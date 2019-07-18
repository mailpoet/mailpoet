<?php

namespace MailPoet\Config;

use ORM;
use PDO;

if (!defined('ABSPATH')) exit;

class Database {
  function init(PDO $pdo) {
    ORM::setDb($pdo);
    $this->setupLogging();
    $this->defineTables();
  }

  function setupLogging() {
    ORM::configure('logging', WP_DEBUG);
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
      define('MP_STATISTICS_WOOCOMMERCE_PURCHASES_TABLE', Env::$db_prefix . 'statistics_woocommerce_purchases');
      define('MP_MAPPING_TO_EXTERNAL_ENTITIES_TABLE', Env::$db_prefix . 'mapping_to_external_entities');
      define('MP_LOG_TABLE', Env::$db_prefix . 'log');
      define('MP_STATS_NOTIFICATIONS_TABLE', Env::$db_prefix . 'stats_notifications');
      define('MP_USER_FLAGS_TABLE', Env::$db_prefix . 'user_flags');
      define('MP_FEATURE_FLAGS_TABLE', Env::$db_prefix . 'feature_flags');
    }
  }
}
