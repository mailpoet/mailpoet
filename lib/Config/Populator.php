<?php
namespace MailPoet\Config;

use MailPoet\Cron\CronTrigger;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\Segment;
use MailPoet\Segments\WP;
use MailPoet\Models\Setting;
use MailPoet\Settings\Pages;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

class Populator {
  public $prefix;
  public $models;
  public $templates;
  const TEMPLATES_NAMESPACE = '\MailPoet\Config\PopulatorData\Templates\\';

  function __construct() {
    $this->prefix = Env::$db_prefix;
    $this->models = array(
      'newsletter_option_fields',
      'newsletter_templates',
    );
    $this->templates = array(
      "FranksRoastHouseTemplate",
      "NewsletterBlank1Column",
      "NewsletterBlank12Column",
      "NewsletterBlank121Column",
      "NewsletterBlank13Column",
      "PostNotificationsBlank1Column",
      "WelcomeBlank1Column",
      "WelcomeBlank12Column",
      "SimpleText",
      "Restaurant",
      "StoreDiscount",
      "TravelEmail"
    );
  }

  function up() {
    $this->convertExistingDataToUTF8();

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

    // set cron trigger option to default method
    if(!Setting::getValue(CronTrigger::SETTING_NAME)) {
      Setting::setValue(CronTrigger::SETTING_NAME, array(
        'method' => CronTrigger::DEFAULT_METHOD
      ));
    }

    // set default sender info based on current user
    $sender = array(
      'name' => $current_user->display_name,
      'address' => $current_user->user_email
    );

    // set default from name & address
    if(!Setting::getValue('sender')) {
      Setting::setValue('sender', $sender);
    }

    // enable signup confirmation by default
    if(!Setting::getValue('signup_confirmation')) {
      Setting::setValue('signup_confirmation', array(
        'enabled' => true,
        'from' => array(
          'name' => get_option('blogname'),
          'address' => get_option('admin_email')
        ),
        'reply_to' => $sender
      ));
    }

    // set installation date
    if(!Setting::getValue('installed_at')) {
      Setting::setValue('installed_at', date("Y-m-d H:i:s"));
    }

    // reset mailer log
    MailerLog::resetMailerLog();
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
        'name' => __('My First List', 'mailpoet'),
        'description' =>
          __('This list is automatically created when you install MailPoet.', 'mailpoet')
      ));
      $default_segment->save();
    }
  }

  private function newsletterOptionFields() {
    $option_fields = array(
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

    return array(
      'rows' => $option_fields,
      'identification_columns' => array(
        'name',
        'newsletter_type'
      )
    );
  }

  private function newsletterTemplates() {
    $templates = array();
    foreach($this->templates as $template) {
      $template = self::TEMPLATES_NAMESPACE . $template;
      $template = new $template(Env::$assets_url);
      $templates[] = $template->get();
    }
    return array(
      'rows' => $templates,
      'identification_columns' => array(
        'name'
      ),
      'remove_duplicates' => true
    );
  }

  private function populate($model) {
    $modelMethod = Helpers::underscoreToCamelCase($model);
    $table = $this->prefix . $model;
    $data_descriptor = $this->$modelMethod();
    $rows = $data_descriptor['rows'];
    $identification_columns = array_fill_keys(
      $data_descriptor['identification_columns'],
      ''
    );
    $remove_duplicates =
      isset($data_descriptor['remove_duplicates']) && $data_descriptor['remove_duplicates'];

    foreach($rows as $row) {
      $existence_comparison_fields = array_intersect_key(
        $row,
        $identification_columns
      );

      if(!$this->rowExists($table, $existence_comparison_fields)) {
        $this->insertRow($table, $row);
      } else {
        if($remove_duplicates) {
          $this->removeDuplicates($table, $row, $existence_comparison_fields);
        }
        $this->updateRow($table, $row, $existence_comparison_fields);
      }
    }
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

  private function updateRow($table, $row, $where) {
    global $wpdb;

    return $wpdb->update(
      $table,
      $row,
      $where
    );
  }

  private function removeDuplicates($table, $row, $where) {
    global $wpdb;

    $conditions = array('1=1');
    $values = array();
    foreach($where as $field => $value) {
      $conditions[] = "`t1`.`$field` = `t2`.`$field`";
      $conditions[] = "`t1`.`$field` = %s";
      $values[] = $value;
    }

    $conditions = implode(' AND ', $conditions);

    $sql = "DELETE FROM `$table` WHERE $conditions";
    return $wpdb->query(
      $wpdb->prepare(
        "DELETE t1 FROM $table t1, $table t2 WHERE t1.id < t2.id AND $conditions",
        $values
      )
    );
  }

  /*
   * MailPoet versions 3.0.0-beta.32 and older used the default MySQL connection
   * character set, which usually defaults to latin1, but stored UTF-8 data.
   * This method converts existing incorrectly stored data that uses the
   * default character set, into a new character set that is used by WordPress.
   */
  public function convertExistingDataToUTF8() {
    global $wpdb;

    if(!version_compare(get_option('mailpoet_db_version'), '3.0.0-beta.32', '<=')) {
      // Data conversion should only be performed only once, when migrating from
      // older version
      return;
    }

    $source_charset = $wpdb->get_var('SELECT @@GLOBAL.character_set_connection');
    $destination_charset = $wpdb->get_var('SELECT @@SESSION.character_set_connection');

    if($source_charset === $destination_charset) return;
    // UTF8MB4 is a superset of UTF8, thus a conversion is not necessary
    if(substr($source_charset, 0, 4) === 'utf8' && $destination_charset === 'utf8mb4') return;

    $tables = array(
      'segments' => array('name', 'type', 'description'),
      'settings' => array('name', 'value'),
      'custom_fields' => array('name', 'type', 'params'),
      'sending_queues' => array('type', 'newsletter_rendered_body', 'newsletter_rendered_subject', 'subscribers', 'status'),
      'subscribers' => array('first_name', 'last_name', 'email', 'status', 'subscribed_ip', 'confirmed_ip', 'unconfirmed_data'),
      'subscriber_segment' => array('status'),
      'subscriber_custom_field' => array('value'),
      'newsletters' => array('hash', 'subject', 'type', 'sender_address', 'sender_name', 'status', 'reply_to_address', 'reply_to_name', 'preheader', 'body'),
      'newsletter_templates' => array('name', 'description', 'body', 'thumbnail'),
      'newsletter_option_fields' => array('name', 'newsletter_type'),
      'newsletter_option' => array('value'),
      'newsletter_links' => array('url', 'hash'),
      'forms' => array('name', 'body', 'settings', 'styles'),
    );


    foreach($tables as $table => $columns) {
      $query = "UPDATE `%s` SET %s WHERE %s";
      $columns_query = array();
      $where_query = array();
      foreach($columns as $column) {
        $columns_query[] = sprintf(
          '`%1$s` = @%1$s',
          $column
        );
        $where_query[] = sprintf(
          'char_length(%1$s) = length(@%1$s := convert(binary convert(%1$s using %2$s) using %3$s))',
          $column,
          $source_charset,
          $destination_charset
        );
      }
      $wpdb->query(sprintf(
        $query,
        $this->prefix . $table,
        implode(', ', $columns_query),
        implode(' AND ', $where_query)
      ));
    }
  }

}
