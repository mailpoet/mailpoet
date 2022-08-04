<?php

namespace MailPoet\Config;

use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Models\CustomField;
use MailPoet\Models\MappingToExternalEntities;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Notices\AfterMigrationNotice;
use MailPoet\Util\ProgressBar;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;

class MP2Migrator {
  const IMPORT_TIMEOUT_IN_SECONDS = 7200; // Timeout = 2 hours
  const CHUNK_SIZE = 10; // To import the data by batch

  const MIGRATION_COMPLETE_SETTING_KEY = 'mailpoet_migration_complete';
  const MIGRATION_STARTED_SETTING_KEY = 'mailpoet_migration_started';

  /** @var SettingsController */
  private $settings;

  /** @var Activator */
  private $activator;

  /** @var FormsRepository */
  private $formsRepository;

  private $logFile;
  public $logFileUrl;
  public $progressbar;
  private $segmentsMapping = []; // Mapping between old and new segment IDs
  private $wpUsersSegment;
  private $doubleOptinEnabled = true;
  private $mp2CampaignTable;
  private $mp2CustomFieldTable;
  private $mp2EmailTable;
  private $mp2FormTable;
  private $mp2ListTable;
  private $mp2UserTable;
  private $mp2UserListTable;

  public function __construct(
    SettingsController $settings,
    FormsRepository $formsRepository,
    Activator $activator
  ) {
    $this->defineMP2Tables();
    $logFilename = 'mp2migration.log';
    $this->logFile = Env::$tempPath . '/' . $logFilename;
    $this->logFileUrl = Env::$tempUrl . '/' . $logFilename;
    $this->progressbar = new ProgressBar('mp2migration');
    $this->settings = $settings;
    $this->activator = $activator;
    $this->formsRepository = $formsRepository;
  }

  private function defineMP2Tables() {
    global $wpdb;

    $this->mp2CampaignTable = defined('MP2_CAMPAIGN_TABLE')
      ? MP2_CAMPAIGN_TABLE
      : $wpdb->prefix . 'wysija_campaign';

    $this->mp2CustomFieldTable = defined('MP2_CUSTOM_FIELD_TABLE')
      ? MP2_CUSTOM_FIELD_TABLE
      : $wpdb->prefix . 'wysija_custom_field';

    $this->mp2EmailTable = defined('MP2_EMAIL_TABLE')
      ? MP2_EMAIL_TABLE
      : $wpdb->prefix . 'wysija_email';

    $this->mp2FormTable = defined('MP2_FORM_TABLE')
      ? MP2_FORM_TABLE
      : $wpdb->prefix . 'wysija_form';

    $this->mp2ListTable = defined('MP2_LIST_TABLE')
      ? MP2_LIST_TABLE
      : $wpdb->prefix . 'wysija_list';

    $this->mp2UserTable = defined('MP2_USER_TABLE')
      ? MP2_USER_TABLE
      : $wpdb->prefix . 'wysija_user';

    $this->mp2UserListTable = defined('MP2_USER_LIST_TABLE')
      ? MP2_USER_LIST_TABLE
      : $wpdb->prefix . 'wysija_user_list';
  }

  /**
   * Test if the migration is already started but is not completed
   *
   * @return bool
   */
  public function isMigrationStartedAndNotCompleted() {
    return $this->settings->get(self::MIGRATION_STARTED_SETTING_KEY, false)
      && !$this->settings->get(self::MIGRATION_COMPLETE_SETTING_KEY, false);
  }

