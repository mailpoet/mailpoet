<?php
use MailPoet\Config\MP2Migrator;
use MailPoet\Models\Setting;
use MailPoet\Models\CustomField;
use MailPoet\Models\MappingToExternalEntities;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use Helper\Database;

class MP2MigratorTest extends MailPoetTest {
  
  public function _before() {
    $this->MP2Migrator = new MP2Migrator();
  }

  public function _after() {
  }
  
  /**
   * Test the isMigrationNeeded function
   * 
   */
  public function testIsMigrationNeeded() {
    Database::loadSQL('dropMP2Tables');
    $result = $this->MP2Migrator->isMigrationNeeded();
    expect($result)->false();
    
    Database::loadSQL('createMP2Tables');
    $result = $this->MP2Migrator->isMigrationNeeded();
    expect($result)->true();
  }

  /**
   * Test the init function
   * 
   */
  public function testInit() {
    // Nothing to test
  }

  /**
   * Test the eraseMP3Data function
   * 
   */
  public function testEraseMP3Data() {
    global $wpdb;
    
    $this->invokeMethod($this->MP2Migrator, 'eraseMP3Data');
    
    // Check if the subscribers number is equal to the WordPress users number
    $WPUsersCount = \ORM::for_table($wpdb->prefix . 'users')->count();
    expect(Subscriber::count())->equals($WPUsersCount);
    
    // Check if the custom fields number is 0
    expect(CustomField::count())->equals(0);
    
    // Check if the subscribers custom fields number is 0
    expect(SubscriberCustomField::count())->equals(0);
  }
  
  /**
   * Test the resetMigrationCounters function
   * 
   */
  public function testResetMigrationCounters() {
    $this->invokeMethod($this->MP2Migrator, 'resetMigrationCounters');
    
    // Check if the last imported user ID is 0
    $lastImportedUserID = Setting::getValue('last_imported_user_id', 0);
    expect($lastImportedUserID)->equals(0);
    
    // Check if the last imported list ID is 0
    $lastImportedListID = Setting::getValue('last_imported_list_id', 0);
    expect($lastImportedListID)->equals(0);
  }
  
  /**
   * Test the stopImport function
   * 
   */
  public function testStopImport() {
    delete_option('mailpoet_stopImport');
    $this->MP2Migrator->stopImport();
    $stopImport = !empty(Setting::getValue('import_stopped', false));
    expect($stopImport)->true();
  }

  /**
   * Create the MP2 tables and erase the MP3 data
   * 
   */
  private function initImport() {
    Database::loadSQL('createMP2Tables');
    $this->invokeMethod($this->MP2Migrator, 'eraseMP3Data');
  }
  
  /**
   * Populate the MP2 tables with some samples data
   * 
   */
  private function loadMP2Fixtures() {
    Database::loadSQL('populateMP2Tables');
  }
  
  /**
   * Test the importSegments function
   * 
   * @global object $wpdb
   */
  public function testImportSegments() {
    global $wpdb;
    
    // Check the segments number
    $this->initImport();
    $this->loadMP2Fixtures();
    $this->invokeMethod($this->MP2Migrator, 'importSegments');
    expect(Segment::count())->equals(3);
    
    // Check a segment data
    $this->initImport();
    $id = 999;
    $name = 'Test list';
    $description = 'Description of the test list';
    $timestamp = 1486319877;
    $wpdb->insert($wpdb->prefix . 'wysija_list', array(
      'list_id' => $id,
      'name' => $name,
      'description' => $description,
      'is_enabled' => 1,
      'is_public' => 1,
      'created_at' => $timestamp,
    ));
    $this->invokeMethod($this->MP2Migrator, 'importSegments');
    $importedSegmentsMapping = $this->MP2Migrator->getImportedMapping('segments');
    $table = MP_SEGMENTS_TABLE;
    $segment = $wpdb->get_row("SELECT * FROM $table WHERE id=" . $importedSegmentsMapping[$id]);
    expect($segment->name)->equals($name);
    expect($segment->description)->equals($description);
  }
  
