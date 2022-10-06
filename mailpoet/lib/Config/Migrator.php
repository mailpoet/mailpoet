<?php

namespace MailPoet\Config;

use MailPoet\Cron\CronTrigger;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceSubscription;
use MailPoet\Settings\SettingsChangeHandler;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;

// The "created_at" column must be NULL in some tables to avoid "there can be only one
// TIMESTAMP column with CURRENT_TIMESTAMP" error on MySQL version < 5.6.5 that occurs
// even when other timestamp is simply "NOT NULL".
class Migrator {

  public $prefix;
  private $charsetCollate;
  private $models;

  /** @var SettingsController */
  private $settings;

  /** @var SettingsChangeHandler */
  private $settingsChangeHandler;

  public function __construct(
    SettingsController $settings,
    SettingsChangeHandler $settingsChangeHandler
  ) {
    $this->settings = $settings;
    $this->settingsChangeHandler = $settingsChangeHandler;
    $this->prefix = Env::$dbPrefix;
    $this->charsetCollate = Env::$dbCharsetCollate;
    $this->models = [
      'segments',
      'settings',
      'custom_fields',
      'scheduled_tasks',
      'stats_notifications',
      'scheduled_task_subscribers',
      'sending_queues',
      'subscribers',
      'subscriber_segment',
      'subscriber_custom_field',
      'subscriber_ips',
      'newsletters',
      'newsletter_templates',
      'newsletter_option_fields',
      'newsletter_option',
      'newsletter_segment',
      'newsletter_links',
      'newsletter_posts',
      'forms',
      'statistics_newsletters',
      'statistics_clicks',
      'statistics_bounces',
      'statistics_opens',
      'statistics_unsubscribes',
      'statistics_forms',
      'statistics_woocommerce_purchases',
      'log',
      'user_flags',
      'feature_flags',
      'dynamic_segment_filters',
      'user_agents',
      'tags',
      'subscriber_tag',
    ];
  }

  public function up() {
    global $wpdb;
    // Ensure dbDelta function
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $output = [];
    foreach ($this->models as $model) {
      $modelMethod = Helpers::underscoreToCamelCase($model);
      $output = array_merge(dbDelta($this->$modelMethod()), $output);
    }
    $this->updateNullInUnsubscribeStats();
    $this->fixScheduledTasksSubscribersTimestampColumns();
    $this->removeDeprecatedStatisticsIndexes();
    $this->migrateSerializedFilterDataToNewColumns();
    $this->migratePurchasedProductDynamicFilters();
    $this->migrateWooSubscriptionsDynamicFilters();
    $this->migratePurchasedInCategoryDynamicFilters();
    $this->migrateEmailActionsFilters();
    $this->updateDefaultInactiveSubscriberTimeRange();
    $this->setDefaultValueForLoadingThirdPartyLibrariesForExistingInstalls();
    $this->disableMailPoetCronTrigger();
    return $output;
  }

  public function down() {
    global $wpdb;

    $_this = $this;
    $dropTable = function($model) use($wpdb, $_this) {
      $table = esc_sql($_this->prefix . $model);
      $wpdb->query("DROP TABLE {$table}");
    };

    array_map($dropTable, $this->models);
  }

