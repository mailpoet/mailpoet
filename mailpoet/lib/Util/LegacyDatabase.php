<?php declare(strict_types = 1);

namespace MailPoet\Util;

use MailPoet\Config\Env;

/**
 * These constants for table names were used with the idiorm ORM library that we removed.
 * They are kept here for sometime for back compatibility with extensions that may still use them.
 *
 * PHP doesn't have a built-in support for deprecation of constants defined with define() function.
 * But some IDEs like PHPStorm can recognize the @deprecated annotation and show warnings.
 *
 * We will remove them after January 2025.
 */
class LegacyDatabase {
  public static function defineTableConstants() {
    if (!defined('MP_SETTINGS_TABLE')) {
      /** @deprecated */
      define('MP_SETTINGS_TABLE', Env::$dbPrefix . 'settings');
      /** @deprecated */
      define('MP_SEGMENTS_TABLE', Env::$dbPrefix . 'segments');
      /** @deprecated */
      define('MP_FORMS_TABLE', Env::$dbPrefix . 'forms');
      /** @deprecated */
      define('MP_CUSTOM_FIELDS_TABLE', Env::$dbPrefix . 'custom_fields');
      /** @deprecated */
      define('MP_SUBSCRIBERS_TABLE', Env::$dbPrefix . 'subscribers');
      /** @deprecated */
      define('MP_SUBSCRIBER_SEGMENT_TABLE', Env::$dbPrefix . 'subscriber_segment');
      /** @deprecated */
      define('MP_SUBSCRIBER_CUSTOM_FIELD_TABLE', Env::$dbPrefix . 'subscriber_custom_field');
      /** @deprecated */
      define('MP_SUBSCRIBER_IPS_TABLE', Env::$dbPrefix . 'subscriber_ips');
      /** @deprecated */
      define('MP_NEWSLETTER_SEGMENT_TABLE', Env::$dbPrefix . 'newsletter_segment');
      /** @deprecated */
      define('MP_SCHEDULED_TASKS_TABLE', Env::$dbPrefix . 'scheduled_tasks');
      /** @deprecated */
      define('MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE', Env::$dbPrefix . 'scheduled_task_subscribers');
      /** @deprecated */
      define('MP_SENDING_QUEUES_TABLE', Env::$dbPrefix . 'sending_queues');
      /** @deprecated */
      define('MP_NEWSLETTERS_TABLE', Env::$dbPrefix . 'newsletters');
      /** @deprecated */
      define('MP_NEWSLETTER_TEMPLATES_TABLE', Env::$dbPrefix . 'newsletter_templates');
      /** @deprecated */
      define('MP_NEWSLETTER_OPTION_FIELDS_TABLE', Env::$dbPrefix . 'newsletter_option_fields');
      /** @deprecated */
      define('MP_NEWSLETTER_OPTION_TABLE', Env::$dbPrefix . 'newsletter_option');
      /** @deprecated */
      define('MP_NEWSLETTER_LINKS_TABLE', Env::$dbPrefix . 'newsletter_links');
      /** @deprecated */
      define('MP_NEWSLETTER_POSTS_TABLE', Env::$dbPrefix . 'newsletter_posts');
      /** @deprecated */
      define('MP_STATISTICS_NEWSLETTERS_TABLE', Env::$dbPrefix . 'statistics_newsletters');
      /** @deprecated */
      define('MP_STATISTICS_CLICKS_TABLE', Env::$dbPrefix . 'statistics_clicks');
      /** @deprecated */
      define('MP_STATISTICS_OPENS_TABLE', Env::$dbPrefix . 'statistics_opens');
      /** @deprecated */
      define('MP_STATISTICS_UNSUBSCRIBES_TABLE', Env::$dbPrefix . 'statistics_unsubscribes');
      /** @deprecated */
      define('MP_STATISTICS_FORMS_TABLE', Env::$dbPrefix . 'statistics_forms');
      /** @deprecated */
      define('MP_STATISTICS_WOOCOMMERCE_PURCHASES_TABLE', Env::$dbPrefix . 'statistics_woocommerce_purchases');
      /** @deprecated */
      define('MP_MAPPING_TO_EXTERNAL_ENTITIES_TABLE', Env::$dbPrefix . 'mapping_to_external_entities');
      /** @deprecated */
      define('MP_LOG_TABLE', Env::$dbPrefix . 'log');
      /** @deprecated */
      define('MP_STATS_NOTIFICATIONS_TABLE', Env::$dbPrefix . 'stats_notifications');
      /** @deprecated */
      define('MP_USER_FLAGS_TABLE', Env::$dbPrefix . 'user_flags');
      /** @deprecated */
      define('MP_FEATURE_FLAGS_TABLE', Env::$dbPrefix . 'feature_flags');
      /** @deprecated */
      define('MP_DYNAMIC_SEGMENTS_FILTERS_TABLE', Env::$dbPrefix . 'dynamic_segment_filters');
    }
  }
}
