<?php
namespace MailPoet\Config;

use MailPoet\Config\PopulatorData\Templates\SampleTemplate;

if (!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

class Populator {
  function __construct() {
    $this->prefix = Env::$db_prefix;
    $this->models = array(
      'newsletter_option_fields',
      'newsletter_templates',
    );
  }

  function up() {
    global $wpdb;

    $_this = $this;

    $populate = function($model) use($_this, $wpdb) {
      $fields = $_this->$model();
      $table = $_this->prefix . $model;

      array_map(function($field) use ($wpdb, $table) {
        $column_conditions = array_map(function($key) use ($field) {
          return $key . '=' . $field[$key];
        }, $field);
        if ($wpdb->get_var("SELECT COUNT(*) FROM " . $table . " WHERE " . implode(' AND ', $column_conditions)) === 0) {
          $wpdb->insert(
            $table,
            $field
          );
        }
      }, $fields);
    };

    array_map(array($this, 'populate'), $this->models);
  }

  function newsletter_option_fields() {
    return array(
      array(
        'name' => 'event',
        'newsletter_type' => 'welcome',
      ),
      array(
        'name' => 'segment',
        'newsletter_type' => 'welcome',
      ),
      array(
        'name' => 'role',
        'newsletter_type' => 'welcome',
      ),
      array(
        'name' => 'afterTimeNumber',
        'newsletter_type' => 'welcome',
      ),
      array(
        'name' => 'afterTimeType',
        'newsletter_type' => 'welcome',
      ),

      array(
        'name' => 'intervalType',
        'newsletter_type' => 'notification',
      ),
      array(
        'name' => 'timeOfDay',
        'newsletter_type' => 'notification',
      ),
      array(
        'name' => 'weekDay',
        'newsletter_type' => 'notification',
      ),
      array(
        'name' => 'monthDay',
        'newsletter_type' => 'notification',
      ),
      array(
        'name' => 'nthWeekDay',
        'newsletter_type' => 'notification',
      ),
    );
  }

  private function newsletter_templates() {
    return array(
      (new SampleTemplate(Env::$assets_url))->get(),
    );
  }

  private function populate($model) {
    $rows = $this->$model();
    $table = $this->prefix . $model;
    $_this = $this;

    array_map(function($row) use ($_this, $table) {
      if (!$_this->rowExists($table, $row)) {
        $_this->insertRow($table, $row);
      }
    }, $rows);
  }

  private function rowExists($table, $columns) {
    global $wpdb;

    $conditions = array_map(function($key) use ($columns) {
      return $key . '=%s';
    }, array_keys($columns));

    return $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $table WHERE " . implode(' AND ', $conditions),
      array_values($columns)
    )) > 0;
  }

  private function insertRow($table, $row) {
    global $wpdb;

    return $wpdb->insert(
      $table,
      $row
    );
  }
}