  public function segments() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'type varchar(90) NOT NULL DEFAULT "default",',
      'description varchar(250) NOT NULL DEFAULT "",',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at timestamp NULL,',
      'average_engagement_score FLOAT unsigned NULL,',
      'average_engagement_score_updated_at timestamp NULL,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name (name),',
      'KEY average_engagement_score_updated_at (average_engagement_score_updated_at)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function settings() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'name varchar(50) NOT NULL,',
      'value longtext,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name (name)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function customFields() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'type varchar(90) NOT NULL,',
      'params longtext NOT NULL,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name (name)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function scheduledTasks() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'type varchar(90) NULL DEFAULT NULL,',
      'status varchar(12) NULL DEFAULT NULL,',
      'priority mediumint(9) NOT NULL DEFAULT 0,',
      'scheduled_at timestamp NULL,',
      'processed_at timestamp NULL,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at timestamp NULL,',
      'in_progress int(1),',
      'reschedule_count int(11) NOT NULL DEFAULT 0,',
      'meta longtext,',
      'PRIMARY KEY  (id),',
      'KEY type (type),',
      'KEY status (status)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function statsNotifications() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'task_id int(11) unsigned NOT NULL,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY (id),',
      'UNIQUE KEY newsletter_id_task_id (newsletter_id, task_id),',
      'KEY task_id (task_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function disableMailPoetCronTrigger() {
    $method = $this->settings->get(CronTrigger::SETTING_NAME . '.method');
    if ($method !== 'MailPoet') {
      return;
    }
    $this->settings->set(CronTrigger::SETTING_NAME . '.method', CronTrigger::METHOD_WORDPRESS);
  }

  public function scheduledTaskSubscribers() {
    $attributes = [
      'task_id int(11) unsigned NOT NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'processed int(1) NOT NULL,',
      'failed smallint(1) NOT NULL DEFAULT 0,',
      'error text NULL,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (task_id, subscriber_id),',
      'KEY subscriber_id (subscriber_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function sendingQueues() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'task_id int(11) unsigned NOT NULL,',
      'newsletter_id int(11) unsigned NULL,',
      'newsletter_rendered_body longtext,',
      'newsletter_rendered_subject varchar(250) NULL DEFAULT NULL,',
      'subscribers longtext,',
      'count_total int(11) unsigned NOT NULL DEFAULT 0,',
      'count_processed int(11) unsigned NOT NULL DEFAULT 0,',
      'count_to_process int(11) unsigned NOT NULL DEFAULT 0,',
      'meta longtext,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at timestamp NULL,',
      'PRIMARY KEY  (id),',
      'KEY task_id (task_id),',
      'KEY newsletter_id (newsletter_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function subscribers() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'wp_user_id bigint(20) NULL,',
      'is_woocommerce_user int(1) NOT NULL DEFAULT 0,',
      'first_name varchar(255) NOT NULL DEFAULT "",',
      'last_name varchar(255) NOT NULL DEFAULT "",',
      'email varchar(150) NOT NULL,',
      'status varchar(12) NOT NULL DEFAULT "' . SubscriberEntity::STATUS_UNCONFIRMED . '",',
      'subscribed_ip varchar(45) NULL,',
      'confirmed_ip varchar(45) NULL,',
      'confirmed_at timestamp NULL,',
      'last_subscribed_at timestamp NULL,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at timestamp NULL,',
      'unconfirmed_data longtext,',
      "source enum('form','imported','administrator','api','wordpress_user','woocommerce_user','woocommerce_checkout','unknown') DEFAULT 'unknown',",
      'count_confirmations int(11) unsigned NOT NULL DEFAULT 0,',
      'unsubscribe_token char(15) NULL,',
      'link_token char(32) NULL,',
      'engagement_score FLOAT unsigned NULL,',
      'engagement_score_updated_at timestamp NULL,',
      'last_engagement_at timestamp NULL,',
      'woocommerce_synced_at timestamp NULL,',
      'email_count int(11) unsigned NOT NULL DEFAULT 0, ',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY email (email),',
      'UNIQUE KEY unsubscribe_token (unsubscribe_token),',
      'KEY wp_user_id (wp_user_id),',
      'KEY updated_at (updated_at),',
      'KEY status_deleted_at (status,deleted_at),',
      'KEY last_subscribed_at (last_subscribed_at),',
      'KEY engagement_score_updated_at (engagement_score_updated_at),',
      'KEY link_token (link_token)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function subscriberSegment() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'segment_id int(11) unsigned NOT NULL,',
      'status varchar(12) NOT NULL DEFAULT "' . SubscriberEntity::STATUS_SUBSCRIBED . '",',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY subscriber_segment (subscriber_id,segment_id),',
      'KEY segment_id (segment_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function subscriberCustomField() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'custom_field_id int(11) unsigned NOT NULL,',
      'value text NOT NULL,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY subscriber_id_custom_field_id (subscriber_id,custom_field_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function subscriberIps() {
    $attributes = [
      'ip varchar(45) NOT NULL,',
      'created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (created_at, ip),',
      'KEY ip (ip)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function newsletters() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'hash varchar(150) NULL DEFAULT NULL,',
      'parent_id int(11) unsigned NULL,',
      'subject varchar(250) NOT NULL DEFAULT "",',
      'type varchar(20) NOT NULL DEFAULT "standard",',
      'sender_address varchar(150) NOT NULL DEFAULT "",',
      'sender_name varchar(150) NOT NULL DEFAULT "",',
      'status varchar(20) NOT NULL DEFAULT "' . NewsletterEntity::STATUS_DRAFT . '",',
      'reply_to_address varchar(150) NOT NULL DEFAULT "",',
      'reply_to_name varchar(150) NOT NULL DEFAULT "",',
      'preheader varchar(250) NOT NULL DEFAULT "",',
      'body longtext,',
      'sent_at timestamp NULL,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at timestamp NULL,',
      'unsubscribe_token char(15) NULL,',
      'ga_campaign varchar(250) NOT NULL DEFAULT "",',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY unsubscribe_token (unsubscribe_token),',
      'KEY type_status (type,status)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function newsletterTemplates() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) NULL DEFAULT 0,',
      'name varchar(250) NOT NULL,',
      'categories varchar(250) NOT NULL DEFAULT "[]",',
      'description varchar(255) NOT NULL DEFAULT "",',
      'body longtext,',
      'thumbnail longtext,',
      'thumbnail_data longtext,',
      'readonly tinyint(1) DEFAULT 0,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function newsletterOptionFields() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'newsletter_type varchar(90) NOT NULL,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name_newsletter_type (newsletter_type,name)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function newsletterOption() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'option_field_id int(11) unsigned NOT NULL,',
      'value longtext,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY newsletter_id_option_field_id (newsletter_id,option_field_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function newsletterSegment() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'segment_id int(11) unsigned NOT NULL,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY newsletter_segment (newsletter_id,segment_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function newsletterLinks() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'queue_id int(11) unsigned NOT NULL,',
      'url varchar(2083) NOT NULL,',
      'hash varchar(20) NOT NULL,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id (newsletter_id),',
      'KEY queue_id (queue_id),',
      'KEY url (url(100))',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function newsletterPosts() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'post_id int(11) unsigned NOT NULL,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id (newsletter_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function forms() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,', // should be null but db_delta can't handle this change
      'status varchar(20) NOT NULL DEFAULT "' . FormEntity::STATUS_ENABLED . '",',
      'body longtext,',
      'settings longtext,',
      'styles longtext,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at timestamp NULL,',
      'PRIMARY KEY  (id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function statisticsNewsletters() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'queue_id int(11) unsigned NOT NULL,',
      'sent_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id (newsletter_id),',
      'KEY subscriber_id (subscriber_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function statisticsBounces() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'queue_id int(11) unsigned NOT NULL,',
      'created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function statisticsClicks() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'queue_id int(11) unsigned NOT NULL,',
      'link_id int(11) unsigned NOT NULL,',
      'user_agent_id int(11) unsigned NULL,',
      'user_agent_type tinyint(1) NOT NULL DEFAULT 0,',
      'count int(11) unsigned NOT NULL,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id_subscriber_id_user_agent_type (newsletter_id, subscriber_id, user_agent_type),',
      'KEY queue_id (queue_id),',
      'KEY subscriber_id (subscriber_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function statisticsOpens() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'queue_id int(11) unsigned NOT NULL,',
      'user_agent_id int(11) unsigned NULL,',
      'user_agent_type tinyint(1) NOT NULL DEFAULT 0,',
      'created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id_subscriber_id_user_agent_type (newsletter_id, subscriber_id, user_agent_type),',
      'KEY queue_id (queue_id),',
      'KEY subscriber_id (subscriber_id),',
      'KEY created_at (created_at),',
      'KEY subscriber_id_created_at (subscriber_id, created_at)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function statisticsUnsubscribes() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'queue_id int(11) unsigned NULL,',
      'created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      "source varchar(255) DEFAULT 'unknown',",
      'meta varchar(255) NULL,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id_subscriber_id (newsletter_id, subscriber_id),',
      'KEY queue_id (queue_id),',
      'KEY subscriber_id (subscriber_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function statisticsForms() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'form_id int(11) unsigned NOT NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY form_subscriber (form_id,subscriber_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function statisticsWoocommercePurchases() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'queue_id int(11) unsigned NOT NULL,',
      'click_id int(11) unsigned NOT NULL,',
      'order_id bigint(20) unsigned NOT NULL,',
      'order_currency char(3) NOT NULL,',
      'order_price_total float NOT NULL COMMENT "With shipping and taxes in order_currency",',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY (id),',
      'KEY newsletter_id (newsletter_id),',
      'KEY queue_id (queue_id),',
      'KEY subscriber_id (subscriber_id),',
      'UNIQUE KEY click_id_order_id (click_id, order_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function log() {
    $attributes = [
      'id bigint(20) unsigned NOT NULL AUTO_INCREMENT,',
      'name varchar(255),',
      'level int(11),',
      'message longtext,',
      'created_at timestamp DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY (id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function userFlags() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'user_id bigint(20) NOT NULL,',
      'name varchar(50) NOT NULL,',
      'value varchar(255),',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY (id),',
      'UNIQUE KEY user_id_name (user_id, name)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function featureFlags() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'name varchar(100) NOT NULL,',
      'value tinyint(1),',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY (id),',
      'UNIQUE KEY name (name)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function dynamicSegmentFilters() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'segment_id int(11) unsigned NOT NULL,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'filter_data longblob,',
      'filter_type varchar(255) NULL,',
      'action varchar(255) NULL,',
      'PRIMARY KEY (id),',
      'KEY segment_id (segment_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function userAgents() {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'hash varchar(32) UNIQUE NOT NULL, ',
      'user_agent text NOT NULL, ',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY (id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function tags(): string {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'name varchar(191) NOT NULL,',
      'description text NOT NULL DEFAULT "",',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY (id),',
      'UNIQUE KEY name (name)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  public function subscriberTag(): string {
    $attributes = [
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'tag_id int(11) unsigned NOT NULL,',
      'created_at timestamp NULL,', // must be NULL, see comment at the top
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY (id),',
      'UNIQUE KEY subscriber_tag (subscriber_id, tag_id),',
      'KEY tag_id (tag_id)',
    ];
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  private function sqlify($model, $attributes) {
    $table = $this->prefix . Helpers::camelCaseToUnderscore($model);

    $sql = [];
    $sql[] = "CREATE TABLE " . $table . " (";
    $sql = array_merge($sql, $attributes);
    $sql[] = ") " . $this->charsetCollate . ";";

    return implode("\n", $sql);
  }

  private function updateNullInUnsubscribeStats() {
    global $wpdb;
    // perform once for versions below or equal to 3.47.6
    if (version_compare((string)$this->settings->get('db_version', '3.47.6'), '3.47.6', '>')) {
      return false;
    }
    $table = esc_sql("{$this->prefix}statistics_unsubscribes");
    $query = "
    ALTER TABLE `{$table}`
      CHANGE `newsletter_id` `newsletter_id` int(11) unsigned NULL,
      CHANGE `queue_id` `queue_id` int(11) unsigned NULL;
    ";
    $wpdb->query($query);
    return true;
  }

  /**
   * This method adds updated_at column to scheduled_task_subscribers for users with old MySQL..
   * Updated_at was added after created_at column and created_at used to have default CURRENT_TIMESTAMP.
   * Since MySQL versions below 5.6.5 allow only one column with CURRENT_TIMESTAMP as default per table
   * and db_delta doesn't remove default values we need to perform this change manually..
   * @return bool
   */
  private function fixScheduledTasksSubscribersTimestampColumns() {
    // skip the migration if the DB version is higher than 3.63.0 or is not set (a new install)
    if (version_compare((string)$this->settings->get('db_version', '3.63.1'), '3.63.0', '>')) {
      return false;
    }

    global $wpdb;
    $scheduledTasksSubscribersTable = esc_sql("{$this->prefix}scheduled_task_subscribers");
    // Remove default CURRENT_TIMESTAMP from created_at
    $updateCreatedAtQuery = "
      ALTER TABLE `$scheduledTasksSubscribersTable`
      CHANGE `created_at` `created_at` timestamp NULL;
    ";
    $wpdb->query($updateCreatedAtQuery);

    // Add updated_at column in case it doesn't exist
    $updatedAtColumnExists = $wpdb->get_results($wpdb->prepare("
      SELECT COLUMN_NAME
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE table_name = %s AND column_name = 'updated_at';
     ", $scheduledTasksSubscribersTable));
    if (empty($updatedAtColumnExists)) {
      $addUpdatedAtQuery = "
        ALTER TABLE `$scheduledTasksSubscribersTable`
        ADD `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
      ";
      $wpdb->query($addUpdatedAtQuery);
    }
    return true;
  }

  private function removeDeprecatedStatisticsIndexes(): bool {
    global $wpdb;
    // skip the migration if the DB version is higher than 3.67.1 or is not set (a new install)
    if (version_compare((string)$this->settings->get('db_version', '3.67.1'), '3.67.1', '>')) {
      return false;
    }

    $dbName = Env::$dbName;
    $statisticsTables = [
      esc_sql("{$this->prefix}statistics_clicks"),
      esc_sql("{$this->prefix}statistics_opens"),
    ];
    foreach ($statisticsTables as $statisticsTable) {
      $oldStatisticsIndexExists = $wpdb->get_results($wpdb->prepare("
      SELECT DISTINCT INDEX_NAME
      FROM INFORMATION_SCHEMA.STATISTICS
      WHERE TABLE_SCHEMA = %s
        AND TABLE_NAME = %s
        AND INDEX_NAME='newsletter_id_subscriber_id'
     ", $dbName, $statisticsTable));
      if (!empty($oldStatisticsIndexExists)) {
        $dropIndexQuery = "
        ALTER TABLE `{$statisticsTable}`
          DROP INDEX `newsletter_id_subscriber_id`
      ";
        $wpdb->query($dropIndexQuery);
      }
    }

    return true;
  }

  private function migrateSerializedFilterDataToNewColumns(): bool {
    global $wpdb;
    // skip the migration if the DB version is higher than 3.73.1 or is not set (a new install)
    if (version_compare((string)$this->settings->get('db_version', '3.73.1'), '3.73.0', '>')) {
      return false;
    }

    $dynamicSegmentFiltersTable = esc_sql("{$this->prefix}dynamic_segment_filters");
    $dynamicSegmentFilters = $wpdb->get_results("
      SELECT id, filter_data, filter_type, `action`
      FROM {$dynamicSegmentFiltersTable}
    ", ARRAY_A);
    foreach ($dynamicSegmentFilters as $dynamicSegmentFilter) {
      if ($dynamicSegmentFilter['filter_type'] && $dynamicSegmentFilter['action']) {
        continue;
      }
      $filterData = unserialize($dynamicSegmentFilter['filter_data']);
      // bc compatibility fix, the filter with the segmentType userRole didn't have filled action
      if ($filterData['segmentType'] === DynamicSegmentFilterData::TYPE_USER_ROLE && empty($filterData['action'])) {
        $filterData['action'] = UserRole::TYPE;
      }
      $wpdb->update($dynamicSegmentFiltersTable, [
        'action' => $filterData['action'] ?? null,
        'filter_type' => $filterData['segmentType'] ?? null,
      ], ['id' => $dynamicSegmentFilter['id']]);
    }

    return true;
  }

  private function migratePurchasedProductDynamicFilters(): bool {
    global $wpdb;
    // skip the migration if the DB version is higher than 3.74.3 or is not set (a new install)
    if (version_compare((string)$this->settings->get('db_version', '3.74.3'), '3.74.2', '>')) {
      return false;
    }

    $dynamicSegmentFiltersTable = esc_sql("{$this->prefix}dynamic_segment_filters");
    $filterType = DynamicSegmentFilterData::TYPE_WOOCOMMERCE;
    $action = WooCommerceProduct::ACTION_PRODUCT;
    $dynamicSegmentFilters = $wpdb->get_results("
      SELECT `id`, `filter_data`, `filter_type`, `action`
      FROM {$dynamicSegmentFiltersTable}
      WHERE `filter_type` = '{$filterType}'
        AND `action` = '{$action}'
    ", ARRAY_A);

    foreach ($dynamicSegmentFilters as $dynamicSegmentFilter) {
      $filterData = unserialize($dynamicSegmentFilter['filter_data']);
      if (!isset($filterData['product_ids'])) {
        $filterData['product_ids'] = [];
      }

      if (isset($filterData['product_id']) && !in_array($filterData['product_id'], $filterData['product_ids'])) {
        $filterData['product_ids'][] = $filterData['product_id'];
        unset($filterData['product_id']);
      }

      if (!isset($filterData['operator'])) {
        $filterData['operator'] = DynamicSegmentFilterData::OPERATOR_ANY;
      }

      $wpdb->update($dynamicSegmentFiltersTable, [
        'filter_data' => serialize($filterData),
      ], ['id' => $dynamicSegmentFilter['id']]);
    }

    return true;
  }

  private function migratePurchasedInCategoryDynamicFilters(): bool {
    global $wpdb;
    // skip the migration if the DB version is higher than 3.75.1 or is not set (a new install)
    if (version_compare((string)$this->settings->get('db_version', '3.76.0'), '3.75.1', '>')) {
      return false;
    }

    $dynamicSegmentFiltersTable = esc_sql("{$this->prefix}dynamic_segment_filters");
    $filterType = DynamicSegmentFilterData::TYPE_WOOCOMMERCE;
    $action = WooCommerceCategory::ACTION_CATEGORY;
    $dynamicSegmentFilters = $wpdb->get_results($wpdb->prepare("
      SELECT `id`, `filter_data`, `filter_type`, `action`
      FROM {$dynamicSegmentFiltersTable}
      WHERE `filter_type` = %s
        AND `action` = %s
    ", $filterType, $action), ARRAY_A);

    foreach ($dynamicSegmentFilters as $dynamicSegmentFilter) {
      $filterData = unserialize($dynamicSegmentFilter['filter_data']);
      if (!isset($filterData['category_ids'])) {
        $filterData['category_ids'] = [];
      }

      if (isset($filterData['category_id']) && !in_array($filterData['category_id'], $filterData['category_ids'])) {
        $filterData['category_ids'][] = $filterData['category_id'];
        unset($filterData['category_id']);
      }

      if (!isset($filterData['operator'])) {
        $filterData['operator'] = DynamicSegmentFilterData::OPERATOR_ANY;
      }

      $wpdb->update($dynamicSegmentFiltersTable, [
        'filter_data' => serialize($filterData),
      ], ['id' => $dynamicSegmentFilter['id']]);
    }

    return true;
  }

  private function migrateWooSubscriptionsDynamicFilters(): bool {
    global $wpdb;
    // skip the migration if the DB version is higher than 3.75.1 or is not set (a new installation)
    if (version_compare((string)$this->settings->get('db_version', '3.76.0'), '3.75.1', '>')) {
      return false;
    }

    $dynamicSegmentFiltersTable = esc_sql("{$this->prefix}dynamic_segment_filters");
    $filterType = DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION;
    $action = WooCommerceSubscription::ACTION_HAS_ACTIVE;
    $dynamicSegmentFilters = $wpdb->get_results($wpdb->prepare("
      SELECT `id`, `filter_data`, `filter_type`, `action`
      FROM {$dynamicSegmentFiltersTable}
      WHERE `filter_type` = %s
        AND `action` = %s
    ", $filterType, $action), ARRAY_A);

    foreach ($dynamicSegmentFilters as $dynamicSegmentFilter) {
      $filterData = unserialize($dynamicSegmentFilter['filter_data']);
      if (!isset($filterData['product_ids'])) {
        $filterData['product_ids'] = [];
      }

      if (isset($filterData['product_id']) && !in_array($filterData['product_id'], $filterData['product_ids'])) {
        $filterData['product_ids'][] = $filterData['product_id'];
        unset($filterData['product_id']);
      }

      if (!isset($filterData['operator'])) {
        $filterData['operator'] = DynamicSegmentFilterData::OPERATOR_ANY;
      }

      $wpdb->update($dynamicSegmentFiltersTable, [
        'filter_data' => serialize($filterData),
      ], ['id' => $dynamicSegmentFilter['id']]);
    }
    return true;
  }

  private function migrateEmailActionsFilters(): bool {
    global $wpdb;
    // skip the migration if the DB version is higher than 3.77.1 or is not set (a new installation)
    if (version_compare($this->settings->get('db_version', '3.77.2'), '3.77.1', '>')) {
      return false;
    }

    $dynamicSegmentFiltersTable = esc_sql("{$this->prefix}dynamic_segment_filters");
    $filterType = DynamicSegmentFilterData::TYPE_EMAIL;
    $dynamicSegmentFilters = $wpdb->get_results("
      SELECT `id`, `filter_data`, `filter_type`, `action`
      FROM {$dynamicSegmentFiltersTable}
      WHERE `filter_type` = '{$filterType}'
    ", ARRAY_A);

    foreach ($dynamicSegmentFilters as $dynamicSegmentFilter) {
      if (!is_array($dynamicSegmentFilter)) {
        continue;
      }
      $filterData = unserialize($dynamicSegmentFilter['filter_data']);
      if (!is_array($filterData)) {
        continue;
      }
      $action = $dynamicSegmentFilter['action'];

      // Not clicked filter is no longer used and was replaced by clicked with none of operator
      if ($action === EmailAction::ACTION_NOT_CLICKED) {
        $action = EmailAction::ACTION_CLICKED;
        $filterData['operator'] = DynamicSegmentFilterData::OPERATOR_NONE;
      }

      // Clicked link filter is refactored to work with multiple link ids
      if ($action === EmailAction::ACTION_CLICKED) {
        if (!isset($filterData['link_ids'])) {
          $filterData['link_ids'] = [];
        }

        if (isset($filterData['link_id']) && !in_array($filterData['link_id'], $filterData['link_ids'])) {
          $filterData['link_ids'][] = (int)$filterData['link_id'];
          unset($filterData['link_id']);
        }
      }

      // Not opened filter is no longer used and was replaced by opened with none of operand
      if ($action === EmailAction::ACTION_NOT_OPENED) {
        $action = EmailAction::ACTION_OPENED;
        $filterData['operator'] = DynamicSegmentFilterData::OPERATOR_NONE;
      }

      // Opened and Machine opened filters are refactored to work with multiple newsletters
      if (($action === EmailAction::ACTION_OPENED) || ($action === EmailAction::ACTION_MACHINE_OPENED)) {
        if (!isset($filterData['newsletters'])) {
          $filterData['newsletters'] = [];
        }

        if (isset($filterData['newsletter_id']) && !in_array($filterData['newsletter_id'], $filterData['newsletters'])) {
          $filterData['newsletters'][] = (int)$filterData['newsletter_id'];
          unset($filterData['newsletter_id']);
        }
      }

      // Ensure default operator
      if (!isset($filterData['operator'])) {
        $filterData['operator'] = DynamicSegmentFilterData::OPERATOR_ANY;
      }

      $wpdb->update($dynamicSegmentFiltersTable, [
        'filter_data' => serialize($filterData),
        'action' => $action,
      ], ['id' => $dynamicSegmentFilter['id']]);
    }
    return true;
  }

  private function updateDefaultInactiveSubscriberTimeRange(): bool {
    // Skip if the installed version is newer than the release that preceded this migration, or if it's a fresh install
    $currentlyInstalledVersion = (string)$this->settings->get('db_version', '3.78.1');
    if (version_compare($currentlyInstalledVersion, '3.78.0', '>')) {
      return false;
    }

    $currentValue = (int)$this->settings->get('deactivate_subscriber_after_inactive_days');
    if ($currentValue === 180) {
      $this->settings->set('deactivate_subscriber_after_inactive_days', 365);
      $this->settingsChangeHandler->onInactiveSubscribersIntervalChange();
    }

    return true;
  }

  private function setDefaultValueForLoadingThirdPartyLibrariesForExistingInstalls(): bool {
    // skip the migration if the DB version is higher than 3.91.1 or is not set (a new installation)
    if (version_compare($this->settings->get('db_version', '3.91.2'), '3.91.1', '>')) {
      return false;
    }

    $thirdPartyScriptsEnabled = $this->settings->get('3rd_party_libs');
    if (is_null($thirdPartyScriptsEnabled)) {
      // keep loading 3rd party libraries for existing users so the functionality is not broken
      $this->settings->set('3rd_party_libs.enabled', '1');
    }

    return true;
  }
}