  /**
   * Test the importCustomFields function
   * 
   * @global object $wpdb
   */
  public function testImportCustomFields() {
    global $wpdb;
    
    // Check the custom fields number
    $this->initImport();
    $this->loadMP2Fixtures();
    $this->invokeMethod($this->MP2Migrator, 'importCustomFields');
    expect(CustomField::count())->equals(10);
    
    // Check a custom field data
    $this->initImport();
    $id = 999;
    $name = 'Test field';
    $type = 'input';
    $required = 1;
    $settings = array(
      'required' => '1',
      'validate' => 'onlyLetterSp',
    );
    $wpdb->insert($wpdb->prefix . 'wysija_custom_field', array(
      'id' => $id,
      'name' => $name,
      'type' => $type,
      'required' => $required,
      'settings' => serialize($settings),
    ));
    $this->invokeMethod($this->MP2Migrator, 'importCustomFields');
    $table = MP_CUSTOM_FIELDS_TABLE;
    $custom_field = $wpdb->get_row("SELECT * FROM $table WHERE id=$id");
    expect($custom_field->id)->equals($id);
    expect($custom_field->name)->equals($name);
    expect($custom_field->type)->equals('text');
    $custom_field_params = unserialize($custom_field->params);
    expect($custom_field_params['required'])->equals($settings['required']);
    expect($custom_field_params['validate'])->equals('alphanum');
    expect($custom_field_params['label'])->equals($name);
  }
  
  /**
   * Test the importSubscribers function
   * 
   * @global object $wpdb
   */
  public function testImportSubscribers() {
    global $wpdb;
    
    // Check the subscribers number
    $this->initImport();
    $this->loadMP2Fixtures();
    $this->invokeMethod($this->MP2Migrator, 'importSubscribers');
    expect(Subscriber::count())->equals(5); // 4 MP2 users + the current user
    
    // Check a subscriber data
    $this->initImport();
    $id = 999;
    $wp_id = 1;
    $email = 'test@test.com';
    $firstname = 'Test firstname';
    $lastname = 'Test lastname';
    $ip = '127.0.0.1';
    $confirmed_ip = $ip;
    $wpdb->insert($wpdb->prefix . 'wysija_user', array(
      'user_id' => $id,
      'wpuser_id' => $wp_id,
      'email' => $email,
      'firstname' => $firstname,
      'lastname' => $lastname,
      'ip' => $ip,
      'confirmed_ip' => $confirmed_ip,
      'status' => '1',
    ));
    $this->invokeMethod($this->MP2Migrator, 'importSubscribers');
    $table = MP_SUBSCRIBERS_TABLE;
    $subscriber = $wpdb->get_row("SELECT * FROM $table WHERE email='$email'");
    expect($subscriber->email)->equals($email);
    expect($subscriber->first_name)->equals($firstname);
    expect($subscriber->last_name)->equals($lastname);
    expect($subscriber->subscribed_ip)->equals($ip);
    expect($subscriber->confirmed_ip)->equals($confirmed_ip);
    expect($subscriber->status)->equals('subscribed');
  }
  
