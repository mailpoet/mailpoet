<?php
namespace MailPoet\Config;

use Carbon\Carbon;
use MailPoet\Config\PopulatorData\DefaultForm;
use MailPoet\Cron\CronTrigger;
use MailPoet\Cron\Workers\AuthorizedSendingEmailsCheck;
use MailPoet\Cron\Workers\Beamer;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\NewsletterTemplate;
use MailPoet\Models\Form;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsForms;
use MailPoet\Models\Subscriber;
use MailPoet\Models\UserFlag;
use MailPoet\Models\Setting;
use MailPoet\Segments\WP;
use MailPoet\Services\Bridge;
use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\Source;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

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
    $this->models = [
      'newsletter_option_fields',
      'newsletter_templates',
    ];
    $this->templates = [
      'WelcomeBlank1Column',
      'WelcomeBlank12Column',
      'GiftWelcome',
      'Minimal',
      'Phone',
      'Sunglasses',
      'RealEstate',
      'AppWelcome',
      'FoodBox',
      'Poet',
      'PostNotificationsBlank1Column',
      'ModularStyleStories',
      'RssSimpleNews',
      'NotSoMedium',
      'WideStoryLayout',
      'IndustryConference',
      'ScienceWeekly',
      'NewspaperTraditional',
      'ClearNews',
      'DogFood',
      'KidsClothing',
      'RockBand',
      'WineCity',
      'Fitness',
      'Motor',
      'Avocado',
      'BookStoreWithCoupon',
      'FlowersWithCoupon',
      'NewsletterBlank1Column',
      'NewsletterBlank12Column',
      'NewsletterBlank121Column',
      'NewsletterBlank13Column',
      'SimpleText',
      'TakeAHike',
      'NewsDay',
      'WorldCup',
      'FestivalEvent',
      'RetroComputingMagazine',
      'Shoes',
      'Music',
      'Hotels',
      'PieceOfCake',
      'BuddhistTemple',
      'Mosque',
      'Synagogue',
      'Faith',
      'College',
      'RenewableEnergy',
      'PrimarySchool',
      'ComputerRepair',
      'YogaStudio',
      'Retro',
      'Charity',
      'CityLocalNews',
      'Coffee',
      'Vlogger',
      'Birds',
      'Engineering',
      'BrandingAgencyNews',
      'WordPressTheme',
      'Drone',
      'FashionBlog',
      'FashionStore',
      'FashionBlogA',
      'Photography',
      'JazzClub',
      'Guitarist',
      'HealthyFoodBlog',
      'Software',
      'LifestyleBlogA',
      'FashionShop',
      'LifestyleBlogB',
      'Painter',
      'FarmersMarket',
    ];
  }

  function up() {
    $localizer = new Localizer();
    $localizer->forceLoadWebsiteLocaleText();

    array_map([$this, 'populate'], $this->models);

    $this->createDefaultSegments();
    $this->createDefaultForm();
    $this->createDefaultSettings();
    $this->createDefaultUsersFlags();
    $this->createMailPoetPage();
    $this->createSourceForSubscribers();
    $this->updateNewsletterCategories();
    $this->updateMetaFields();
    $this->scheduleInitialInactiveSubscribersCheck();
    $this->scheduleAuthorizedSendingEmailsCheck();
    $this->initLastAnnouncementDate();
    // Will be uncommented on task [MAILPOET-1998]
    // $this->updateFormsSuccessMessages();
  }

  private function createMailPoetPage() {
    $pages = WPFunctions::get()->getPosts([
      'posts_per_page' => 1,
      'orderby' => 'date',
      'order' => 'DESC',
      'post_type' => 'mailpoet_page',
    ]);

    $page = null;
    if (!empty($pages)) {
      $page = array_shift($pages);
      if (strpos($page->post_content, '[mailpoet_page]') === false) {
        $page = null;
      }
    }

    if ($page === null) {
      $mailpoet_page_id = Pages::createMailPoetPage();
    } else {
      $mailpoet_page_id = (int)$page->ID;
    }

    $subscription = $this->settings->get('subscription.pages', []);
    if (empty($subscription)) {
      $this->settings->set('subscription.pages', [
        'unsubscribe' => $mailpoet_page_id,
        'manage' => $mailpoet_page_id,
        'confirmation' => $mailpoet_page_id,
      ]);
    }
  }

  private function createDefaultSettings() {
    $current_user = WPFunctions::get()->wpGetCurrentUser();
    $settings_db_version = $this->settings->fetch('db_version');

    // set cron trigger option to default method
    if (!$this->settings->fetch(CronTrigger::SETTING_NAME)) {
      $this->settings->set(CronTrigger::SETTING_NAME, [
        'method' => CronTrigger::DEFAULT_METHOD,
      ]);
    }

    // set default sender info based on current user
    $sender = [
      'name' => $current_user->display_name,
      'address' => $current_user->user_email,
    ];

    // set default from name & address
    if (!$this->settings->fetch('sender')) {
      $this->settings->set('sender', $sender);
    }

    // enable signup confirmation by default
    if (!$this->settings->fetch('signup_confirmation')) {
      $this->settings->set('signup_confirmation', [
        'enabled' => true,
        'from' => [
          'name' => WPFunctions::get()->getOption('blogname'),
          'address' => WPFunctions::get()->getOption('admin_email'),
        ],
        'reply_to' => $sender,
      ]);
    }

    // set installation date
    if (!$this->settings->fetch('installed_at')) {
      $this->settings->set('installed_at', date("Y-m-d H:i:s"));
    }

    // set reCaptcha settings
    $re_captcha = $this->settings->fetch('re_captcha');
    if (empty($re_captcha)) {
      $this->settings->set('re_captcha', [
        'enabled' => false,
        'site_token' => '',
        'secret_token' => '',
      ]);
    }

    $subscriber_email_notification = $this->settings->fetch(NewSubscriberNotificationMailer::SETTINGS_KEY);
    if (empty($subscriber_email_notification)) {
      $sender = $this->settings->fetch('sender', []);
      $this->settings->set('subscriber_email_notification', [
        'enabled' => true,
        'address' => isset($sender['address']) ? $sender['address'] : null,
      ]);
    }

    $stats_notifications = $this->settings->fetch('stats_notifications');
    if (empty($stats_notifications)) {
      $sender = $this->settings->fetch('sender', []);
      $this->settings->set('stats_notifications', [
        'enabled' => true,
        'address' => isset($sender['address']) ? $sender['address'] : null,
      ]);
    }

    $woocommerce_optin_on_checkout = $this->settings->fetch('woocommerce.optin_on_checkout');
    if (empty($woocommerce_optin_on_checkout)) {
      $this->settings->set('woocommerce.optin_on_checkout', [
        'enabled' => empty($settings_db_version), // enable on new installs only
        'message' => WPFunctions::get()->_x('Yes, I would like to be added to your mailing list', "default email opt-in message displayed on checkout page for ecommerce websites"),
      ]);
    }

    // reset mailer log
    MailerLog::resetMailerLog();
  }

  private function createDefaultUsersFlags() {
    $last_announcement_seen = $this->settings->fetch('last_announcement_seen');
    if (!empty($last_announcement_seen)) {
      foreach ($last_announcement_seen as $user_id => $value) {
        UserFlag::createOrUpdate([
          'user_id' => $user_id,
          'name' => 'last_announcement_seen',
          'value' => $value,
        ]);
      }
      $this->settings->delete('last_announcement_seen');
    }

    $prefix = 'user_seen_editor_tutorial';
    $prefix_length = strlen($prefix);
    $users_seen_editor_tutorial = Setting::whereLike('name', $prefix . '%')->findMany();
    if (!empty($users_seen_editor_tutorial)) {
      foreach ($users_seen_editor_tutorial as $setting) {
        $user_id = substr($setting->name, $prefix_length);
        UserFlag::createOrUpdate([
          'user_id' => $user_id,
          'name' => 'editor_tutorial_seen',
          'value' => $setting->value,
        ]);
      }
      Setting::whereLike('name', $prefix . '%')->deleteMany();
    }
  }

  private function createDefaultSegments() {
    // WP Users segment
    Segment::getWPSegment();
    // WooCommerce customers segment
    Segment::getWooCommerceSegment();

    // Synchronize WP Users
    WP::synchronizeUsers();

    // Default segment
    if (Segment::where('type', 'default')->count() === 0) {
      $this->default_segment = Segment::create();
      $this->default_segment->hydrate([
        'name' => WPFunctions::get()->__('My First List', 'mailpoet'),
        'description' =>
          WPFunctions::get()->__('This list is automatically created when you install MailPoet.', 'mailpoet'),
      ]);
      $this->default_segment->save();
    }
  }

  private function createDefaultForm() {
    if (Form::count() === 0) {
      $factory = new DefaultForm();
      if (!$this->default_segment) {
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
    $option_fields = [
      [
        'name' => 'isScheduled',
        'newsletter_type' => 'standard',
      ],
      [
        'name' => 'scheduledAt',
        'newsletter_type' => 'standard',
      ],
      [
        'name' => 'event',
        'newsletter_type' => 'welcome',
      ],
      [
        'name' => 'segment',
        'newsletter_type' => 'welcome',
      ],
      [
        'name' => 'role',
        'newsletter_type' => 'welcome',
      ],
      [
        'name' => 'afterTimeNumber',
        'newsletter_type' => 'welcome',
      ],
      [
        'name' => 'afterTimeType',
        'newsletter_type' => 'welcome',
      ],
      [
        'name' => 'intervalType',
        'newsletter_type' => 'notification',
      ],
      [
        'name' => 'timeOfDay',
        'newsletter_type' => 'notification',
      ],
      [
        'name' => 'weekDay',
        'newsletter_type' => 'notification',
      ],
      [
        'name' => 'monthDay',
        'newsletter_type' => 'notification',
      ],
      [
        'name' => 'nthWeekDay',
        'newsletter_type' => 'notification',
      ],
      [
        'name' => 'schedule',
        'newsletter_type' => 'notification',
      ],
    ];

    return [
      'rows' => $option_fields,
      'identification_columns' => [
        'name',
        'newsletter_type',
      ],
    ];
  }

  protected function newsletterTemplates() {
    $templates = [];
    foreach ($this->templates as $template) {
      $template = self::TEMPLATES_NAMESPACE . $template;
      $template = new $template(Env::$assets_url);
      $templates[] = $template->get();
    }
    return [
      'rows' => $templates,
      'identification_columns' => [
        'name',
      ],
      'remove_duplicates' => true,
    ];
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

    foreach ($rows as $row) {
      $existence_comparison_fields = array_intersect_key(
        $row,
        $identification_columns
      );

      if (!$this->rowExists($table, $existence_comparison_fields)) {
        $this->insertRow($table, $row);
      } else {
        if ($remove_duplicates) {
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

    $conditions = ['1=1'];
    $values = [];
    foreach ($where as $field => $value) {
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
    if (version_compare($this->settings->get('db_version', '3.14.1'), '3.14.0', '>')) {
      return false;
    }
    $query = "UPDATE `%s` SET categories = REPLACE(REPLACE(categories, ',\"blank\"', ''), ',\"sample\"', ',\"all\"')";
    $wpdb->query(sprintf(
      $query,
      NewsletterTemplate::$_table
    ));
    return true;
  }

  private function updateMetaFields() {
    global $wpdb;
    // perform once for versions below or equal to 3.26.0
    if (version_compare($this->settings->get('db_version', '3.26.1'), '3.26.0', '>')) {
      return false;
    }
    $tables = [ScheduledTask::$_table, SendingQueue::$_table];
    foreach ($tables as $table) {
      $query = "UPDATE `%s` SET meta = NULL WHERE meta = 'null'";
      $wpdb->query(sprintf($query, $table));
    }
    return true;
  }

  private function scheduleInitialInactiveSubscribersCheck() {
    $this->scheduleTask(
      InactiveSubscribers::TASK_TYPE,
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addHour()
    );
  }

  private function scheduleAuthorizedSendingEmailsCheck() {
    if (!Bridge::isMPSendingServiceEnabled()) {
      return;
    }
    $this->scheduleTask(
      AuthorizedSendingEmailsCheck::TASK_TYPE,
      Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))
    );
  }

  private function initLastAnnouncementDate() {
    if (!$this->settings->get('last_announcement_date')) {
      $beamer = new Beamer($this->settings);
      $beamer->setLastAnnouncementDate();
    }
  }

  private function scheduleTask($type, $datetime) {
    $task = ScheduledTask::where('type', $type)
      ->whereRaw('status = ? OR status IS NULL', [ScheduledTask::STATUS_SCHEDULED])
      ->findOne();
    if ($task) {
      return true;
    }
    $task = ScheduledTask::create();
    $task->type = $type;
    $task->status = ScheduledTask::STATUS_SCHEDULED;
    $task->scheduled_at = $datetime;
    $task->save();
  }

  /**
   * Remove this comment when this private function is actually used
   * @phpcsSuppress SlevomatCodingStandard.Classes.UnusedPrivateElements
   */
  private function updateFormsSuccessMessages() {
    if (version_compare($this->settings->get('db_version', '3.23.2'), '3.23.1', '>')) {
      return;
    }
    Form::updateSuccessMessages();
  }
}
