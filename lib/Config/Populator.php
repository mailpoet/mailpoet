<?php
namespace MailPoet\Config;

use MailPoet\Config\PopulatorData\DefaultForm;
use MailPoet\Cron\CronTrigger;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\NewsletterTemplate;
use MailPoet\Models\Form;
use MailPoet\Models\Segment;
use MailPoet\Models\StatisticsForms;
use MailPoet\Models\Subscriber;
use MailPoet\Segments\WP;
use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\Source;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

class Populator {
  public $prefix;
  public $models;
  public $templates;
  private $default_segment;
  /** @var SettingsController */
  private $settings;
  const TEMPLATES_NAMESPACE = '\MailPoet\Config\PopulatorData\Templates\\';

  function __construct() {
    $this->settings = new SettingsController();
    $this->prefix = Env::$db_prefix;
    $this->models = array(
      'newsletter_option_fields',
      'newsletter_templates',
    );
    $this->templates = array(
      'NewsletterBlank1Column',
      'NewsletterBlank12Column',
      'NewsletterBlank121Column',
      'NewsletterBlank13Column',
      'SimpleText',
      'TakeAHike',
      'Faith',
      'NewsDay',
      'WorldCup',
      'FoodBox',
      'FestivalEvent',
      'RetroComputingMagazine',
      'Shoes',
      'PieceOfCake',
      'Coffee',
      'Drone',
      'Retro',
      'Hotels',
      'Music',
      'YogaStudio',
      'Charity',
      'FashionStore',

      'WelcomeBlank1Column',
      'WelcomeBlank12Column',
      'GiftWelcome',
      'Minimal',
      'Phone',
      'Sunglasses',
      'RealEstate',
      'AppWelcome',

      'PostNotificationsBlank1Column',
      'ModularStyleStories',
      'NotSoMedium',
      'RssSimpleNews',
      'WideStoryLayout',
      'ScienceWeekly',

      'WineCity',
      'DogFood',
      'Fitness',
      'KidsClothing',
      'Avocado',
      
      'FashionBlogA',
      'FashionShop',
      'LifestyleBlogA',
      'LifestyleBlogB',
      'NewspaperTraditional',
      'ClearNews',
      'IndustryConference',
      'BookStoreWithCoupon',
      'FlowersWithCoupon',
    );
  }

  function up() {
    $localizer = new Localizer();
    $localizer->forceLoadWebsiteLocaleText();

    array_map(array($this, 'populate'), $this->models);

    $this->createDefaultSegments();
    $this->createDefaultForm();
    $this->createDefaultSettings();
    $this->createMailPoetPage();
    $this->createSourceForSubscribers();
    $this->updateNewsletterCategories();
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

    $subscription = $this->settings->get('subscription.pages', array());
    if(empty($subscription)) {
      $this->settings->set('subscription.pages', array(
        'unsubscribe' => $mailpoet_page_id,
        'manage' => $mailpoet_page_id,
        'confirmation' => $mailpoet_page_id
      ));
    }
  }

