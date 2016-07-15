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
use \MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

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
    } else {
      $mailpoet_page_id = (int)$page->ID;
    }

    $subscription = Setting::getValue('subscription.pages', array());
    if(empty($subscription)) {
      Setting::setValue('subscription.pages', array(
        'unsubscribe' => $mailpoet_page_id,
        'manage' => $mailpoet_page_id,
        'confirmation' => $mailpoet_page_id
      ));
    }
  }

  private function createDefaultSettings() {
    $current_user = wp_get_current_user();

    if(!Setting::getValue('task_scheduler')) {
      // disable task scheduler (cron) be default
      Setting::setValue('task_scheduler', array(
        'method' => 'WordPress'
      ));
    }

    // default sender info based on current user
    $sender = array(
      'name' => $current_user->display_name,
      'address' => $current_user->user_email
    );

    if(!Setting::getValue('sender')) {
      // default from name & address
      Setting::setValue('sender', $sender);
    }

    if(!Setting::getValue('signup_confirmation')) {
      // enable signup confirmation by default
      Setting::setValue('signup_confirmation', array(
        'enabled' => true,
        'from' => array(
          'name' => get_option('blogname'),
          'address' => get_option('admin_email')
        ),
        'reply_to' => $sender
      ));
    }
  }

  private function createDefaultSegments() {
    // WP Users segment
    $wp_segment = Segment::getWPSegment();

    // Synchronize WP Users
    WP::synchronizeUsers();

    // Default segment
    if(Segment::where('type', 'default')->count() === 0) {
      $default_segment = Segment::create();
      $default_segment->hydrate(array(
        'name' => __('My First List'),
        'description' =>
          __('This list is automatically created when you install MailPoet')
      ));
      $default_segment->save();
    }
  }

  private function newsletterOptionFields() {
    return array(
      array(
        'name' => 'isScheduled',
        'newsletter_type' => 'standard',
      ),
      array(
        'name' => 'scheduledAt',
        'newsletter_type' => 'standard',
      ),
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
      array(
        'name' => 'schedule',
        'newsletter_type' => 'notification',
      )
    );
  }

  private function newsletterTemplates() {
    return array(
      (new FranksRoastHouseTemplate(Env::$assets_url))->get(),
      (new BlankTemplate(Env::$assets_url))->get(),
      (new WelcomeTemplate(Env::$assets_url))->get(),
      (new PostNotificationsBlankTemplate(Env::$assets_url))->get(),
    );
  }

  private function populate($model) {
    $modelMethod = Helpers::underscoreToCamelCase($model);
    $rows = $this->$modelMethod();
    $table = $this->prefix . $model;
    $_this = $this;

    array_map(function($row) use ($_this, $table) {
      if(!$_this->rowExists($table, $row)) {
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
