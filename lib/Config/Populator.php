<?php
namespace MailPoet\Config;

use MailPoet\Config\PopulatorData\Templates\FranksRoastHouseTemplate;
use MailPoet\Config\PopulatorData\Templates\BlankTemplate;
use MailPoet\Config\PopulatorData\Templates\WelcomeTemplate;
use MailPoet\Config\PopulatorData\Templates\PostNotificationsBlankTemplate;
use \MailPoet\Models\Segment;
use \MailPoet\Segments\WP;
use \MailPoet\Models\Setting;
use \MailPoet\Settings\Pages;

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

    $this->createDefaultSegments();
    $this->createDefaultSettings();
    $this->createMailPoetPage();
  }

  private function createMailPoetPage() {
    $pages = get_posts(array(
      'posts_per_page' => 1,
      'orderby' => 'date',
      'order' => 'DESC',
      'post_type' => 'mailpoet_page'
    ));

    $page = null;
    if(!empty($pages)) {
      $page = array_shift($pages);
      if(strpos($page->post_content, '[mailpoet_page]') === false) {
        $page = null;
      }
    }

    if($page === null) {
      $mailpoet_page_id = Pages::createMailPoetPage();
      Setting::setValue('subscription.page', $mailpoet_page_id);
    }
  }

  private function createDefaultSettings() {
    $current_user = wp_get_current_user();

    // user name
    $user_name = '';
    if($current_user->user_firstname) {
      $user_name = $current_user->user_firstname;
    }
    if($current_user->user_lastname) {
      if($user_name) {
        $user_name .= ' '.$current_user->user_lastname;
      }
    }
    if(!$user_name) {
      $user_name = $current_user->display_name;
    }

    // default from name & address
    Setting::setValue('sender', array(
      'name' => $user_name,
      'address' => $current_user->user_email
    ));

    // enable signup confirmation by default
    Setting::setValue('signup_confirmation.enabled', true);
  }

  private function createDefaultSegments() {
    // WP Users segment
    $wp_users_segment = Segment::getWPUsers();

    if($wp_users_segment === false) {
      // create the wp users list
      $wp_users_segment = Segment::create();
      $wp_users_segment->hydrate(array(
        'name' => __('WordPress Users'),
        'description' =>
          __('The list containing all of your WordPress users.'),
        'type' => 'wp_users'
      ));
      $wp_users_segment->save();
    }

    // Synchronize WP Users
    WP::synchronizeUsers();

    // Default segment
    if(Segment::where('type', 'default')->count() === 0) {
      $default_segment = Segment::create();
      $default_segment->hydrate(array(
        'name' => __('My First List'),
        'description' =>
          __('The list created automatically on install of MailPoet')
      ));
      $default_segment->save();
    }
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
      (new FranksRoastHouseTemplate(Env::$assets_url))->get(),
      (new BlankTemplate(Env::$assets_url))->get(),
      (new WelcomeTemplate(Env::$assets_url))->get(),
      (new PostNotificationsBlankTemplate(Env::$assets_url))->get(),
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