  /**
   * Test if the migration is needed
   *
   * @return bool
   */
  public function isMigrationNeeded() {
    if ($this->settings->get(self::MIGRATION_COMPLETE_SETTING_KEY)) {
      return false;
    } else {
      return $this->tableExists($this->mp2CampaignTable); // Check if the MailPoet 2 tables exist
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
   * @return bool
   */
  private function tableExists($table) {
    global $wpdb;

    try {
      $sql = $wpdb->prepare("SHOW TABLES LIKE %s", $table);
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
    file_put_contents($this->logFile, "$message\n", FILE_APPEND);
  }

  /**
   * Import the data from MailPoet 2
   *
   * @return string Result
   */
  public function import() {
    if (strpos((string)@ini_get('disable_functions'), 'set_time_limit') === false) {
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
      $afterMigrationNotice = new AfterMigrationNotice();
      $afterMigrationNotice->enable();
    }

    $this->log(sprintf('=== ' . mb_strtoupper(__('End import', 'mailpoet'), 'UTF-8') . ' %s ===', $datetime->formatTime(time(), \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT)));
    $result = ob_get_contents();
    ob_clean();
    return (string)$result;
  }

  /**
   * Empty the log file
   *
   */
  private function emptyLog() {
    file_put_contents($this->logFile, '');
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
    $encodedOption = WPFunctions::get()->getOption('wysija');
    $values = unserialize(base64_decode($encodedOption));
    if (isset($values['confirm_dbleoptin']) && $values['confirm_dbleoptin'] === '0') {
      $this->doubleOptinEnabled = false;
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
   * @return bool Import must stop or not
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
    $totalCount = 0;

    $this->progressbar->setTotalCount(0);

    $result .= __('MailPoet 2 data found:', 'mailpoet') . "\n";

    // User Lists
    $usersListsCount = ORM::for_table($this->mp2ListTable)->count();
    $totalCount += $usersListsCount;
    $result .= sprintf(_n('%d subscribers list', '%d subscribers lists', $usersListsCount, 'mailpoet'), $usersListsCount) . "\n";

    // Users
    $usersCount = ORM::for_table($this->mp2UserTable)->count();
    $totalCount += $usersCount;
    $result .= sprintf(_n('%d subscriber', '%d subscribers', $usersCount, 'mailpoet'), $usersCount) . "\n";

    // Forms
    $formsCount = ORM::for_table($this->mp2FormTable)->count();
    $totalCount += $formsCount;
    $result .= sprintf(_n('%d form', '%d forms', $formsCount, 'mailpoet'), $formsCount) . "\n";

    $this->progressbar->setTotalCount($totalCount);

    return $result;
  }

  /**
   * Import the subscribers segments
   *
   */
  private function importSegments() {
    $importedSegmentsCount = 0;
    if ($this->importStopped()) {
      $this->segmentsMapping = $this->getImportedMapping('segments');
      return;
    }
    $this->log(__("Importing segments...", 'mailpoet'));
    do {
      if ($this->importStopped()) {
        break;
      }
      $lists = $this->getLists(self::CHUNK_SIZE);
      $listsCount = count($lists);

      if (is_array($lists)) {
        foreach ($lists as $list) {
          $segment = $this->importSegment($list);
          if (!empty($segment)) {
            $importedSegmentsCount++;
          }
        }
      }
      $this->progressbar->incrementCurrentCount($listsCount);
    } while (($lists != null) && ($listsCount > 0));

    $this->segmentsMapping = $this->getImportedMapping('segments');

    $this->log(sprintf(_n("%d segment imported", "%d segments imported", $importedSegmentsCount, 'mailpoet'), $importedSegmentsCount));
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

    $lastId = intval($this->settings->get('last_imported_list_id', 0));
    $table = esc_sql($this->mp2ListTable);
    $sql = $wpdb->prepare("
      SELECT l.list_id, l.name, l.description, l.is_enabled, l.created_at
      FROM `$table` l
      WHERE l.list_id > %s
      ORDER BY l.list_id
      LIMIT %d
      ", $lastId, $limit);
    $lists = $wpdb->get_results($sql, ARRAY_A);

    return $lists;
  }

  /**
   * Import a segment
   *
   * @param array $listData List data
   * @return Segment
   */
  private function importSegment($listData) {
    $datetime = new \MailPoet\WP\DateTime();
    if ($listData['is_enabled']) {
      $segment = Segment::createOrUpdate([
        'name' => $listData['name'],
        'type' => 'default',
        'description' => !empty($listData['description']) ? $listData['description'] : '',
        'created_at' => $datetime->formatTime($listData['created_at'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
      ]);
    } else {
      $segment = Segment::getWPSegment();
    }
    if (!empty($segment)) {
      // Map the segment with its old ID
      $mapping = new MappingToExternalEntities();
      $mapping->create([
        'old_id' => $listData['list_id'],
        'type' => 'segments',
        'new_id' => $segment->id,
        'created_at' => $datetime->formatTime(time(), \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
      ]);
    }
    $this->settings->set('last_imported_list_id', $listData['list_id']);
    return $segment;
  }

  /**
   * Import the custom fields
   *
   */
  private function importCustomFields() {
    $importedCustomFieldsCount = 0;
    if ($this->importStopped()) {
      return;
    }
    $this->log(__("Importing custom fields...", 'mailpoet'));
    $customFields = $this->getCustomFields();

    foreach ($customFields as $customField) {
      $result = $this->importCustomField($customField);
      if (!empty($result)) {
        $importedCustomFieldsCount++;
      }
    }

    $this->log(sprintf(_n("%d custom field imported", "%d custom fields imported", $importedCustomFieldsCount, 'mailpoet'), $importedCustomFieldsCount));
  }

  /**
   * Get the Mailpoet 2 custom fields
   *
   * @global object $wpdb
   * @return array Custom fields
   */
  private function getCustomFields() {
    global $wpdb;
    $customFields = [];

    $table = esc_sql($this->mp2CustomFieldTable);
    $sql = "
      SELECT cf.id, cf.name, cf.type, cf.required, cf.settings
      FROM `$table` cf
      ";
    $customFields = $wpdb->get_results($sql, ARRAY_A);

    return $customFields;
  }

  /**
   * Import a custom field
   *
   * @param array $customField MP2 custom field
   * @return CustomField
   */
  private function importCustomField($customField) {
    $data = [
      'id' => $customField['id'],
      'name' => $customField['name'],
      'type' => $this->mapCustomFieldType($customField['type']),
      'params' => $this->mapCustomFieldParams($customField['name'], unserialize($customField['settings'])),
    ];
    $customField = new CustomField();
    $customField->createOrUpdate($data);
    return $customField;
  }

  /**
   * Map the MailPoet 2 custom field type with the MailPoet custom field type
   *
   * @param string $mp2Type MP2 custom field type
   * @return string MP3 custom field type
   */
  private function mapCustomFieldType($mp2Type) {
    $type = '';
    switch ($mp2Type) {
      case 'input':
        $type = 'text';
        break;
      case 'list':
        $type = 'segment';
        break;
      default:
        $type = $mp2Type;
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
   * @param string $mp2Value MP2 value
   * @return string MP3 value
   */
  private function mapCustomFieldValidateValue($mp2Value) {
    $value = '';
    switch ($mp2Value) {
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
    $importedSubscribersCount = 0;
    if ($this->importStopped()) {
      return;
    }
    $this->log(__("Importing subscribers...", 'mailpoet'));
    $this->wpUsersSegment = Segment::getWPSegment();
    do {
      if ($this->importStopped()) {
        break;
      }
      $users = $this->getUsers(self::CHUNK_SIZE);
      $usersCount = count($users);

      if (is_array($users)) {
        foreach ($users as $user) {
          $subscriber = $this->importSubscriber($user);
          if (!empty($subscriber)) {
            $importedSubscribersCount++;
            $this->importSubscriberSegments($subscriber, $user['user_id']);
            $this->importSubscriberCustomFields($subscriber, $user);
          }
        }
      }
      $this->progressbar->incrementCurrentCount($usersCount);
    } while (($users != null) && ($usersCount > 0));

    $this->log(sprintf(_n("%d subscriber imported", "%d subscribers imported", $importedSubscribersCount, 'mailpoet'), $importedSubscribersCount));
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
    $lastId = intval($this->settings->get('last_imported_user_id', 0));
    $table = esc_sql($this->mp2UserTable);
    $sql = $wpdb->prepare("
      SELECT u.*
      FROM `$table` u
      WHERE u.user_id > %s
      ORDER BY u.user_id
      LIMIT %d
      ", $lastId, $limit);
    $users = $wpdb->get_results($sql, ARRAY_A);

    return $users;
  }

  /**
   * Import a subscriber
   *
   * @param array $userData User data
   * @return Subscriber
   */
  private function importSubscriber($userData) {
    $datetime = new \MailPoet\WP\DateTime();
    $subscriber = Subscriber::createOrUpdate([
      'wp_user_id' => !empty($userData['wpuser_id']) ? $userData['wpuser_id'] : null,
      'email' => $userData['email'],
      'first_name' => $userData['firstname'],
      'last_name' => $userData['lastname'],
      'status' => $this->mapUserStatus($userData['status']),
      'created_at' => $datetime->formatTime($userData['created_at'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
      'subscribed_ip' => !empty($userData['ip']) ? $userData['ip'] : null,
      'confirmed_ip' => !empty($userData['confirmed_ip']) ? $userData['confirmed_ip'] : null,
      'confirmed_at' => !empty($userData['confirmed_at']) ? $datetime->formatTime($userData['confirmed_at'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT) : null,
    ]);
    $this->settings->set('last_imported_user_id', $userData['user_id']);
    if (!empty($subscriber)) {
      // Map the subscriber with its old ID
      $mapping = new MappingToExternalEntities();
      $mapping->create([
        'old_id' => $userData['user_id'],
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
   * @param int $mp2UserStatus MP2 user status
   * @return string MP3 user status
   */
  private function mapUserStatus($mp2UserStatus) {


    switch ($mp2UserStatus) {
      case 1:
        $status = 'subscribed';
        break;
      case -1:
        $status = 'unsubscribed';
        break;
      case 0:
      default:
        //if MP2 double-optin is disabled, we change "unconfirmed" status in MP2 to "confirmed" status in MP3.
        if (!$this->doubleOptinEnabled) {
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
   * @param int $userId MP2 user ID
   */
  private function importSubscriberSegments($subscriber, $userId) {
    $userLists = $this->getUserLists($userId);
    foreach ($userLists as $userList) {
      $this->importSubscriberSegment($subscriber->id, $userList);
    }
  }

  /**
   * Get the lists for a user
   *
   * @global object $wpdb
   * @param int $userId User ID
   * @return array Users Lists
   */
  private function getUserLists($userId) {
    global $wpdb;

    $table = esc_sql($this->mp2UserListTable);
    $sql = $wpdb->prepare("
      SELECT ul.list_id, ul.sub_date, ul.unsub_date
      FROM `$table` ul
      WHERE ul.user_id = %s
      ", $userId);
    $userLists = $wpdb->get_results($sql, ARRAY_A);

    return $userLists;
  }

  /**
   * Import a subscriber segment
   *
   * @param int $subscriberId
   * @param array $userList
   * @return SubscriberSegment|null
   */
  private function importSubscriberSegment($subscriberId, $userList) {
    $subscriberSegment = null;
    $datetime = new \MailPoet\WP\DateTime();
    if (isset($this->segmentsMapping[$userList['list_id']])) {
      $segmentId = $this->segmentsMapping[$userList['list_id']];
      $status = (($segmentId == $this->wpUsersSegment->id) || empty($userList['unsub_date'])) ? 'subscribed' : 'unsubscribed'; // the users belonging to the wp_users segment are always subscribed
      $data = [
        'subscriber_id' => $subscriberId,
        'segment_id' => $segmentId,
        'status' => $status,
        'created_at' => $datetime->formatTime($userList['sub_date'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT),
      ];
      $data['updated_at'] = !empty($userList['unsub_date']) ? $datetime->formatTime($userList['unsub_date'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT) : $data['created_at'];
      $subscriberSegment = new SubscriberSegment();
      $subscriberSegment->createOrUpdate($data);
    }
    return $subscriberSegment;
  }

  /**
   * Import the custom fields values for a subscriber
   *
   * @param Subscriber $subscriber MP3 subscriber
   * @param array $user MP2 user
   */
  private function importSubscriberCustomFields($subscriber, $user) {
    $importedCustomFields = $this->getImportedCustomFields();
    foreach ($importedCustomFields as $customField) {
      $customFieldColumn = 'cf_' . $customField['id'];
      $this->importSubscriberCustomField($subscriber->id, $customField, $user[$customFieldColumn]);
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
    $customFields = $wpdb->get_results($sql, ARRAY_A);
    return $customFields;
  }

  /**
   * Import a subscriber custom field
   *
   * @param int $subscriberId Subscriber ID
   * @param array $customField Custom field
   * @param string $customFieldValue Custom field value
   * @return SubscriberCustomField
   */
  private function importSubscriberCustomField($subscriberId, $customField, $customFieldValue) {
    if ($customField['type'] == 'date') {
      $datetime = new \MailPoet\WP\DateTime();
      $value = $datetime->formatTime($customFieldValue, \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT); // Convert the date field
    } else {
      $value = $customFieldValue;
    }
    $data = [
      'subscriber_id' => $subscriberId,
      'custom_field_id' => $customField['id'],
      'value' => isset($value) ? $value : '',
    ];
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
  public function getImportedMapping($model) {
    $mappings = [];
    $mappingRelations = MappingToExternalEntities::where('type', $model)->findArray();
    foreach ($mappingRelations as $relation) {
      $mappings[$relation['old_id']] = $relation['new_id'];
    }
    return $mappings;
  }

  /**
   * Import the forms
   *
   */
  private function importForms() {
    $importedFormsCount = 0;
    if ($this->importStopped()) {
      return;
    }
    $this->log(__("Importing forms...", 'mailpoet'));
    do {
      if ($this->importStopped()) {
        break;
      }
      $forms = $this->getForms(self::CHUNK_SIZE);
      $formsCount = count($forms);

      if (is_array($forms)) {
        foreach ($forms as $form) {
          $this->importForm($form);
          $importedFormsCount++;
        }
      }
      $this->formsRepository->flush();
      $this->progressbar->incrementCurrentCount($formsCount);
    } while (($forms != null) && ($formsCount > 0));

    $this->log(sprintf(_n("%d form imported", "%d forms imported", $importedFormsCount, 'mailpoet'), $importedFormsCount));
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

    $lastId = intval($this->settings->get('last_imported_form_id', 0));
    $table = esc_sql($this->mp2FormTable);
    $sql = $wpdb->prepare("
      SELECT f.*
      FROM `$table` f
      WHERE f.form_id > %s
      ORDER BY f.form_id
      LIMIT %d
      ", $lastId, $limit);
    $forms = $wpdb->get_results($sql, ARRAY_A);

    return $forms;
  }

  /**
   * Import a form
   *
   * @param array $formData Form data
   */
  private function importForm($formData) {
    $serializedData = base64_decode($formData['data']);
    $data = unserialize($serializedData);
    $settings = $data['settings'];
    $body = $data['body'];
    $segments = $this->getMappedSegmentIds($settings['lists']);
    $mp3FormSettings = [
      'on_success' => $settings['on_success'],
      'success_message' => $settings['success_message'],
      'segments_selected_by' => $settings['lists_selected_by'],
      'segments' => $segments,
    ];

    $mp3FormBody = [];
    foreach ($body as $field) {
      $type = $this->mapCustomFieldType($field['type']);
      if ($type == 'segment') {
          $fieldId = 'segments';
      } else {
        switch ($field['field']) {
          case 'firstname':
            $fieldId = 'first_name';
            break;
          case 'lastname':
            $fieldId = 'last_name';
            break;
          default:
            $fieldId = $field['field'];
        }
      }
      $fieldId = preg_replace('/^cf_(\d+)$/', '$1', $fieldId);
      $params = $this->mapCustomFieldParams($field['name'], $field['params']);
      if (isset($params['text'])) {
        $params['text'] = $this->replaceMP2Shortcodes(html_entity_decode($params['text']));
      }
      if (isset($params['values'])) {
        $params['values'] = $this->replaceListIds($params['values']);
      }
      $mp3FormBody[] = [
        'type' => $type,
        'name' => $field['name'],
        'id' => $fieldId,
        'unique' => !in_array($field['type'], ['html', 'divider', 'email', 'submit']) ? "1" : "0",
        'static' => in_array($fieldId, ['email', 'submit']) ? "1" : "0",
        'params' => $params,
        'position' => isset($field['position']) ? $field['position'] : '',
      ];
    }

    $form = new FormEntity($formData['name']);
    $form->setBody($mp3FormBody);
    $form->setSettings($mp3FormSettings);

    $this->formsRepository->persist($form);

    $this->settings->set('last_imported_form_id', $formData['form_id']);
  }

  /**
   * Get the MP3 segments IDs of the MP2 lists IDs
   *
   * @param array $mp2ListIds
   */
  private function getMappedSegmentIds($mp2ListIds) {
    $mp3SegmentIds = [];
    foreach ($mp2ListIds as $listId) {
      if (isset($this->segmentsMapping[$listId])) {
        $mp3SegmentIds[] = $this->segmentsMapping[$listId];
      }
    }
    return $mp3SegmentIds;
  }

  /**
   * Replace the MP2 shortcodes used in the textarea fields
   *
   * @param string $text Text
   * @return string|null Text
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
      $mp2Lists = explode(',', $matches[1]);
      $segments = $this->getMappedSegmentIds($mp2Lists);
      $segmentsIds = implode(',', $segments);
      return '[mailpoet_subscribers_count segments=' . $segmentsIds . ']';
    }
    return '';
  }

  /**
   * Replace the MP2 list IDs by MP3 segment IDs
   *
   * @param array $values Field values
   * @return array Field values
   */
  private function replaceListIds($values) {
    $mp3Values = [];
    foreach ($values as $value) {
      $mp3Value = [];
      foreach ($value as $item => $itemValue) {
        if (($item == 'list_id') && isset($this->segmentsMapping[$itemValue])) {
          $segmentId = $this->segmentsMapping[$itemValue];
          $mp3Value['id'] = $segmentId;
          $segment = Segment::findOne($segmentId);
          if ($segment instanceof Segment) {
            $mp3Value['name'] = $segment->get('name');
          }
        } else {
          $mp3Value[$item] = $itemValue;
        }
      }
      if (!empty($mp3Value)) {
        $mp3Values[] = $mp3Value;
      }
    }
    return $mp3Values;
  }

  /**
   * Import the settings
   *
   */
  private function importSettings() {
    $encodedOptions = WPFunctions::get()->getOption('wysija');
    $options = unserialize(base64_decode($encodedOptions));

    // Sender
    $sender = $this->settings->get('sender');
    $sender['name'] = isset($options['from_name']) ? $options['from_name'] : '';
    $sender['address'] = isset($options['from_email']) ? $options['from_email'] : '';
    $this->settings->set('sender', $sender);

    // Reply To
    $replyTo = $this->settings->get('reply_to');
    $replyTo['name'] = isset($options['replyto_name']) ? $options['replyto_name'] : '';
    $replyTo['address'] = isset($options['replyto_email']) ? $options['replyto_email'] : '';
    $this->settings->set('reply_to', $replyTo);

    // Bounce
    $bounce = $this->settings->get('bounce');
    $bounce['address'] = isset($options['bounce_email']) && WPFunctions::get()->isEmail($options['bounce_email']) ? $options['bounce_email'] : '';
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
    $signupConfirmation = $this->settings->get('signup_confirmation');
    $signupConfirmation['enabled'] = isset($options['confirm_dbleoptin']) && ($options['confirm_dbleoptin'] == 0) ? 0 : 1;
    if (isset($options['confirm_email_id'])) {
      $confirmEmailId = $options['confirm_email_id'];
      $confirmEmail = $this->getEmail($confirmEmailId);
      if (!empty($confirmEmail)) {
        $signupConfirmation['subject'] = isset($confirmEmail['subject']) ? $confirmEmail['subject'] : '';
        $signupConfirmation['body'] = isset($confirmEmail['body']) ? $confirmEmail['body'] : '';
      }
    }
    $this->settings->set('signup_confirmation', $signupConfirmation);

    // Analytics
    $analytics = $this->settings->get('analytics');
    $analytics['enabled'] = isset($options['analytics']) ? $options['analytics'] : '';
    $this->settings->set('analytics', $analytics);

    // MTA
    $mtaGroup = isset($options['sending_method']) && ($options['sending_method'] == 'smtp') ? 'smtp' : 'website';
    $this->settings->set('mta_group', $mtaGroup);

    $mta = $this->settings->get('mta');
    $mta['method'] = (isset($options['smtp_host']) && ($options['smtp_host'] == 'smtp.sendgrid.net')) ? 'SendGrid' : (isset($options['sending_method']) && ($options['sending_method'] == 'smtp') ? 'SMTP' : 'PHPMail');
    $sendingEmailsNumber = isset($options['sending_emails_number']) ? $options['sending_emails_number'] : '';
    $sendingEmailsEach = isset($options['sending_emails_each']) ? $options['sending_emails_each'] : '';
    $mta['frequency']['emails'] = $this->mapFrequencyEmails($sendingEmailsNumber, $sendingEmailsEach);
    $mta['frequency']['interval'] = $this->mapFrequencyInterval($sendingEmailsEach);
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
      $installedAt = $datetime->formatTime($options['installed_time'], \MailPoet\WP\DateTime::DEFAULT_DATE_TIME_FORMAT);
      $this->settings->set('installed_at', $installedAt);
    }

    $this->log(__("Settings imported", 'mailpoet'));
  }

  /**
   * Get an email
   *
   * @global object $wpdb
   * @param int $emailId
   * @return array Email
   */
  private function getEmail($emailId) {
    global $wpdb;
    $email = [];

    $table = esc_sql($this->mp2EmailTable);
    $sql = $wpdb->prepare("
      SELECT e.*
      FROM `$table` e
      WHERE e.email_id = %s
      ", $emailId);
    $email = $wpdb->get_row($sql, ARRAY_A);

    return $email;
  }

  /**
   * Map the Email frequency interval
   *
   * @param string $intervalStr Interval
   * @return string Interval
   */
  private function mapFrequencyInterval($intervalStr) {
    switch ($intervalStr) {
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
   * @param int $emailsNumber Emails number
   * @param string $intervalStr Interval
   * @return int Emails number
   */
  private function mapFrequencyEmails($emailsNumber, $intervalStr) {
    if (empty($emailsNumber)) {
      $emailsNumber = 70;
    } else {
      switch ($intervalStr) {
        case 'thirty_min':
          $emailsNumber /= 2;
          break;

        case 'hourly':
        case '':
          $emailsNumber /= 4;
          break;

        case 'two_hours':
          $emailsNumber /= 8;
          break;
      }
      $emailsNumber = (int)round($emailsNumber);
    }
    return $emailsNumber;
  }
}
