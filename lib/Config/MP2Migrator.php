<?php

namespace MailPoet\Config;

use MailPoet\Util\ProgressBar;
use MailPoet\Models\Setting;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\CustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\MappingToExternalEntities;
use MailPoet\Config\Activator;

if(!defined('ABSPATH')) exit;

class MP2Migrator {

  const IMPORT_TIMEOUT_IN_SECONDS = 7200; // Timeout = 2 hours
  const CHUNK_SIZE = 10; // To import the data by batch

  private $log_file;
  public $log_file_url;
  public $progressbar;
  private $segments_mapping = array(); // Mapping between old and new segment IDs

  public function __construct() {
    $this->defineMP2Tables();
    $log_filename = 'mp2migration.log';
    $this->log_file = Env::$temp_path . '/' . $log_filename;
    $this->log_file_url = Env::$temp_url . '/' . $log_filename;
    $this->progressbar = new ProgressBar('mp2migration');
  }

  private function defineMP2Tables() {
    global $wpdb;

    if(!defined('MP2_CAMPAIGN_TABLE')) {
      define('MP2_CAMPAIGN_TABLE', $wpdb->prefix . 'wysija_campaign');
    }
    if(!defined('MP2_CUSTOM_FIELD_TABLE')) {
      define('MP2_CUSTOM_FIELD_TABLE', $wpdb->prefix . 'wysija_custom_field');
    }
    if(!defined('MP2_EMAIL_TABLE')) {
      define('MP2_EMAIL_TABLE', $wpdb->prefix . 'wysija_email');
    }
    if(!defined('MP2_FORM_TABLE')) {
      define('MP2_FORM_TABLE', $wpdb->prefix . 'wysija_form');
    }
    if(!defined('MP2_LIST_TABLE')) {
      define('MP2_LIST_TABLE', $wpdb->prefix . 'wysija_list');
    }
    if(!defined('MP2_USER_TABLE')) {
      define('MP2_USER_TABLE', $wpdb->prefix . 'wysija_user');
    }
    if(!defined('MP2_USER_LIST_TABLE')) {
      define('MP2_USER_LIST_TABLE', $wpdb->prefix . 'wysija_user_list');
    }
  }

  /**
   * Test if the migration is needed
   *
   * @return boolean
   */
  public function isMigrationNeeded() {
    if(Setting::getValue('mailpoet_migration_complete')) {
      return false;
    } else {
      return $this->tableExists(MP2_CAMPAIGN_TABLE); // Check if the MailPoet 2 tables exist
    }
  }

  /**
   * Store the "Skip import" choice
   *
   */
  public function skipImport() {
    Setting::setValue('mailpoet_migration_complete', true);
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
    } catch (Exception $e) {
      // Do nothing
    }

