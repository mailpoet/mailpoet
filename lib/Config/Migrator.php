<?php
namespace MailPoet\Config;

use MailPoet\Models\Subscriber;
use MailPoet\Models\Newsletter;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

class Migrator {
  function __construct() {
    $this->prefix = Env::$db_prefix;
    $this->charset_collate = Env::$db_charset_collate;
    $this->models = array(
      'segments',
      'settings',
      'custom_fields',
      'sending_queues',
      'subscribers',
      'subscriber_segment',
      'subscriber_custom_field',
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
      'statistics_forms'
    );
  }

  function up() {
    global $wpdb;

    $_this = $this;
    $migrate = function($model) use($_this) {
      $modelMethod = Helpers::underscoreToCamelCase($model);
      dbDelta($_this->$modelMethod());
    };

    array_map($migrate, $this->models);
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
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'type varchar(90) NOT NULL DEFAULT "default",',
      'description varchar(250) NOT NULL DEFAULT "",',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at TIMESTAMP NULL,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name (name)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function settings() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'name varchar(20) NOT NULL,',
      'value longtext,',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name (name)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function customFields() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'type varchar(90) NOT NULL,',
      'params longtext NOT NULL,',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name (name)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function sendingQueues() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'type varchar(90) NULL DEFAULT NULL,',
      'newsletter_id mediumint(9) NOT NULL,',
      'newsletter_rendered_body longtext,',
      'newsletter_rendered_subject varchar(250) NULL DEFAULT NULL,',
      'subscribers longtext,',
      'status varchar(12) NULL DEFAULT NULL,',
      'priority mediumint(9) NOT NULL DEFAULT 0,',
      'count_total mediumint(9) NOT NULL DEFAULT 0,',
      'count_processed mediumint(9) NOT NULL DEFAULT 0,',
      'count_to_process mediumint(9) NOT NULL DEFAULT 0,',
      'scheduled_at TIMESTAMP NULL,',
      'processed_at TIMESTAMP NULL,',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at TIMESTAMP NULL,',
      'PRIMARY KEY  (id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function subscribers() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'wp_user_id bigint(20) NULL,',
      'first_name tinytext NOT NULL DEFAULT "",',
      'last_name tinytext NOT NULL DEFAULT "",',
      'email varchar(150) NOT NULL,',
      'status varchar(12) NOT NULL DEFAULT "' . Subscriber::STATUS_UNCONFIRMED . '",',
      'subscribed_ip varchar(32) NULL,',
      'confirmed_ip varchar(32) NULL,',
      'confirmed_at TIMESTAMP NULL,',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at TIMESTAMP NULL,',
      'unconfirmed_data longtext,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY email (email)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function subscriberSegment() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'subscriber_id mediumint(9) NOT NULL,',
      'segment_id mediumint(9) NOT NULL,',
      'status varchar(12) NOT NULL DEFAULT "' . Subscriber::STATUS_SUBSCRIBED . '",',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY subscriber_segment (subscriber_id,segment_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function subscriberCustomField() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'subscriber_id mediumint(9) NOT NULL,',
      'custom_field_id mediumint(9) NOT NULL,',
      'value varchar(255) NOT NULL DEFAULT "",',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY subscriber_id_custom_field_id (subscriber_id,custom_field_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletters() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'hash varchar(150) NULL DEFAULT NULL,',
      'parent_id mediumint(9) NULL,',
      'subject varchar(250) NOT NULL DEFAULT "",',
      'type varchar(20) NOT NULL DEFAULT "standard",',
      'sender_address varchar(150) NOT NULL DEFAULT "",',
      'sender_name varchar(150) NOT NULL DEFAULT "",',
      'status varchar(20) NOT NULL DEFAULT "'.Newsletter::STATUS_DRAFT.'",',
      'reply_to_address varchar(150) NOT NULL DEFAULT "",',
      'reply_to_name varchar(150) NOT NULL DEFAULT "",',
      'preheader varchar(250) NOT NULL DEFAULT "",',
      'body longtext,',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at TIMESTAMP NULL,',
      'PRIMARY KEY  (id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletterTemplates() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'name varchar(250) NOT NULL,',
      'description varchar(250) NOT NULL,',
      'body LONGTEXT,',
      'thumbnail LONGTEXT,',
      'readonly TINYINT(1) DEFAULT 0,',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletterOptionFields() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'newsletter_type varchar(90) NOT NULL,',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name_newsletter_type (newsletter_type,name)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletterOption() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'newsletter_id mediumint(9) NOT NULL,',
      'option_field_id mediumint(9) NOT NULL,',
      'value varchar(255) NOT NULL DEFAULT "",',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY newsletter_id_option_field_id (newsletter_id,option_field_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletterSegment() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'newsletter_id mediumint(9) NOT NULL,',
      'segment_id mediumint(9) NOT NULL,',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY newsletter_segment (newsletter_id,segment_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletterLinks() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'newsletter_id mediumint(9) NOT NULL,',
      'queue_id mediumint(9) NOT NULL,',
      'url varchar(255) NOT NULL,',
      'hash varchar(20) NOT NULL,',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletterPosts() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'newsletter_id mediumint(9) NOT NULL,',
      'post_id mediumint(9) NOT NULL,',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function forms() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'body longtext,',
      'settings longtext,',
      'styles longtext,',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at TIMESTAMP NULL,',
      'PRIMARY KEY  (id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function statisticsNewsletters() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'newsletter_id mediumint(9) NOT NULL,',
      'subscriber_id mediumint(9) NOT NULL,',
      'queue_id mediumint(9) NOT NULL,',
      'sent_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id (newsletter_id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function statisticsClicks() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'newsletter_id mediumint(9) NOT NULL,',
      'subscriber_id mediumint(9) NOT NULL,',
      'queue_id mediumint(9) NOT NULL,',
      'link_id mediumint(9) NOT NULL,',
      'count mediumint(9) NOT NULL,',
      'created_at TIMESTAMP NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id (newsletter_id),',
      'KEY queue_id (queue_id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function statisticsOpens() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'newsletter_id mediumint(9) NOT NULL,',
      'subscriber_id mediumint(9) NOT NULL,',
      'queue_id mediumint(9) NOT NULL,',
      'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id (newsletter_id),',
      'KEY queue_id (queue_id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function statisticsUnsubscribes() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'newsletter_id mediumint(9) NOT NULL,',
      'subscriber_id mediumint(9) NOT NULL,',
      'queue_id mediumint(9) NOT NULL,',
      'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'KEY newsletter_id (newsletter_id),',
      'KEY queue_id (queue_id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function statisticsForms() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'form_id mediumint(9) NOT NULL,',
      'subscriber_id mediumint(9) NOT NULL,',
      'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY form_subscriber (form_id,subscriber_id)'
    );
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
