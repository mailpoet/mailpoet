<?php

namespace MailPoet\Config;

use MailPoet\Util\ProgressBar;
use MailPoet\Models\Setting;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\CustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\ImportedDataMapping;
use MailPoet\Config\Activator;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class MP2Migrator {

  private $log_file;
  public $log_file_url;
  public $progressbar;
  private $chunks_size = 10; // To import the data by batch

  public function __construct() {
    $log_filename = 'mp2migration.log';
    $this->log_file = Env::$temp_path . '/' . $log_filename;
    $this->log_file_url = Env::$temp_url . '/' . $log_filename;
    $this->progressbar = new ProgressBar('mp2migration');
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
      return $this->tableExists('wysija_campaign'); // Check if the MailPoet 2 tables exist
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
      $sql = "SHOW TABLES LIKE '{$wpdb->prefix}{$table}'";
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
    $this->log('INIT');
  }

  /**
   * Register the JavaScript for the admin area.
   *
   */
  private function enqueueScripts() {
    wp_register_script('mp2migrator', Env::$assets_url . '/js/mp2migrator.js', array('jquery-ui-progressbar'));
    wp_enqueue_script('mp2migrator');
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
   * @return boolean Result
   */
  public function import() {
    set_time_limit(7200); // Timeout = 2 hours
    ob_start();
    $this->emptyLog();
    $this->log(sprintf("=== START IMPORT %s ===", date('Y-m-d H:i:s')));
    Setting::setValue('import_stopped', false); // Reset the stop import action
    $this->eraseMP3Data();
    
    $this->displayDataToMigrate();
    
    $this->importSegments();
    $this->importCustomFields();
    $this->importSubscribers();
    
    $this->log(sprintf("=== END IMPORT %s ===", date('Y-m-d H:i:s')));
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
    $this->resetMigrationCounters();
    $this->log(__("MailPoet data erased", Env::$plugin_name));
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
   * Stop the import
   * 
   */
  public function stopImport() {
    Setting::setValue('import_stopped', true);
    $this->log(__('IMPORT STOPPED BY USER', Env::$plugin_name));
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
    $data = $this->getDataToMigrate();
    $this->log($data);
  }
  
  /**
   * Get the data to migrate
   * 
   * @return string Data to migrate
   */
  private function getDataToMigrate() {
    $result = '';
    $totalCount = 0;
    
    $this->progressbar->setTotalCount(0);
    
    $result .= __('MailPoet 2 data found:', Env::$plugin_name) . "\n";
    
    // User Lists
    $usersListsCount = $this->rowsCount('wysija_list');
    $totalCount += $usersListsCount;
    $result .= sprintf(_n('%d subscribers list', '%d subscribers lists', $usersListsCount, Env::$plugin_name), $usersListsCount) . "\n";
    
    // Users
    $usersCount = $this->rowsCount('wysija_user');
    $totalCount += $usersCount;
    $result .= sprintf(_n('%d subscriber', '%d subscribers', $usersCount, Env::$plugin_name), $usersCount) . "\n";
    
    // Emails
    $emailsCount = $this->rowsCount('wysija_email');
    $totalCount += $emailsCount;
    $result .= sprintf(_n('%d newsletter', '%d newsletters', $emailsCount, Env::$plugin_name), $emailsCount) . "\n";
    
    // Forms
    $formsCount = $this->rowsCount('wysija_form');
    $totalCount += $formsCount;
    $result .= sprintf(_n('%d form', '%d forms', $formsCount, Env::$plugin_name), $formsCount) . "\n";
    
    $this->progressbar->setTotalCount($totalCount);
    
    return $result;
  }
  
  /**
   * Count the number of rows in a table
   * 
   * @global object $wpdb
   * @param string $table Table
   * @return int Number of rows found
   */
  private function rowsCount($table) {
    global $wpdb;

    $table = $wpdb->prefix . $table;
    $sql = "SELECT COUNT(*) FROM `$table`";
    $count = $wpdb->get_var($sql);
    
    return $count;
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
    $this->log(__("Importing segments...", Env::$plugin_name));
    do {
      if($this->importStopped()) {
        break;
      }
      $lists = $this->getLists($this->chunks_size);
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
    
    $this->log(sprintf(_n("%d segment imported", "%d segments imported", $imported_segments_count, Env::$plugin_name), $imported_segments_count));
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
    $table = $wpdb->prefix . 'wysija_list';
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
    $segment = Segment::createOrUpdate(array(
      'id' => $list_data['list_id'],
      'name' => $list_data['name'],
      'type' => $list_data['is_enabled']? 'default': 'wp_users',
      'description' => $list_data['description'],
      'created_at' => Helpers::mysqlDate($list_data['created_at']),
    ));
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
    $this->log(__("Importing custom fields...", Env::$plugin_name));
    $custom_fields = $this->getCustomFields();
 
    foreach($custom_fields as $custom_field) {
      $result = $this->importCustomField($custom_field);
      if(!empty($result)) {
        $imported_custom_fields_count++;
      }
    }
    
    $this->log(sprintf(_n("%d custom field imported", "%d custom fields imported", $imported_custom_fields_count, Env::$plugin_name), $imported_custom_fields_count));
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

    $table = $wpdb->prefix . 'wysija_custom_field';
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
    $customField = new CustomField();
    $customField->createOrUpdate($data);
    return $customField;
  }
  
  /**
   * Map the MailPoet 2 custom field type with the MailPoet custom field type
   * 
   * @param string $MP2type MP2 custom field type
   * @return string MP3 custom field type
   */
  private function mapCustomFieldType($MP2type) {
    $type = '';
    switch($MP2type) {
      case 'input':
        $type = 'text';
        break;
      default:
        $type = $MP2type;
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
   * @param string $MP2value MP2 value
   * @return string MP3 value
   */
  private function mapCustomFieldValidateValue($MP2value) {
    $value = '';
    switch($MP2value) {
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
    $this->log(__("Importing subscribers...", Env::$plugin_name));
    do {
      if($this->importStopped()) {
        break;
      }
      $users = $this->getUsers($this->chunks_size);
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
    
    $this->log(sprintf(_n("%d subscriber imported", "%d subscribers imported", $imported_subscribers_count, Env::$plugin_name), $imported_subscribers_count));
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
    $table = $wpdb->prefix . 'wysija_user';
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
    $subscriber = Subscriber::createOrUpdate(array(
      'id' => $user_data['user_id'],
      'wp_user_id' => !empty($user_data['wpuser_id'])? $user_data['wpuser_id'] : null,
      'email' => $user_data['email'],
      'first_name' => $user_data['firstname'],
      'last_name' => $user_data['lastname'],
      'status' => $this->mapUserStatus($user_data['status']),
      'created_at' => Helpers::mysqlDate($user_data['created_at']),
      'subscribed_ip' => !empty($user_data['ip'])? $user_data['ip'] : null,
      'confirmed_ip' => !empty($user_data['confirmed_ip'])? $user_data['confirmed_ip'] : null,
      'confirmed_at' => !empty($user_data['confirmed_at'])? Helpers::mysqlDate($user_data['confirmed_at']) : null,
    ));
    Setting::setValue('last_imported_user_id', $user_data['user_id']);
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
      case 0:
        $status = 'unconfirmed';
        break;
      case 1:
        $status = 'subscribed';
        break;
      case -1:
        $status = 'unsubscribed';
        break;
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

    $table = $wpdb->prefix . 'wysija_user_list';
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
    $data = array(
      'subscriber_id' => $subscriber_id,
      'segment_id' => $user_list['list_id'],
      'status' => empty($user_list['unsub_date'])? 'subscribed' : 'unsubscribed',
      'created_at' => Helpers::mysqlDate($user_list['sub_date']),
      'updated_at' => !empty($user_list['unsub_date'])? Helpers::mysqlDate($user_list['unsub_date']) : null,
    );
    $subscriberSegment = new SubscriberSegment();
    $subscriberSegment->createOrUpdate($data);
    return $subscriberSegment;
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
      $value = Helpers::mysqlDate($custom_field_value); // Convert the date field
    } else {
      $value = $custom_field_value;
    }
    $data = array(
      'subscriber_id' => $subscriber_id,
      'custom_field_id' => $custom_field['id'],
      'value' => isset($value)? $value : '',
    );
    $subscriberCustomField = new SubscriberCustomField();
    $subscriberCustomField->createOrUpdate($data);
    return $subscriberCustomField;
  }
  
  /**
   * Get the mapping between the MP2 and the imported MP3 IDs
   * 
   * @param string $model Model (segment,...)
   * @return array Mapping
   */
  private function getImportedMapping($model) {
    $mappings = array();
    $mapping_relations = ImportedDataMapping::where('type', $model)->findArray();
    foreach($mapping_relations as $relation) {
      $mappings[$relation['old_id']] = $relation['new_id'];
    }
    return $mappings;
  }
  
}
