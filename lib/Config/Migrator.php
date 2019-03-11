<?php
namespace MailPoet\Config;

use MailPoet\Models\Subscriber;
use MailPoet\Models\Newsletter;
use MailPoet\Util\Helpers;

if (!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

class Migrator {

  public $prefix;
  private $charset_collate;
  private $models;

  function __construct() {
    $this->prefix = Env::$db_prefix;
    $this->charset_collate = Env::$db_charset_collate;
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
      'statistics_opens',
      'statistics_unsubscribes',
      'statistics_forms',
      'mapping_to_external_entities',
      'log',
    ];
  }

  function up() {
    global $wpdb;

    $output = [];
    foreach ($this->models as $model) {
      $modelMethod = Helpers::underscoreToCamelCase($model);
      $output = array_merge(dbDelta($this->$modelMethod()), $output);
    }
    return $output;
  }

  function down() {
    global $wpdb;

    $_this = $this;
    $drop_table = function($model) use($wpdb, $_this) {
      $table = $_this->prefix . $model;
      $wpdb->query("DROP TABLE {$table}");
    };

    array_map($drop_table, $this->models);
  }

  function segments() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'type varchar(90) NOT NULL DEFAULT "default",',
      'description varchar(250) NOT NULL DEFAULT "",',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at timestamp NULL,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name (name)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function settings() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'name varchar(50) NOT NULL,',
      'value longtext,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name (name)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function customFields() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'type varchar(90) NOT NULL,',
      'params longtext NOT NULL,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name (name)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function scheduledTasks() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'type varchar(90) NULL DEFAULT NULL,',
      'status varchar(12) NULL DEFAULT NULL,',
      'priority mediumint(9) NOT NULL DEFAULT 0,',
      'scheduled_at timestamp NULL,',
      'processed_at timestamp NULL,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at timestamp NULL,',
      'PRIMARY KEY  (id),',
      'KEY type (type),',
      'KEY status (status)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function statsNotifications() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'task_id int(11) unsigned NOT NULL,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY (id),',
      'UNIQUE KEY newsletter_id_task_id (newsletter_id, task_id),',
      'KEY task_id (task_id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function scheduledTaskSubscribers() {
    $attributes = array (
      'task_id int(11) unsigned NOT NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'processed int(1) NOT NULL,',
      'failed smallint(1) NOT NULL DEFAULT 0,',
      'error text NULL,',
      'created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (task_id, subscriber_id),',
      'KEY subscriber_id (subscriber_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function sendingQueues() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'task_id int(11) unsigned NOT NULL,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'newsletter_rendered_body longtext,',
      'newsletter_rendered_subject varchar(250) NULL DEFAULT NULL,',
      'subscribers longtext,',
      'count_total int(11) unsigned NOT NULL DEFAULT 0,',
      'count_processed int(11) unsigned NOT NULL DEFAULT 0,',
      'count_to_process int(11) unsigned NOT NULL DEFAULT 0,',
      'meta longtext,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at timestamp NULL,',
      'PRIMARY KEY  (id),',
      'KEY task_id (task_id),',
      'KEY newsletter_id (newsletter_id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function subscribers() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'wp_user_id bigint(20) NULL,',
      'is_woocommerce_user int(1) NOT NULL DEFAULT 0,',
      'first_name varchar(255) NOT NULL DEFAULT "",',
      'last_name varchar(255) NOT NULL DEFAULT "",',
      'email varchar(150) NOT NULL,',
      'status varchar(12) NOT NULL DEFAULT "' . Subscriber::STATUS_UNCONFIRMED . '",',
      'subscribed_ip varchar(45) NULL,',
      'confirmed_ip varchar(45) NULL,',
      'confirmed_at timestamp NULL,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at timestamp NULL,',
      'unconfirmed_data longtext,',
      "source enum('form','imported','administrator','api','wordpress_user','woocommerce_user','unknown') DEFAULT 'unknown',",
      'count_confirmations int(11) unsigned NOT NULL DEFAULT 0,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY email (email),',
      'KEY wp_user_id (wp_user_id),',
      'KEY updated_at (updated_at),',
      'KEY status_deleted_at (status,deleted_at)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function subscriberSegment() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'segment_id int(11) unsigned NOT NULL,',
      'status varchar(12) NOT NULL DEFAULT "' . Subscriber::STATUS_SUBSCRIBED . '",',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY subscriber_segment (subscriber_id,segment_id),',
      'KEY segment_id (segment_id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function subscriberCustomField() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'custom_field_id int(11) unsigned NOT NULL,',
      'value text NOT NULL,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY subscriber_id_custom_field_id (subscriber_id,custom_field_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function subscriberIps() {
    $attributes = array(
      'ip varchar(45) NOT NULL,',
      'created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (created_at, ip),',
      'KEY ip (ip)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletters() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'hash varchar(150) NULL DEFAULT NULL,',
      'parent_id int(11) unsigned NULL,',
      'subject varchar(250) NOT NULL DEFAULT "",',
      'type varchar(20) NOT NULL DEFAULT "standard",',
      'sender_address varchar(150) NOT NULL DEFAULT "",',
      'sender_name varchar(150) NOT NULL DEFAULT "",',
      'status varchar(20) NOT NULL DEFAULT "'.Newsletter::STATUS_DRAFT.'",',
      'reply_to_address varchar(150) NOT NULL DEFAULT "",',
      'reply_to_name varchar(150) NOT NULL DEFAULT "",',
      'preheader varchar(250) NOT NULL DEFAULT "",',
      'body longtext,',
      'sent_at timestamp NULL,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at timestamp NULL,',
      'PRIMARY KEY  (id),',
      'KEY type_status (type,status)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletterTemplates() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) NULL DEFAULT 0,',
      'name varchar(250) NOT NULL,',
      'categories varchar(250) NOT NULL DEFAULT "[]",',
      'description varchar(255) NOT NULL DEFAULT "",',
      'body longtext,',
      'thumbnail longtext,',
      'readonly tinyint(1) DEFAULT 0,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletterOptionFields() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'newsletter_type varchar(90) NOT NULL,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name_newsletter_type (newsletter_type,name)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletterOption() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'option_field_id int(11) unsigned NOT NULL,',
      'value longtext,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY newsletter_id_option_field_id (newsletter_id,option_field_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletterSegment() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'segment_id int(11) unsigned NOT NULL,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY newsletter_segment (newsletter_id,segment_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletterLinks() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'queue_id int(11) unsigned NOT NULL,',
      'url varchar(2083) NOT NULL,',
      'hash varchar(20) NOT NULL,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id (newsletter_id),',
      'KEY queue_id (queue_id),',
      'KEY url (url(255))',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletterPosts() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'post_id int(11) unsigned NOT NULL,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id (newsletter_id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function forms() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'body longtext,',
      'settings longtext,',
      'styles longtext,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at timestamp NULL,',
      'PRIMARY KEY  (id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function statisticsNewsletters() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'queue_id int(11) unsigned NOT NULL,',
      'sent_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id (newsletter_id),',
      'KEY subscriber_id (subscriber_id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function statisticsClicks() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'queue_id int(11) unsigned NOT NULL,',
      'link_id int(11) unsigned NOT NULL,',
      'count int(11) unsigned NOT NULL,',
      'created_at timestamp NULL,',
      'updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id (newsletter_id),',
      'KEY queue_id (queue_id),',
      'KEY subscriber_id (subscriber_id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function statisticsOpens() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'queue_id int(11) unsigned NOT NULL,',
      'created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id (newsletter_id),',
      'KEY queue_id (queue_id),',
      'KEY subscriber_id (subscriber_id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function statisticsUnsubscribes() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'newsletter_id int(11) unsigned NOT NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'queue_id int(11) unsigned NOT NULL,',
      'created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id (newsletter_id),',
      'KEY queue_id (queue_id),',
      'KEY subscriber_id (subscriber_id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function statisticsForms() {
    $attributes = array(
      'id int(11) unsigned NOT NULL AUTO_INCREMENT,',
      'form_id int(11) unsigned NOT NULL,',
      'subscriber_id int(11) unsigned NOT NULL,',
      'created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY form_subscriber (form_id,subscriber_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function mappingToExternalEntities() {
    $attributes = array(
      'old_id int(11) unsigned NOT NULL,',
      'type varchar(50) NOT NULL,',
      'new_id int(11) unsigned NOT NULL,',
      'created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY (old_id, type),',
      'KEY new_id (new_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function log() {
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

  private function sqlify($model, $attributes) {
    $table = $this->prefix . Helpers::camelCaseToUnderscore($model);

    $sql = array();
    $sql[] = "CREATE TABLE " . $table . " (";
    $sql = array_merge($sql, $attributes);
    $sql[] = ") " . $this->charset_collate . ";";

    return implode("\n", $sql);
  }
}