    return false;
  }

  /**
   * Initialize the migration page
   *
   */
  public function init() {
    $this->enqueueScripts();
  }

  /**
   * Register the JavaScript for the admin area.
   *
   */
  private function enqueueScripts() {
    wp_enqueue_script('jquery-ui-progressbar');
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
    set_time_limit(self::IMPORT_TIMEOUT_IN_SECONDS);
    ob_start();
    $this->emptyLog();
    $datetime = new \MailPoet\WP\DateTime();
    $this->log(sprintf('=== ' . __('START IMPORT', 'mailpoet') . ' %s ===', $datetime->formatTime(time(), \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT)));
    Setting::setValue('import_stopped', false); // Reset the stop import action

    if(!Setting::getValue('mailpoet_migration_started', false)) {
      $this->eraseMP3Data();
      Setting::setValue('mailpoet_migration_started', true);
      $this->displayDataToMigrate();
    }

    $this->importSegments();
    $this->segments_mapping = $this->getImportedMapping('segments');
    $this->importCustomFields();
    $this->importSubscribers();

    Setting::setValue('mailpoet_migration_complete', true);

    $this->log(__('IMPORT COMPLETE', 'mailpoet'));
    $this->log(sprintf('=== ' . __('END IMPORT', 'mailpoet') . ' %s ===', $datetime->formatTime(time(), \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT)));
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
    Activator::deactivate();
    Activator::activate();

    $this->deleteSegments();
    $this->resetMigrationCounters();
    $this->log(__("MailPoet data erased", 'mailpoet'));
  }

  /**
   * Reset the migration counters
   *
   */
  private function resetMigrationCounters() {
    Setting::setValue('last_imported_user_id', 0);
    Setting::setValue('last_imported_list_id', 0);
  }

  /**
   * Delete the existing segments except the wp_users segment
   *
   */
  private function deleteSegments() {
    global $wpdb;

    $table = MP_SEGMENTS_TABLE;
    $wpdb->query("DELETE FROM {$table} WHERE type != '" . Segment::TYPE_WP_USERS . "'");
  }

  /**
   * Stop the import
   *
   */
  public function stopImport() {
    Setting::setValue('import_stopped', true);
    $this->log(__('IMPORT STOPPED BY USER', 'mailpoet'));
  }

  /**
   * Test if the import must stop
   *
   * @return boolean Import must stop or not
   */
  private function importStopped() {
    return Setting::getValue('import_stopped', false);
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

    $result .= __('MailPoet 2 data found:', 'mailpoet') . "\n";

    // User Lists
    $users_lists_count = \ORM::for_table(MP2_LIST_TABLE)->count();
    $total_count += $users_lists_count;
    $result .= sprintf(_n('%d subscribers list', '%d subscribers lists', $users_lists_count, 'mailpoet'), $users_lists_count) . "\n";

    // Users
    $users_count = \ORM::for_table(MP2_USER_TABLE)->count();
    $total_count += $users_count;
    $result .= sprintf(_n('%d subscriber', '%d subscribers', $users_count, 'mailpoet'), $users_count) . "\n";

    // TODO to reactivate during the next phases
    /*
    // Emails
    $emails_count = \ORM::for_table(MP2_EMAIL_TABLE)->count();
    $total_count += $emails_count;
    $result .= sprintf(_n('%d newsletter', '%d newsletters', $emails_count, 'mailpoet'), $emails_count) . "\n";

    // Forms
    $forms_count = \ORM::for_table(MP2_FORM_TABLE)->count();
    $total_count += $forms_count;
    $result .= sprintf(_n('%d form', '%d forms', $forms_count, 'mailpoet'), $forms_count) . "\n";
    */

    $this->progressbar->setTotalCount($total_count);

    return $result;
  }

  /**
   * Import the subscribers segments
   *
   */
  private function importSegments() {
    $imported_segments_count = 0;
    if($this->importStopped()) {
      return;
    }
    $this->log(__("Importing segments...", 'mailpoet'));
    do {
      if($this->importStopped()) {
        break;
      }
      $lists = $this->getLists(self::CHUNK_SIZE);
      $lists_count = count($lists);

      if(is_array($lists)) {
        foreach($lists as $list) {
          $segment = $this->importSegment($list);
          if(!empty($segment)) {
            $imported_segments_count++;
          }
        }
      }
      $this->progressbar->incrementCurrentCount($lists_count);
    } while(($lists != null) && ($lists_count > 0));

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
    $lists = array();

    $last_id = Setting::getValue('last_imported_list_id', 0);
    $table = MP2_LIST_TABLE;
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
    if($list_data['is_enabled']) {
      $segment = Segment::createOrUpdate(array(
        'name' => $list_data['name'],
        'type' => 'default',
        'description' => $list_data['description'],
        'created_at' => $datetime->formatTime($list_data['created_at'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
      ));
    } else {
      $segment = Segment::getWPSegment();
    }
     if(!empty($segment)) {
      // Map the segment with its old ID
      $mapping = new MappingToExternalEntities();
      $mapping->create(array(
        'old_id' => $list_data['list_id'],
        'type' => 'segments',
        'new_id' => $segment->id,
        'created_at' => $datetime->formatTime(time(), \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
      ));
    }
    Setting::setValue('last_imported_list_id', $list_data['list_id']);
    return $segment;
  }

  /**
   * Import the custom fields
   *
   */
  private function importCustomFields() {
    $imported_custom_fields_count = 0;
    if($this->importStopped()) {
      return;
    }
    $this->log(__("Importing custom fields...", 'mailpoet'));
    $custom_fields = $this->getCustomFields();

    foreach($custom_fields as $custom_field) {
      $result = $this->importCustomField($custom_field);
      if(!empty($result)) {
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
    $custom_fields = array();

    $table = MP2_CUSTOM_FIELD_TABLE;
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
    $data = array(
      'id' => $custom_field['id'],
      'name' => $custom_field['name'],
      'type' => $this->mapCustomFieldType($custom_field['type']),
      'params' => $this->mapCustomFieldParams($custom_field),
    );
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
    switch($mp2_type) {
      case 'input':
        $type = 'text';
        break;
      default:
        $type = $mp2_type;
    }
    return $type;
  }

  /**
   * Map the MailPoet 2 custom field settings with the MailPoet custom field params
   *
   * @param array $custom_field MP2 custom field
   * @return string serialized MP3 custom field params
   */
  private function mapCustomFieldParams($custom_field) {
    $params = unserialize($custom_field['settings']);
    $params['label'] = $custom_field['name'];
    if(isset($params['validate'])) {
      $params['validate'] = $this->mapCustomFieldValidateValue($params['validate']);
    }
    if(isset($params['date_order'])) { // Convert the date_order field
      $params['date_format'] = strtoupper($params['date_order']);
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
    switch($mp2_value) {
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
    if($this->importStopped()) {
      return;
    }
    $this->log(__("Importing subscribers...", 'mailpoet'));
    do {
      if($this->importStopped()) {
        break;
      }
      $users = $this->getUsers(self::CHUNK_SIZE);
      $users_count = count($users);

      if(is_array($users)) {
        foreach($users as $user) {
          $subscriber = $this->importSubscriber($user);
          if(!empty($subscriber)) {
            $imported_subscribers_count++;
            $this->importSubscriberSegments($subscriber, $user['user_id']);
            $this->importSubscriberCustomFields($subscriber, $user);
          }
        }
      }
      $this->progressbar->incrementCurrentCount($users_count);
    } while(($users != null) && ($users_count > 0));

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
    $users = array();

    $last_id = Setting::getValue('last_imported_user_id', 0);
    $table = MP2_USER_TABLE;
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
    $subscriber = Subscriber::createOrUpdate(array(
      'wp_user_id' => !empty($user_data['wpuser_id']) ? $user_data['wpuser_id'] : null,
      'email' => $user_data['email'],
      'first_name' => $user_data['firstname'],
      'last_name' => $user_data['lastname'],
      'status' => $this->mapUserStatus($user_data['status']),
      'created_at' => $datetime->formatTime($user_data['created_at'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
      'subscribed_ip' => !empty($user_data['ip']) ? $user_data['ip'] : null,
      'confirmed_ip' => !empty($user_data['confirmed_ip']) ? $user_data['confirmed_ip'] : null,
      'confirmed_at' => !empty($user_data['confirmed_at']) ? $datetime->formatTime($user_data['confirmed_at'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT) : null,
    ));
    Setting::setValue('last_imported_user_id', $user_data['user_id']);
    if(!empty($subscriber)) {
      // Map the subscriber with its old ID
      $mapping = new MappingToExternalEntities();
      $mapping->create(array(
        'old_id' => $user_data['user_id'],
        'type' => 'subscribers',
        'new_id' => $subscriber->id,
        'created_at' => $datetime->formatTime(time(), \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
      ));
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
    switch($mp2_user_status) {
      case 1:
        $status = 'subscribed';
        break;
      case -1:
        $status = 'unsubscribed';
        break;
      case 0:
      default:
        $status = 'unconfirmed';
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
    foreach($user_lists as $user_list) {
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
    $user_lists = array();

    $table = MP2_USER_LIST_TABLE;
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
    if(isset($this->segments_mapping[$user_list['list_id']])) {
      $segment_id = $this->segments_mapping[$user_list['list_id']];
      $data = array(
        'subscriber_id' => $subscriber_id,
        'segment_id' => $segment_id,
        'status' => empty($user_list['unsub_date']) ? 'subscribed' : 'unsubscribed',
        'created_at' => $datetime->formatTime($user_list['sub_date'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
        'updated_at' => !empty($user_list['unsub_date']) ? $datetime->formatTime($user_list['unsub_date'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT) : null,
      );
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
    foreach($imported_custom_fields as $custom_field) {
      $custom_field_column = 'cf_' . $custom_field['id'];
      if(isset($custom_field_column)) {
        $this->importSubscriberCustomField($subscriber->id, $custom_field, $user[$custom_field_column]);
      }
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
    if($custom_field['type'] == 'date') {
      $datetime = new \MailPoet\WP\DateTime();
      $value = $datetime->formatTime($custom_field_value, \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT); // Convert the date field
    } else {
      $value = $custom_field_value;
    }
    $data = array(
      'subscriber_id' => $subscriber_id,
      'custom_field_id' => $custom_field['id'],
      'value' => isset($value) ? $value : '',
    );
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
    $mappings = array();
    $mapping_relations = MappingToExternalEntities::where('type', $model)->findArray();
    foreach($mapping_relations as $relation) {
      $mappings[$relation['old_id']] = $relation['new_id'];
    }
    return $mappings;
  }

}
