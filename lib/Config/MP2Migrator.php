<?php

namespace MailPoet\Config;

use MailPoet\Models\CustomField;
use MailPoet\Models\Form;
use MailPoet\Models\MappingToExternalEntities;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;
use MailPoet\Util\Notices\AfterMigrationNotice;
use MailPoet\Util\ProgressBar;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class MP2Migrator {
  const IMPORT_TIMEOUT_IN_SECONDS = 7200; // Timeout = 2 hours
  const CHUNK_SIZE = 10; // To import the data by batch

  const MIGRATION_COMPLETE_SETTING_KEY = 'mailpoet_migration_complete';
  const MIGRATION_STARTED_SETTING_KEY = 'mailpoet_migration_started';

  /** @var SettingsController */
  private $settings;

  /** @var Activator */
  private $activator;

  private $log_file;
  public $log_file_url;
  public $progressbar;
  private $segments_mapping = []; // Mapping between old and new segment IDs
  private $wp_users_segment;
  private $double_optin_enabled = true;
  private $mp2_campaign_table;
  private $mp2_custom_field_table;
  private $mp2_email_table;
  private $mp2_form_table;
  private $mp2_list_table;
  private $mp2_user_table;
  private $mp2_user_list_table;


  public function __construct(SettingsController $settings, Activator $activator) {
    $this->defineMP2Tables();
    $log_filename = 'mp2migration.log';
    $this->log_file = Env::$temp_path . '/' . $log_filename;
    $this->log_file_url = Env::$temp_url . '/' . $log_filename;
    $this->progressbar = new ProgressBar('mp2migration');
    $this->settings = $settings;
    $this->activator = $activator;
  }

  private function defineMP2Tables() {
    global $wpdb;

    $this->mp2_campaign_table = defined('MP2_CAMPAIGN_TABLE')
      ? MP2_CAMPAIGN_TABLE
      : $wpdb->prefix . 'wysija_campaign';

    $this->mp2_custom_field_table = defined('MP2_CUSTOM_FIELD_TABLE')
      ? MP2_CUSTOM_FIELD_TABLE
      : $wpdb->prefix . 'wysija_custom_field';

    $this->mp2_email_table = defined('MP2_EMAIL_TABLE')
      ? MP2_EMAIL_TABLE
      : $wpdb->prefix . 'wysija_email';

    $this->mp2_form_table = defined('MP2_FORM_TABLE')
      ? MP2_FORM_TABLE
      : $wpdb->prefix . 'wysija_form';

    $this->mp2_list_table = defined('MP2_LIST_TABLE')
      ? MP2_LIST_TABLE
      : $wpdb->prefix . 'wysija_list';

    $this->mp2_user_table = defined('MP2_USER_TABLE')
      ? MP2_USER_TABLE
      : $wpdb->prefix . 'wysija_user';

    $this->mp2_user_list_table = defined('MP2_USER_LIST_TABLE')
      ? MP2_USER_LIST_TABLE
      : $wpdb->prefix . 'wysija_user_list';
  }

  /**
   * Test if the migration is already started but is not completed
   *
   * @return boolean
   */
  public function isMigrationStartedAndNotCompleted() {
    return $this->settings->get(self::MIGRATION_STARTED_SETTING_KEY, false)
      && !$this->settings->get(self::MIGRATION_COMPLETE_SETTING_KEY, false);
  }

  /**
   * Test if the migration is needed
   *
   * @return boolean
   */
  public function isMigrationNeeded() {
    if ($this->settings->get(self::MIGRATION_COMPLETE_SETTING_KEY)) {
      return false;
    } else {
      return $this->tableExists($this->mp2_campaign_table); // Check if the MailPoet 2 tables exist
    }
  }

  /**
   * Store the "Skip import" choice
   *
   */
  public function skipImport() {
    $this->settings->set(self::MIGRATION_COMPLETE_SETTING_KEY, true);
  }

  /**
   * Test if a table exists
   *
   * @param string $table Table name
   * @return boolean
   */
  private function tableExists($table) {
    global $wpdb;

    try {
      $sql = "SHOW TABLES LIKE '{$table}'";
      $result = $wpdb->query($sql);
      return !empty($result);
    } catch (\Exception $e) {
      // Do nothing
    }

    return false;
  }

  /**
   * Initialize the migration page
   *
   */
  public function init() {
    if (!$this->settings->get(self::MIGRATION_STARTED_SETTING_KEY, false)) {
      $this->emptyLog();
      $this->progressbar->setTotalCount(0);
    }
    $this->enqueueScripts();
  }

  /**
   * Register the JavaScript for the admin area.
   *
   */
  private function enqueueScripts() {
    WPFunctions::get()->wpEnqueueScript('jquery-ui-progressbar');
  }

  /**
   * Write a message in the log file
   *
   * @param string $message
   */
  private function log($message) {
    file_put_contents($this->log_file, "$message\n", FILE_APPEND);
  }

  /**
   * Import the data from MailPoet 2
   *
   * @return string Result
   */
  public function import() {
    if (strpos(@ini_get('disable_functions'), 'set_time_limit') === false) {
      @set_time_limit(3600);
    }
    ob_start();
    $datetime = new \MailPoet\WP\DateTime();
    $this->log(sprintf('=== ' . mb_strtoupper(__('Start import', 'mailpoet'), 'UTF-8') . ' %s ===', $datetime->formatTime(time(), \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT)));
    $this->settings->set('import_stopped', false); // Reset the stop import action

    if (!$this->settings->get(self::MIGRATION_STARTED_SETTING_KEY, false)) {
      $this->eraseMP3Data();
      $this->settings->set(self::MIGRATION_STARTED_SETTING_KEY, true);
      $this->displayDataToMigrate();
    }

    $this->loadDoubleOptinSettings();

    $this->importSegments();
    $this->importCustomFields();
    $this->importSubscribers();
    $this->importForms();
    $this->importSettings();

    if (!$this->importStopped()) {
      $this->settings->set(self::MIGRATION_COMPLETE_SETTING_KEY, true);
      $this->log(mb_strtoupper(__('Import complete', 'mailpoet'), 'UTF-8'));
      $after_migration_notice = new AfterMigrationNotice();
      $after_migration_notice->enable();
    }

    $this->log(sprintf('=== ' . mb_strtoupper(__('End import', 'mailpoet'), 'UTF-8') . ' %s ===', $datetime->formatTime(time(), \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT)));
    $result = ob_get_contents();
    ob_clean();
    return $result;
  }

  /**
   * Empty the log file
   *
   */
  private function emptyLog() {
    file_put_contents($this->log_file, '');
  }

  /**
   * Erase all the MailPoet 3 data
   *
   */
  private function eraseMP3Data() {
    $this->activator->deactivate();
    $this->activator->activate();

    $this->deleteSegments();
    $this->resetMigrationCounters();
    $this->log(__("MailPoet data erased", 'mailpoet'));
  }

  /**
   * Reset the migration counters
   *
   */
  private function resetMigrationCounters() {
    $this->settings->set('last_imported_user_id', 0);
    $this->settings->set('last_imported_list_id', 0);
    $this->settings->set('last_imported_form_id', 0);
  }

  private function loadDoubleOptinSettings() {
    $encoded_option = WPFunctions::get()->getOption('wysija');
    $values = unserialize(base64_decode($encoded_option));
    if (isset($values['confirm_dbleoptin']) && $values['confirm_dbleoptin'] === '0') {
      $this->double_optin_enabled = false;
    }
  }

  /**
   * Delete the existing segments except the wp_users and woocommerce_users segments
   *
   */
  private function deleteSegments() {
    global $wpdb;

    $table = MP_SEGMENTS_TABLE;
    $wpdb->query("DELETE FROM {$table} WHERE type != '" . Segment::TYPE_WP_USERS . "' AND type != '" . Segment::TYPE_WC_USERS . "'");
  }

  /**
   * Stop the import
   *
   */
  public function stopImport() {
    $this->settings->set('import_stopped', true);
    $this->log(mb_strtoupper(__('Import stopped by user', 'mailpoet'), 'UTF-8'));
  }

  /**
   * Test if the import must stop
   *
   * @return boolean Import must stop or not
   */
  private function importStopped() {
    return $this->settings->get('import_stopped', false);
  }

  /**
   * Display the number of data to migrate
   *
   */
  private function displayDataToMigrate() {
    $data = $this->getDataToMigrateAndResetProgressBar();
    $this->log($data);
  }

  /**
   * Get the data to migrate
   *
   * @return string Data to migrate
   */
  private function getDataToMigrateAndResetProgressBar() {
    $result = '';
    $total_count = 0;

    $this->progressbar->setTotalCount(0);

    $result .= WPFunctions::get()->__('MailPoet 2 data found:', 'mailpoet') . "\n";

    // User Lists
    $users_lists_count = \ORM::for_table($this->mp2_list_table)->count();
    $total_count += $users_lists_count;
    $result .= sprintf(_n('%d subscribers list', '%d subscribers lists', $users_lists_count, 'mailpoet'), $users_lists_count) . "\n";

    // Users
    $users_count = \ORM::for_table($this->mp2_user_table)->count();
    $total_count += $users_count;
    $result .= sprintf(_n('%d subscriber', '%d subscribers', $users_count, 'mailpoet'), $users_count) . "\n";

    // Forms
    $forms_count = \ORM::for_table($this->mp2_form_table)->count();
    $total_count += $forms_count;
    $result .= sprintf(_n('%d form', '%d forms', $forms_count, 'mailpoet'), $forms_count) . "\n";

    $this->progressbar->setTotalCount($total_count);

    return $result;
  }

  /**
   * Import the subscribers segments
   *
   */
  private function importSegments() {
    $imported_segments_count = 0;
    if ($this->importStopped()) {
      $this->segments_mapping = $this->getImportedMapping('segments');
      return;
    }
    $this->log(__("Importing segments...", 'mailpoet'));
    do {
      if ($this->importStopped()) {
        break;
      }
      $lists = $this->getLists(self::CHUNK_SIZE);
      $lists_count = count($lists);

      if (is_array($lists)) {
        foreach ($lists as $list) {
          $segment = $this->importSegment($list);
          if (!empty($segment)) {
            $imported_segments_count++;
          }
        }
      }
      $this->progressbar->incrementCurrentCount($lists_count);
    } while (($lists != null) && ($lists_count > 0));

    $this->segments_mapping = $this->getImportedMapping('segments');

    $this->log(sprintf(_n("%d segment imported", "%d segments imported", $imported_segments_count, 'mailpoet'), $imported_segments_count));
  }

  /**
   * Get the Mailpoet 2 users lists
   *
   * @global object $wpdb
   * @param int $limit Number of users max
   * @return array Users Lists
   */
  private function getLists($limit) {
    global $wpdb;

    $last_id = intval($this->settings->get('last_imported_list_id', 0));
    $table = $this->mp2_list_table;
    $sql = "
      SELECT l.list_id, l.name, l.description, l.is_enabled, l.created_at
      FROM `$table` l
      WHERE l.list_id > '$last_id'
      ORDER BY l.list_id
      LIMIT $limit
      ";
    $lists = $wpdb->get_results($sql, ARRAY_A);

    return $lists;
  }

  /**
   * Import a segment
   *
   * @param array $list_data List data
   * @return Segment
   */
  private function importSegment($list_data) {
    $datetime = new \MailPoet\WP\DateTime();
    if ($list_data['is_enabled']) {
      $segment = Segment::createOrUpdate([
        'name' => $list_data['name'],
        'type' => 'default',
        'description' => !empty($list_data['description']) ? $list_data['description'] : '',
        'created_at' => $datetime->formatTime($list_data['created_at'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
      ]);
    } else {
      $segment = Segment::getWPSegment();
    }
    if (!empty($segment)) {
      // Map the segment with its old ID
      $mapping = new MappingToExternalEntities();
      $mapping->create([
        'old_id' => $list_data['list_id'],
        'type' => 'segments',
        'new_id' => $segment->id,
        'created_at' => $datetime->formatTime(time(), \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
      ]);
    }
    $this->settings->set('last_imported_list_id', $list_data['list_id']);
    return $segment;
  }

  /**
   * Import the custom fields
   *
   */
  private function importCustomFields() {
    $imported_custom_fields_count = 0;
    if ($this->importStopped()) {
      return;
    }
    $this->log(__("Importing custom fields...", 'mailpoet'));
    $custom_fields = $this->getCustomFields();

    foreach ($custom_fields as $custom_field) {
      $result = $this->importCustomField($custom_field);
      if (!empty($result)) {
        $imported_custom_fields_count++;
      }
    }

    $this->log(sprintf(_n("%d custom field imported", "%d custom fields imported", $imported_custom_fields_count, 'mailpoet'), $imported_custom_fields_count));
  }

  /**
   * Get the Mailpoet 2 custom fields
   *
   * @global object $wpdb
   * @return array Custom fields
   */
  private function getCustomFields() {
    global $wpdb;
    $custom_fields = [];

    $table = $this->mp2_custom_field_table;
    $sql = "
      SELECT cf.id, cf.name, cf.type, cf.required, cf.settings
      FROM `$table` cf
      ";
    $custom_fields = $wpdb->get_results($sql, ARRAY_A);

    return $custom_fields;
  }

  /**
   * Import a custom field
   *
   * @param array $custom_field MP2 custom field
   * @return CustomField
   */
  private function importCustomField($custom_field) {
    $data = [
      'id' => $custom_field['id'],
      'name' => $custom_field['name'],
      'type' => $this->mapCustomFieldType($custom_field['type']),
      'params' => $this->mapCustomFieldParams($custom_field['name'], unserialize($custom_field['settings'])),
    ];
    $custom_field = new CustomField();
    $custom_field->createOrUpdate($data);
    return $custom_field;
  }

  /**
   * Map the MailPoet 2 custom field type with the MailPoet custom field type
   *
   * @param string $mp2_type MP2 custom field type
   * @return string MP3 custom field type
   */
  private function mapCustomFieldType($mp2_type) {
    $type = '';
    switch ($mp2_type) {
      case 'input':
        $type = 'text';
        break;
      case 'list':
        $type = 'segment';
        break;
      default:
        $type = $mp2_type;
    }
    return $type;
  }

  /**
   * Map the MailPoet 2 custom field settings with the MailPoet custom field params
   *
   * @param string $name Parameter name
   * @param array $params MP2 parameters
   * @return array serialized MP3 custom field params
   */
  private function mapCustomFieldParams($name, $params) {
    if (!isset($params['label'])) {
      $params['label'] = $name;
    }
    if (isset($params['required'])) {
      $params['required'] = (bool)$params['required'];
    }
    if (isset($params['validate'])) {
      $params['validate'] = $this->mapCustomFieldValidateValue($params['validate']);
    }
    if (isset($params['date_order'])) { // Convert the date_order field
      switch ($params['date_type']) {

        case 'year_month':
          if (preg_match('/y$/i', $params['date_order'])) {
            $params['date_format'] = 'MM/YYYY';
          } else {
            $params['date_format'] = 'YYYY/MM';
          }
          break;

        case 'month';
          $params['date_format'] = 'MM';
          break;

        case 'year';
          $params['date_format'] = 'YYYY';
          break;

        default:
          $params['date_format'] = mb_strtoupper($params['date_order'], 'UTF-8');
      }
      unset($params['date_order']);
    }
    return $params;
  }

  /**
   * Map the validate value
   *
   * @param string $mp2_value MP2 value
   * @return string MP3 value
   */
  private function mapCustomFieldValidateValue($mp2_value) {
    $value = '';
    switch ($mp2_value) {
      case 'onlyLetterSp':
      case 'onlyLetterNumber':
        $value = 'alphanum';
        break;
      case 'onlyNumberSp':
        $value = 'number';
        break;
      case 'phone':
        $value = 'phone';
        break;
    }
    return $value;
  }

  /**
   * Import the subscribers
   *
   */
  private function importSubscribers() {
    $imported_subscribers_count = 0;
    if ($this->importStopped()) {
      return;
    }
    $this->log(__("Importing subscribers...", 'mailpoet'));
    $this->wp_users_segment = Segment::getWPSegment();
    do {
      if ($this->importStopped()) {
        break;
      }
      $users = $this->getUsers(self::CHUNK_SIZE);
      $users_count = count($users);

      if (is_array($users)) {
        foreach ($users as $user) {
          $subscriber = $this->importSubscriber($user);
          if (!empty($subscriber)) {
            $imported_subscribers_count++;
            $this->importSubscriberSegments($subscriber, $user['user_id']);
            $this->importSubscriberCustomFields($subscriber, $user);
          }
        }
      }
      $this->progressbar->incrementCurrentCount($users_count);
    } while (($users != null) && ($users_count > 0));

    $this->log(sprintf(_n("%d subscriber imported", "%d subscribers imported", $imported_subscribers_count, 'mailpoet'), $imported_subscribers_count));
  }

  /**
   * Get the Mailpoet 2 users
   *
   * @global object $wpdb
   * @param int $limit Number of users max
   * @return array Users
   */
  private function getUsers($limit) {
    global $wpdb;
    $last_id = intval($this->settings->get('last_imported_user_id', 0));
    $table = $this->mp2_user_table;
    $sql = "
      SELECT u.*
      FROM `$table` u
      WHERE u.user_id > '$last_id'
      ORDER BY u.user_id
      LIMIT $limit
      ";
    $users = $wpdb->get_results($sql, ARRAY_A);

    return $users;
  }

  /**
   * Import a subscriber
   *
   * @param array $user_data User data
   * @return Subscriber
   */
  private function importSubscriber($user_data) {
    $datetime = new \MailPoet\WP\DateTime();
    $subscriber = Subscriber::createOrUpdate([
      'wp_user_id' => !empty($user_data['wpuser_id']) ? $user_data['wpuser_id'] : null,
      'email' => $user_data['email'],
      'first_name' => $user_data['firstname'],
      'last_name' => $user_data['lastname'],
      'status' => $this->mapUserStatus($user_data['status']),
      'created_at' => $datetime->formatTime($user_data['created_at'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
      'subscribed_ip' => !empty($user_data['ip']) ? $user_data['ip'] : null,
      'confirmed_ip' => !empty($user_data['confirmed_ip']) ? $user_data['confirmed_ip'] : null,
      'confirmed_at' => !empty($user_data['confirmed_at']) ? $datetime->formatTime($user_data['confirmed_at'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT) : null,
    ]);
    $this->settings->set('last_imported_user_id', $user_data['user_id']);
    if (!empty($subscriber)) {
      // Map the subscriber with its old ID
      $mapping = new MappingToExternalEntities();
      $mapping->create([
        'old_id' => $user_data['user_id'],
        'type' => 'subscribers',
        'new_id' => $subscriber->id,
        'created_at' => $datetime->formatTime(time(), \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
      ]);
    }
    return $subscriber;
  }

  /**
   * Map the MailPoet 2 user status with MailPoet 3
   *
   * @param int $mp2_user_status MP2 user status
   * @return string MP3 user status
   */
  private function mapUserStatus($mp2_user_status) {


    switch ($mp2_user_status) {
      case 1:
        $status = 'subscribed';
        break;
      case -1:
        $status = 'unsubscribed';
        break;
      case 0:
      default:
        //if MP2 double-optin is disabled, we change "unconfirmed" status in MP2 to "confirmed" status in MP3.
        if (!$this->double_optin_enabled) {
          $status = 'subscribed';
        } else {
          $status = 'unconfirmed';
        }
    }
    return $status;
  }

  /**
   * Import the segments for a subscriber
   *
   * @param Subscriber $subscriber MP3 subscriber
   * @param int $user_id MP2 user ID
   */
  private function importSubscriberSegments($subscriber, $user_id) {
    $user_lists = $this->getUserLists($user_id);
    foreach ($user_lists as $user_list) {
      $this->importSubscriberSegment($subscriber->id, $user_list);
    }
  }

  /**
   * Get the lists for a user
   *
   * @global object $wpdb
   * @param int $user_id User ID
   * @return array Users Lists
   */
  private function getUserLists($user_id) {
    global $wpdb;

    $table = $this->mp2_user_list_table;
    $sql = "
      SELECT ul.list_id, ul.sub_date, ul.unsub_date
      FROM `$table` ul
      WHERE ul.user_id = '$user_id'
      ";
    $user_lists = $wpdb->get_results($sql, ARRAY_A);

    return $user_lists;
  }

  /**
   * Import a subscriber segment
   *
   * @param int $subscriber_id
   * @param array $user_list
   * @return SubscriberSegment
   */
  private function importSubscriberSegment($subscriber_id, $user_list) {
    $subscriber_segment = null;
    $datetime = new \MailPoet\WP\DateTime();
    if (isset($this->segments_mapping[$user_list['list_id']])) {
      $segment_id = $this->segments_mapping[$user_list['list_id']];
      $status = (($segment_id == $this->wp_users_segment->id) || empty($user_list['unsub_date'])) ? 'subscribed' : 'unsubscribed'; // the users belonging to the wp_users segment are always subscribed
      $data = [
        'subscriber_id' => $subscriber_id,
        'segment_id' => $segment_id,
        'status' => $status,
        'created_at' => $datetime->formatTime($user_list['sub_date'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
      ];
      $data['updated_at'] = !empty($user_list['unsub_date']) ? $datetime->formatTime($user_list['unsub_date'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT) : $data['created_at'];
      $subscriber_segment = new SubscriberSegment();
      $subscriber_segment->createOrUpdate($data);
    }
    return $subscriber_segment;
  }

  /**
   * Import the custom fields values for a subscriber
   *
   * @param Subscriber $subscriber MP3 subscriber
   * @param array $user MP2 user
   */
  private function importSubscriberCustomFields($subscriber, $user) {
    $imported_custom_fields = $this->getImportedCustomFields();
    foreach ($imported_custom_fields as $custom_field) {
      $custom_field_column = 'cf_' . $custom_field['id'];
      $this->importSubscriberCustomField($subscriber->id, $custom_field, $user[$custom_field_column]);
    }
  }

  /**
   * Get the imported custom fields
   *
   * @global object $wpdb
   * @return array Imported custom fields
   *
   */
  private function getImportedCustomFields() {
    global $wpdb;
    $table = MP_CUSTOM_FIELDS_TABLE;
    $sql = "
      SELECT cf.id, cf.name, cf.type
      FROM `$table` cf
      ";
    $custom_fields = $wpdb->get_results($sql, ARRAY_A);
    return $custom_fields;
  }

  /**
   * Import a subscriber custom field
   *
   * @param int $subscriber_id Subscriber ID
   * @param int $custom_field Custom field
   * @param string $custom_field_value Custom field value
   * @return SubscriberCustomField
   */
  private function importSubscriberCustomField($subscriber_id, $custom_field, $custom_field_value) {
    if ($custom_field['type'] == 'date') {
      $datetime = new \MailPoet\WP\DateTime();
      $value = $datetime->formatTime($custom_field_value, \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT); // Convert the date field
    } else {
      $value = $custom_field_value;
    }
    $data = [
      'subscriber_id' => $subscriber_id,
      'custom_field_id' => $custom_field['id'],
      'value' => isset($value) ? $value : '',
    ];
    $subscriber_custom_field = new SubscriberCustomField();
    $subscriber_custom_field->createOrUpdate($data);
    return $subscriber_custom_field;
  }

  /**
   * Get the mapping between the MP2 and the imported MP3 IDs
   *
   * @param string $model Model (segment,...)
   * @return array Mapping
   */
  public function getImportedMapping($model) {
    $mappings = [];
    $mapping_relations = MappingToExternalEntities::where('type', $model)->findArray();
    foreach ($mapping_relations as $relation) {
      $mappings[$relation['old_id']] = $relation['new_id'];
    }
    return $mappings;
  }

  /**
   * Import the forms
   *
   */
  private function importForms() {
    $imported_forms_count = 0;
    if ($this->importStopped()) {
      return;
    }
    $this->log(__("Importing forms...", 'mailpoet'));
    do {
      if ($this->importStopped()) {
        break;
      }
      $forms = $this->getForms(self::CHUNK_SIZE);
      $forms_count = count($forms);

      if (is_array($forms)) {
        foreach ($forms as $form) {
          $new_form = $this->importForm($form);
          if (!empty($new_form)) {
            $imported_forms_count++;
          }
        }
      }
      $this->progressbar->incrementCurrentCount($forms_count);
    } while (($forms != null) && ($forms_count > 0));

    $this->log(sprintf(_n("%d form imported", "%d forms imported", $imported_forms_count, 'mailpoet'), $imported_forms_count));
  }

  /**
   * Get the Mailpoet 2 forms
   *
   * @global object $wpdb
   * @param int $limit Number of forms max
   * @return array Forms
   */
  private function getForms($limit) {
    global $wpdb;

    $last_id = intval($this->settings->get('last_imported_form_id', 0));
    $table = $this->mp2_form_table;
    $sql = "
      SELECT f.*
      FROM `$table` f
      WHERE f.form_id > '$last_id'
      ORDER BY f.form_id
      LIMIT $limit
      ";
    $forms = $wpdb->get_results($sql, ARRAY_A);

    return $forms;
  }

  /**
   * Import a form
   *
   * @param array $form_data Form data
   * @return Form
   */
  private function importForm($form_data) {
    $serialized_data = base64_decode($form_data['data']);
    $data = unserialize($serialized_data);
    $settings = $data['settings'];
    $body = $data['body'];
    $segments = $this->getMappedSegmentIds($settings['lists']);
    $mp3_form_settings = [
      'on_success' => $settings['on_success'],
      'success_message' => $settings['success_message'],
      'segments_selected_by' => $settings['lists_selected_by'],
      'segments' => $segments,
    ];

    $mp3_form_body = [];
    foreach ($body as $field) {
      $type = $this->mapCustomFieldType($field['type']);
      if ($type == 'segment') {
          $field_id = 'segments';
      } else {
        switch ($field['field']) {
          case 'firstname':
            $field_id = 'first_name';
            break;
          case 'lastname':
            $field_id = 'last_name';
            break;
          default:
            $field_id = $field['field'];
        }
      }
      $field_id = preg_replace('/^cf_(\d+)$/', '$1', $field_id);
      $params = $this->mapCustomFieldParams($field['name'], $field['params']);
      if (isset($params['text'])) {
        $params['text'] = $this->replaceMP2Shortcodes(html_entity_decode($params['text']));
      }
      if (isset($params['values'])) {
        $params['values'] = $this->replaceListIds($params['values']);
      }
      $mp3_form_body[] = [
        'type' => $type,
        'name' => $field['name'],
        'id' => $field_id,
        'unique' => !in_array($field['type'], ['html', 'divider', 'email', 'submit']) ? "1" : "0",
        'static' => in_array($field_id, ['email', 'submit']) ? "1" : "0",
        'params' => $params,
        'position' => isset($field['position']) ? $field['position'] : '',
      ];
    }

    $form = Form::createOrUpdate([
      'name' => $form_data['name'],
      'body' => $mp3_form_body,
      'settings' => $mp3_form_settings,
    ]);
    $this->settings->set('last_imported_form_id', $form_data['form_id']);
    return $form;
  }

  /**
   * Get the MP3 segments IDs of the MP2 lists IDs
   *
   * @param array $mp2_list_ids
   */
  private function getMappedSegmentIds($mp2_list_ids) {
    $mp3_segment_ids = [];
    foreach ($mp2_list_ids as $list_id) {
      if (isset($this->segments_mapping[$list_id])) {
        $mp3_segment_ids[] = $this->segments_mapping[$list_id];
      }
    }
    return $mp3_segment_ids;
  }

  /**
   * Replace the MP2 shortcodes used in the textarea fields
   *
   * @param string $text Text
   * @return string Text
   */
  private function replaceMP2Shortcodes($text) {
    $text = str_replace('[total_subscribers]', '[mailpoet_subscribers_count]', $text);
    $text = preg_replace_callback(
      '/\[wysija_subscribers_count list_id="(.*)" \]/',
      function ($matches) {
        return $this->replaceMP2ShortcodesCallback($matches);
      },
      $text
    );
    return $text;
  }

  /**
   * Callback function for MP2 shortcodes replacement
   *
   * @param array $matches PREG matches
   * @return string Replacement
   */
  private function replaceMP2ShortcodesCallback($matches) {
    if (!empty($matches)) {
      $mp2_lists = explode(',', $matches[1]);
      $segments = $this->getMappedSegmentIds($mp2_lists);
      $segments_ids = implode(',', $segments);
      return '[mailpoet_subscribers_count segments=' . $segments_ids . ']';
    }
  }

  /**
   * Replace the MP2 list IDs by MP3 segment IDs
   *
   * @param array $values Field values
   * @return array Field values
   */
  private function replaceListIds($values) {
    $mp3_values = [];
    foreach ($values as $value) {
      $mp3_value = [];
      foreach ($value as $item => $item_value) {
        if (($item == 'list_id') && isset($this->segments_mapping[$item_value])) {
          $segment_id = $this->segments_mapping[$item_value];
          $mp3_value['id'] = $segment_id;
          $segment = Segment::findOne($segment_id);
          if ($segment) {
            $mp3_value['name'] = $segment->get('name');
          }
        } else {
          $mp3_value[$item] = $item_value;
        }
      }
      if (!empty($mp3_value)) {
        $mp3_values[] = $mp3_value;
      }
    }
    return $mp3_values;
  }

  /**
   * Import the settings
   *
   */
  private function importSettings() {
    $encoded_options = WPFunctions::get()->getOption('wysija');
    $options = unserialize(base64_decode($encoded_options));

    // Sender
    $sender = $this->settings->get('sender');
    $sender['name'] = isset($options['from_name']) ? $options['from_name'] : '';
    $sender['address'] = isset($options['from_email']) ? $options['from_email'] : '';
    $this->settings->set('sender', $sender);

    // Reply To
    $reply_to = $this->settings->get('reply_to');
    $reply_to['name'] = isset($options['replyto_name']) ? $options['replyto_name'] : '';
    $reply_to['address'] = isset($options['replyto_email']) ? $options['replyto_email'] : '';
    $this->settings->set('reply_to', $reply_to);

    // Bounce
    $bounce = $this->settings->get('bounce');
    $bounce['address'] = isset($options['bounce_email']) ? $options['bounce_email'] : '';
    $this->settings->set('bounce', $bounce);

    // Notification
    $notification = $this->settings->get('notification');
    $notification['address'] = isset($options['emails_notified']) ? $options['emails_notified'] : '';
    $this->settings->set('notification', $notification);

    // Subscribe
    $subscribe = $this->settings->get('subscribe');
    $subscribe['on_comment']['enabled'] = isset($options['commentform']) ? $options['commentform'] : '0';
    $subscribe['on_comment']['label'] = isset($options['commentform_linkname']) ? $options['commentform_linkname'] : '';
    $subscribe['on_comment']['segments'] = isset($options['commentform_lists']) ? $this->getMappedSegmentIds($options['commentform_lists']) : [];
    $subscribe['on_register']['enabled'] = isset($options['registerform']) ? $options['registerform'] : '0';
    $subscribe['on_register']['label'] = isset($options['registerform_linkname']) ? $options['registerform_linkname'] : '';
    $subscribe['on_register']['segments'] = isset($options['registerform_lists']) ? $this->getMappedSegmentIds($options['registerform_lists']) : [];
    $this->settings->set('subscribe', $subscribe);

    // Subscription
    $subscription = $this->settings->get('subscription');
    $subscription['pages']['unsubscribe'] = isset($options['unsubscribe_page']) ? $options['unsubscribe_page'] : '';
    $subscription['pages']['confirmation'] = isset($options['confirmation_page']) ? $options['confirmation_page'] : '';
    $subscription['pages']['manage'] = isset($options['subscriptions_page']) ? $options['subscriptions_page'] : '';
    $subscription['segments'] = isset($options['manage_subscriptions_lists']) ? $this->getMappedSegmentIds($options['manage_subscriptions_lists']) : [];
    $this->settings->set('subscription', $subscription);

    // Confirmation email
    $signup_confirmation = $this->settings->get('signup_confirmation');
    $signup_confirmation['enabled'] = isset($options['confirm_dbleoptin']) && ($options['confirm_dbleoptin'] == 0) ? 0 : 1;
    if (isset($options['confirm_email_id'])) {
      $confirm_email_id = $options['confirm_email_id'];
      $confirm_email = $this->getEmail($confirm_email_id);
      if (!empty($confirm_email)) {
        $signup_confirmation['from']['name'] = isset($confirm_email['from_name']) ? $confirm_email['from_name'] : '';
        $signup_confirmation['from']['address'] = isset($confirm_email['from_email']) ? $confirm_email['from_email'] : '';
        $signup_confirmation['reply_to']['name'] = isset($confirm_email['replyto_name']) ? $confirm_email['replyto_name'] : '';
        $signup_confirmation['reply_to']['address'] = isset($confirm_email['replyto_email']) ? $confirm_email['replyto_email'] : '';
        $signup_confirmation['subject'] = isset($confirm_email['subject']) ? $confirm_email['subject'] : '';
        $signup_confirmation['body'] = isset($confirm_email['body']) ? $confirm_email['body'] : '';
      }
    }
    $this->settings->set('signup_confirmation', $signup_confirmation);

    // Analytics
    $analytics = $this->settings->get('analytics');
    $analytics['enabled'] = isset($options['analytics']) ? $options['analytics'] : '';
    $this->settings->set('analytics', $analytics);

    // MTA
    $mta_group = isset($options['sending_method']) && ($options['sending_method'] == 'smtp') ? 'smtp' : 'website';
    $this->settings->set('mta_group', $mta_group);

    $mta = $this->settings->get('mta');
    $mta['method'] = (isset($options['smtp_host']) && ($options['smtp_host'] == 'smtp.sendgrid.net')) ? 'SendGrid' : (isset($options['sending_method']) && ($options['sending_method'] == 'smtp') ? 'SMTP' : 'PHPMail');
    $sending_emails_number = isset($options['sending_emails_number']) ? $options['sending_emails_number'] : '';
    $sending_emails_each = isset($options['sending_emails_each']) ? $options['sending_emails_each'] : '';
    $mta['frequency']['emails'] = $this->mapFrequencyEmails($sending_emails_number, $sending_emails_each);
    $mta['frequency']['interval'] = $this->mapFrequencyInterval($sending_emails_each);
    $mta['host'] = isset($options['smtp_host']) ? $options['smtp_host'] : '';
    $mta['port'] = isset($options['smtp_port']) ? $options['smtp_port'] : '';
    $mta['login'] = isset($options['smtp_login']) ? $options['smtp_login'] : '';
    $mta['password'] = isset($options['smtp_password']) ? $options['smtp_password'] : '';
    $mta['encryption'] = isset($options['smtp_secure']) ? $options['smtp_secure'] : '';
    $mta['authentication'] = !isset($options['smtp_auth']) ? '1' : '-1';
    $this->settings->set('mta', $mta);

    // SMTP Provider
    if ($mta['method'] == 'SendGrid') {
      $this->settings->set('smtp_provider', 'SendGrid');
    }

    // Installation date
    if (isset($options['installed_time'])) {
      $datetime = new \MailPoet\WP\DateTime();
      $installed_at = $datetime->formatTime($options['installed_time'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT);
      $this->settings->set('installed_at', $installed_at);
    }

    $this->log(__("Settings imported", 'mailpoet'));
  }

  /**
   * Get an email
   *
   * @global object $wpdb
   * @param int $email_id
   * @return array Email
   */
  private function getEmail($email_id) {
    global $wpdb;
    $email = [];

    $table = $this->mp2_email_table;
    $sql = "
      SELECT e.*
      FROM `$table` e
      WHERE e.email_id = '$email_id'
      ";
    $email = $wpdb->get_row($sql, ARRAY_A);

    return $email;
  }

  /**
   * Map the Email frequency interval
   *
   * @param string $interval_str Interval
   * @return string Interval
   */
  private function mapFrequencyInterval($interval_str) {
    switch ($interval_str) {
      case 'one_min':
        $interval = 1;
        break;

      case 'two_min':
        $interval = 2;
        break;

      case 'five_min':
        $interval = 5;
        break;

      case 'ten_min':
        $interval = 10;
        break;

      default:
        $interval = 15;
    }
    return (string)$interval;
  }

  /**
   * Map the Email frequency number
   *
   * @param int $emails_number Emails number
   * @param string $interval_str Interval
   * @return int Emails number
   */
  private function mapFrequencyEmails($emails_number, $interval_str) {
    if (empty($emails_number)) {
      $emails_number = 70;
    } else {
      switch ($interval_str) {
        case 'thirty_min':
          $emails_number /= 2;
          break;

        case 'hourly':
        case '':
          $emails_number /= 4;
          break;

        case 'two_hours':
          $emails_number /= 8;
          break;
      }
      $emails_number = round($emails_number);
    }
    return $emails_number;
  }
}