  private function createDefaultSettings() {
    $current_user = wp_get_current_user();

    // set cron trigger option to default method
    if(!$this->settings->fetch(CronTrigger::SETTING_NAME)) {
      $this->settings->set(CronTrigger::SETTING_NAME, array(
        'method' => CronTrigger::DEFAULT_METHOD
      ));
    }

    // set default sender info based on current user
    $sender = array(
      'name' => $current_user->display_name,
      'address' => $current_user->user_email
    );

    // set default from name & address
    if(!$this->settings->fetch('sender')) {
      $this->settings->set('sender', $sender);
    }

    // enable signup confirmation by default
    if(!$this->settings->fetch('signup_confirmation')) {
      $this->settings->set('signup_confirmation', array(
        'enabled' => true,
        'from' => array(
          'name' => get_option('blogname'),
          'address' => get_option('admin_email')
        ),
        'reply_to' => $sender
      ));
    }

    // set installation date
    if(!$this->settings->fetch('installed_at')) {
      $this->settings->set('installed_at', date("Y-m-d H:i:s"));
    }

    // set reCaptcha settings
    $re_captcha = $this->settings->fetch('re_captcha');
    if(empty($re_captcha)) {
      $this->settings->set('re_captcha', array(
        'enabled' => false,
        'site_token' => '',
        'secret_token' => ''
      ));
    }

    $subscriber_email_notification = $this->settings->fetch(NewSubscriberNotificationMailer::SETTINGS_KEY);
    if(empty($subscriber_email_notification)) {
      $sender = $this->settings->fetch('sender', []);
      $this->settings->set('subscriber_email_notification', [
        'enabled' => true,
        'address' => isset($sender['address'])? $sender['address'] : null,
      ]);
    }

    $stats_notifications = $this->settings->fetch('stats_notifications');
    if(empty($stats_notifications)) {
      $sender = $this->settings->fetch('sender', []);
      $this->settings->set('stats_notifications', [
        'enabled' => true,
        'address' => isset($sender['address'])? $sender['address'] : null,
      ]);
    }

    // reset mailer log
    MailerLog::resetMailerLog();
  }

  private function createDefaultSegments() {
    // WP Users segment
    Segment::getWPSegment();
    // WooCommerce customers segment
    Segment::getWooCommerceSegment();

    // Synchronize WP Users
    WP::synchronizeUsers();

    // Default segment
    if(Segment::where('type', 'default')->count() === 0) {
      $this->default_segment = Segment::create();
      $this->default_segment->hydrate([
        'name' => __('My First List', 'mailpoet'),
        'description' =>
          __('This list is automatically created when you install MailPoet.', 'mailpoet')
      ]);
      $this->default_segment->save();
    }
  }

  private function createDefaultForm() {
    if(Form::count() === 0) {
      $factory = new DefaultForm();
      if(!$this->default_segment) {
        $this->default_segment = Segment::where('type', 'default')->orderByAsc('id')->limit(1)->findOne();
      }
      Form::createOrUpdate([
        'name' => $factory->getName(),
        'body' => serialize($factory->getBody()),
        'settings' => serialize($factory->getSettings($this->default_segment)),
        'styles' => $factory->getStyles(),
      ]);
    }
  }

  protected function newsletterOptionFields() {
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

  protected function newsletterTemplates() {
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

  protected function populate($model) {
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

    $conditions = array_map(function($key) {
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

  private function createSourceForSubscribers() {
    Subscriber::rawExecute(
      ' UPDATE LOW_PRIORITY `' . Subscriber::$_table . '` subscriber ' .
      ' JOIN `' . StatisticsForms::$_table . '` stats ON stats.subscriber_id=subscriber.id ' .
      ' SET `source` = "' . Source::FORM . '"' .
      ' WHERE `source` = "' . Source::UNKNOWN . '"'
    );
    Subscriber::rawExecute(
      'UPDATE LOW_PRIORITY `' . Subscriber::$_table . '`' .
      ' SET `source` = "' . Source::WORDPRESS_USER . '"' .
      ' WHERE `source` = "' . Source::UNKNOWN . '"' .
      ' AND `wp_user_id` IS NOT NULL'
    );
    Subscriber::rawExecute(
      'UPDATE LOW_PRIORITY `' . Subscriber::$_table . '`' .
      ' SET `source` = "' . Source::WOOCOMMERCE_USER . '"' .
      ' WHERE `source` = "' . Source::UNKNOWN . '"' .
      ' AND `is_woocommerce_user` = 1'
    );
  }

  private function updateNewsletterCategories() {
    global $wpdb;
    // perform once for versions below or equal to 3.14.0
    if(version_compare($this->settings->get('db_version', '3.14.1'), '3.14.0', '>')) {
      return false;
    }
    $query = "UPDATE `%s` SET categories = REPLACE(REPLACE(categories, ',\"blank\"', ''), ',\"sample\"', ',\"all\"')";
    $wpdb->query(sprintf(
      $query,
      NewsletterTemplate::$_table
    ));
    return true;
  }
}