  /**
   * Test the importSubscriberSegments function
   * 
   * @global object $wpdb
   */
  public function testImportSubscriberSegments() {
    global $wpdb;
    
    // Check the subscriber segments number
    $this->initImport();
    $this->loadMP2Fixtures();
    $this->invokeMethod($this->MP2Migrator, 'importSegments');
    $this->invokeMethod($this->MP2Migrator, 'importSubscribers');
    expect(SubscriberSegment::count())->equals(8); // 7 + the current user belongs to WordPress users segment
    
    // Check a subscriber segment data
    
    // Insert a list
    $this->initImport();
    $list_id = 998;
    $list_name = 'Test list';
    $description = 'Description of the test list';
    $timestamp = 1486319877;
    $wpdb->insert($wpdb->prefix . 'wysija_list', array(
      'list_id' => $list_id,
      'name' => $list_name,
      'description' => $description,
      'is_enabled' => 1,
      'is_public' => 1,
      'created_at' => $timestamp,
    ));
    
    // Insert a user
    $user_id = 999;
    $wp_id = 1;
    $email = 'test@test.com';
    $wpdb->insert($wpdb->prefix . 'wysija_user', array(
      'user_id' => $user_id,
      'wpuser_id' => $wp_id,
      'email' => $email,
    ));
    
    // Insert a user list
    $wpdb->insert($wpdb->prefix . 'wysija_user_list', array(
      'list_id' => $list_id,
      'user_id' => $user_id,
      'sub_date' => $timestamp,
    ));
    
    $this->invokeMethod($this->MP2Migrator, 'importSegments');
    $this->invokeMethod($this->MP2Migrator, 'importSubscribers');
    $importedSegmentsMapping = $this->MP2Migrator->getImportedMapping('segments');
    $importedSubscribersMapping = $this->MP2Migrator->getImportedMapping('subscribers');
    $table = MP_SUBSCRIBER_SEGMENT_TABLE;
    $segment_id = $importedSegmentsMapping[$list_id];
    $subscriber_id = $importedSubscribersMapping[$user_id];
    $subscriber_segment = $wpdb->get_row("SELECT * FROM $table WHERE subscriber_id='$subscriber_id' AND segment_id='$segment_id'");
    expect($subscriber_segment)->notNull();
  }
  
  /**
   * Test the importSubscriberCustomFields function
   * 
   * @global object $wpdb
   */
  public function testImportSubscriberCustomFields() {
    global $wpdb;
    
    // Check the subscriber custom fields number
    $this->initImport();
    $this->loadMP2Fixtures();
    $this->invokeMethod($this->MP2Migrator, 'importCustomFields');
    $this->invokeMethod($this->MP2Migrator, 'importSubscribers');
    expect(SubscriberCustomField::count())->equals(40);
    
    // Check a subscriber custom field data
    
    $this->initImport();
    // Insert a custom field
    $cf_id = 1;
    $cf_name = 'Custom field key';
    $cf_type = 'input';
    $cf_required = 1;
    $cf_settings = array(
      'required' => '1',
      'validate' => 'onlyLetterSp',
    );
    $wpdb->insert($wpdb->prefix . 'wysija_custom_field', array(
      'id' => $cf_id,
      'name' => $cf_name,
      'type' => $cf_type,
      'required' => $cf_required,
      'settings' => serialize($cf_settings),
    ));
    
    // Insert a user
    $user_id = 999;
    $wp_id = 1;
    $email = 'test@test.com';
    $custom_field_value = 'Test custom field value';
    $wpdb->insert($wpdb->prefix . 'wysija_user', array(
      'user_id' => $user_id,
      'wpuser_id' => $wp_id,
      'email' => $email,
      'cf_' . $cf_id => $custom_field_value,
    ));
    
    $this->invokeMethod($this->MP2Migrator, 'importCustomFields');
    $this->invokeMethod($this->MP2Migrator, 'importSubscribers');
    $importedSubscribersMapping = $this->MP2Migrator->getImportedMapping('subscribers');
    $table = MP_SUBSCRIBER_CUSTOM_FIELD_TABLE;
    $subscriber_id = $importedSubscribersMapping[$user_id];
    $subscriber_custom_field = $wpdb->get_row("SELECT * FROM $table WHERE subscriber_id='$subscriber_id' AND custom_field_id='$cf_id'");
    expect($subscriber_custom_field->value)->equals($custom_field_value);
  }
  
  /**
   * Test the getImportedMapping function
   * 
   */
  public function testGetImportedMapping() {
    $this->initImport();
    $mapping = new MappingToExternalEntities();
    $old_id = 999;
    $new_id = 500;
    $type = 'testMapping';
    $mapping->create(array(
      'old_id' => $old_id,
      'type' => $type,
      'new_id' => $new_id,
    ));
    $result = $this->invokeMethod($this->MP2Migrator, 'getImportedMapping', array('testMapping'));
    expect($result[$old_id])->equals($new_id);
  }
  
}
