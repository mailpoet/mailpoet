<?php
namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

class Migrator {
  function __construct() {
    $this->prefix = Env::$db_prefix;
    $this->charset = Env::$db_charset;
    $this->models = array(
      'subscribers',
      'settings',
      'newsletters',
      'newsletter_templates',
      'segments',
      'subscriber_segment',
      'newsletter_segment',
      'custom_fields',
      'subscriber_custom_field',
      'newsletter_option_fields',
      'newsletter_option',
      'sending_queues',
      'newsletter_statistics',
      'forms'
    );
  }

  function up() {
    global $wpdb;

    $_this = $this;
    $migrate = function($model) use($_this) {
      dbDelta($_this->$model());
    };

    array_map($migrate, $this->models);
  }

  function down() {
    global $wpdb;

    $drop_table = function($model) use($wpdb) {
      $table = $this->prefix . $model;
      $wpdb->query("DROP TABLE {$table}");
    };

    array_map($drop_table, $this->models);
  }

  function subscribers() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'wp_user_id bigint(20) NULL,',
      'first_name tinytext NOT NULL,',
      'last_name tinytext NOT NULL,',
      'email varchar(150) NOT NULL,',
      'status varchar(12) NOT NULL DEFAULT "unconfirmed",',
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'deleted_at TIMESTAMP NULL DEFAULT NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY email (email)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function settings() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'name varchar(20) NOT NULL,',
      'value longtext,',
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name (name)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletters() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'subject varchar(250) NOT NULL,',
      'type varchar(20) NOT NULL DEFAULT "standard",',
      'sender_address varchar(150) NOT NULL,',
      'sender_name varchar(150) NOT NULL,',
      'reply_to_address varchar(150) NOT NULL,',
      'reply_to_name varchar(150) NOT NULL,',
      'preheader varchar(250) NOT NULL,',
      'body longtext,',
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'deleted_at TIMESTAMP NULL DEFAULT NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletter_templates() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'name varchar(250) NOT NULL,',
      'description varchar(250) NOT NULL,',
      'body LONGTEXT,',
      'thumbnail LONGTEXT,',
      'readonly TINYINT(1) DEFAULT 0,',
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function segments() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'type varchar(90) NOT NULL DEFAULT "default",',
      'description varchar(250) NOT NULL,',
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'deleted_at TIMESTAMP NULL DEFAULT NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name (name)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function subscriber_segment() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'subscriber_id mediumint(9) NOT NULL,',
      'segment_id mediumint(9) NOT NULL,',
      'status varchar(12) NOT NULL DEFAULT "subscribed",',
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY subscriber_segment (subscriber_id,segment_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletter_segment() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'newsletter_id mediumint(9) NOT NULL,',
      'segment_id mediumint(9) NOT NULL,',
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY newsletter_segment (newsletter_id,segment_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function custom_fields() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'type varchar(90) NOT NULL,',
      'params longtext NOT NULL,',
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name (name)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function subscriber_custom_field() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'subscriber_id mediumint(9) NOT NULL,',
      'custom_field_id mediumint(9) NOT NULL,',
      'value varchar(255) NOT NULL,',
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY subscriber_id_custom_field_id (subscriber_id,custom_field_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletter_option_fields() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'name varchar(90) NOT NULL,',
      'newsletter_type varchar(90) NOT NULL,',
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY name_newsletter_type (newsletter_type,name)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletter_option() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'newsletter_id mediumint(9) NOT NULL,',
      'option_field_id mediumint(9) NOT NULL,',
      'value varchar(255) NOT NULL,',
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id),',
      'UNIQUE KEY newsletter_id_option_field_id (newsletter_id,option_field_id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function sending_queues() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'newsletter_id mediumint(9) NOT NULL,',
      'subscribers longtext,',
      'status varchar(12) NULL DEFAULT NULL,',
      'priority mediumint(9) NOT NULL DEFAULT 0,',
      'count_total mediumint(9) NOT NULL DEFAULT 0,',
      'count_processed mediumint(9) NOT NULL DEFAULT 0,',
      'count_to_process mediumint(9) NOT NULL DEFAULT 0,',
      'count_failed mediumint(9) NOT NULL DEFAULT 0,',
      'processed_at TIMESTAMP NOT NULL DEFAULT 0,',
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at TIMESTAMP NULL DEFAULT NULL,',
      'PRIMARY KEY  (id)',
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function newsletter_statistics() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'newsletter_id mediumint(9) NOT NULL,',
      'subscriber_id mediumint(9) NOT NULL,',
      'queue_id mediumint(9) NOT NULL,',
      'sent_at TIMESTAMP NOT NULL DEFAULT 0,',
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'deleted_at TIMESTAMP NULL DEFAULT NULL,',
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
      'created_at TIMESTAMP NOT NULL DEFAULT 0,',
      'deleted_at TIMESTAMP NULL DEFAULT NULL,',
      'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,',
      'PRIMARY KEY  (id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  private function sqlify($model, $attributes) {
    $table = $this->prefix . $model;

    $sql = array();
    $sql[] = "CREATE TABLE " . $table . " (";
    $sql = array_merge($sql, $attributes);
    $sql[] = ") " . $this->charset . ";";

    return implode("\n", $sql);
  }
}
