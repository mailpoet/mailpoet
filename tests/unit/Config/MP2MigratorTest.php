<?php
namespace MailPoet\Test\Config;

use MailPoet\Config\MP2Migrator;
use MailPoet\Models\Setting;
use MailPoet\Models\CustomField;
use MailPoet\Models\MappingToExternalEntities;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\Form;
use Helper\Database;

class MP2MigratorTest extends \MailPoetTest {

  public function _before() {
    $this->MP2Migrator = new MP2Migrator();
  }

  public function _after() {
    $this->MP2Migrator->progressbar->setTotalCount(0);
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
    // On multisite environment, there's only 1 users table that's shared by subsites
    $WPUsersCount = \ORM::for_table($wpdb->base_prefix . 'users')->count();
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
    $value = Setting::getValue('import_stopped', false);
    $stopImport = !empty($value);
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

  /**
   * Test the importForms function
   *
   * @global object $wpdb
   */
  public function testImportForms() {
    global $wpdb;

    // Check the forms number
    $this->initImport();
    $this->loadMP2Fixtures();
    $this->invokeMethod($this->MP2Migrator, 'importForms');
    expect(Form::count())->equals(2);

    // Check a form data
    $this->initImport();
    $id = 999;
    $name = 'Test form';
    $list_id = 2;

    // Insert a MP2 list
    $wpdb->insert($wpdb->prefix . 'wysija_list', array(
      'list_id' => $list_id,
      'name' => 'Test list',
      'description' => 'Test list description',
      'is_enabled' => 1,
      'is_public' => 1,
      'created_at' => 1486319877,
    ));
    $this->invokeMethod($this->MP2Migrator, 'importSegments');
    $importedSegmentsMapping = $this->MP2Migrator->getImportedMapping('segments');

    // Insert a MP2 form
    $data = array(
      'version' => 0.4,
      'settings' => array(
        'on_success' => 'message',
        'success_message' => 'Test message',
        'lists' => array($list_id),
        'lists_selected_by' => 'admin',
      ),
      'body' => array(
        array(
          'name' => 'E-mail',
          'type' => 'input',
          'field' => 'email',
          'params' => array(
            'label' => 'E-mail',
            'required' => 1,
          ),
        ),
      ),
    );
    $wpdb->insert($wpdb->prefix . 'wysija_form', array(
      'form_id' => $id,
      'name' => $name,
      'data' => base64_encode(serialize($data)),
    ));
    $this->invokeMethod($this->MP2Migrator, 'importForms');
    $table = MP_FORMS_TABLE;
    $form = $wpdb->get_row("SELECT * FROM $table WHERE id=" . 1);
    expect($form->name)->equals($name);
    $settings = unserialize(($form->settings));
    expect($settings['on_success'])->equals('message');
    expect($settings['success_message'])->equals('Test message');
    expect($settings['segments'][0])->equals($importedSegmentsMapping[$list_id]);
    $body = unserialize(($form->body));
    expect($body[0]['name'])->equals('E-mail');
    expect($body[0]['type'])->equals('text');
  }

  /**
   * Test the replaceMP2Shortcodes function
   *
   */
  public function testReplaceMP2Shortcodes() {
    $this->initImport();

    $result = $this->invokeMethod($this->MP2Migrator, 'replaceMP2Shortcodes', array('[total_subscribers]'));
    expect($result)->equals('[mailpoet_subscribers_count]');

    $result = $this->invokeMethod($this->MP2Migrator, 'replaceMP2Shortcodes', array('Total: [total_subscribers]'));
    expect($result)->equals('Total: [mailpoet_subscribers_count]');

    $result = $this->invokeMethod($this->MP2Migrator, 'replaceMP2Shortcodes', array('Total: [total_subscribers] found'));
    expect($result)->equals('Total: [mailpoet_subscribers_count] found');

    $result = $this->invokeMethod($this->MP2Migrator, 'replaceMP2Shortcodes', array('[wysija_subscribers_count list_id="1,2" ]'));
    expect($result)->notEquals('mailpoet_subscribers_count segments=1,2');

    $this->loadMP2Fixtures();
    $this->invokeMethod($this->MP2Migrator, 'importSegments');
    $result = $this->invokeMethod($this->MP2Migrator, 'replaceMP2Shortcodes', array('[wysija_subscribers_count list_id="1,2" ]'));
    $importedSegmentsMapping = $this->MP2Migrator->getImportedMapping('segments');
    expect($result)->equals(sprintf('[mailpoet_subscribers_count segments=%d,%d]', $importedSegmentsMapping[1], $importedSegmentsMapping[2]));
  }

  /**
   * Test the getMappedSegmentIds function
   *
   */
  public function testGetMappedSegmentIds() {
    $this->initImport();

    $lists = array(1, 2);
    $this->loadMP2Fixtures();
    $this->invokeMethod($this->MP2Migrator, 'importSegments');
    $importedSegmentsMapping = $this->MP2Migrator->getImportedMapping('segments');
    $result = $this->invokeMethod($this->MP2Migrator, 'getMappedSegmentIds', array($lists));
    $expected_lists = array($importedSegmentsMapping[1],$importedSegmentsMapping[2]);
    expect($result)->equals($expected_lists);
  }

  /**
   * Test the replaceListIds function
   *
   */
  public function testReplaceListIds() {
    $this->initImport();

    $lists = array(
      array(
        'list_id' => 1,
        'name' => 'List 1',
        ),
      array(
        'list_id' => 2,
        'name' => 'List 2',
        ),
    );
    $this->loadMP2Fixtures();
    $this->invokeMethod($this->MP2Migrator, 'importSegments');
    $importedSegmentsMapping = $this->MP2Migrator->getImportedMapping('segments');
    $result = $this->invokeMethod($this->MP2Migrator, 'replaceListIds', array($lists));
    $expected_lists = array(
      array(
        'id' => $importedSegmentsMapping[1],
        'name' => 'List 1',
        ),
      array(
        'id' => $importedSegmentsMapping[2],
        'name' => 'List 2',
        ),
    );
    expect($result)->equals($expected_lists);
  }

  /**
   * Test the mapFrequencyInterval function
   *
   */
  public function testMapFrequencyInterval() {
    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', array('one_min'));
    expect($result)->equals(1);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', array('two_min'));
    expect($result)->equals(2);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', array('five_min'));
    expect($result)->equals(5);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', array('ten_min'));
    expect($result)->equals(10);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', array('fifteen_min'));
    expect($result)->equals(15);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', array('thirty_min'));
    expect($result)->equals(15);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', array('hourly'));
    expect($result)->equals(15);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', array('two_hours'));
    expect($result)->equals(15);
  }

  /**
   * Test the importSettings function
   *
   */
  public function testImportSettings() {
    $this->loadMP2OptionsFixtures();

    $this->invokeMethod($this->MP2Migrator, 'importSettings');

    $sender = Setting::getValue('sender');
    expect($sender['name'])->equals('Sender');
    expect($sender['address'])->equals('sender@email.com');

    $reply_to = Setting::getValue('reply_to');
    expect($reply_to['name'])->equals('Reply');
    expect($reply_to['address'])->equals('reply@email.com');

    $bounce = Setting::getValue('bounce');
    expect($bounce['address'])->equals('bounce@email.com');

    $notification = Setting::getValue('notification');
    expect($notification['address'])->equals('notification@email.com');

    $subscribe = Setting::getValue('subscribe');
    expect($subscribe['on_comment']['enabled'])->equals(1);
    expect($subscribe['on_comment']['label'])->equals('Oui, ajoutez moi à votre liste de diffusion !!!');
    expect($subscribe['on_register']['enabled'])->equals(1);
    expect($subscribe['on_register']['label'])->equals('Oui, ajoutez moi à votre liste de diffusion 2');

    $subscription = Setting::getValue('subscription');
    expect($subscription['pages']['unsubscribe'])->equals(2);
    expect($subscription['pages']['confirmation'])->equals(4);
    expect($subscription['pages']['manage'])->equals(4);

    $signup_confirmation = Setting::getValue('signup_confirmation');
    expect($signup_confirmation['enabled'])->equals(1);

    $analytics = Setting::getValue('analytics');
    expect($analytics['enabled'])->equals(1);

    $mta_group = Setting::getValue('mta_group');
    expect($mta_group)->equals('smtp');

    $mta = Setting::getValue('mta');
    expect($mta['method'])->equals('SMTP');
    expect($mta['frequency']['emails'])->equals(25);
    expect($mta['frequency']['interval'])->equals(5);
    expect($mta['host'])->equals('smtp.mondomaine.com');
    expect($mta['port'])->equals(25);
    expect($mta['login'])->equals('login');
    expect($mta['password'])->equals('password');
    expect($mta['encryption'])->equals('ssl');
    expect($mta['authentication'])->equals(1);
  }

  /**
   * Load some MP2 fixtures
   *
   */
  private function loadMP2OptionsFixtures() {
    $wysija_options = array (
      'from_name' => 'Sender',
      'replyto_name' => 'Reply',
      'emails_notified' => 'notification@email.com',
      'from_email' => 'sender@email.com',
      'replyto_email' => 'reply@email.com',
      'default_list_id' => 1,
      'total_subscribers' => '1262',
      'importwp_list_id' => 2,
      'confirm_email_link' => 4,
      'uploadfolder' => '',
      'uploadurl' => '',
      'confirm_email_id' => 2,
      'installed' => true,
      'manage_subscriptions' => 1,
      'installed_time' => 1486319877,
      'wysija_db_version' => '2.7.7',
      'dkim_domain' => 'localhost',
      'wysija_whats_new' => '2.7.10',
      'ignore_msgs' =>
      array (
        'ctaupdate' => 1,
      ),
      'queue_sends_slow' => 1,
      'emails_notified_when_sub' => 1,
      'emails_notified_when_bounce' => false,
      'emails_notified_when_dailysummary' => 1,
      'bounce_process_auto' => false,
      'ms_bounce_process_auto' => false,
      'sharedata' => false,
      'dkim_active' => false,
      'commentform' => 1,
      'smtp_rest' => false,
      'ms_smtp_rest' => false,
      'debug_log_cron' => false,
      'debug_log_post_notif' => false,
      'debug_log_query_errors' => false,
      'debug_log_queue_process' => false,
      'debug_log_manual' => false,
      'company_address' => 'mon adresse postale',
      'commentform_lists' =>
      array (
        0 => '15',
        1 => '3',
      ),
      'unsubscribe_page' => '2',
      'confirmation_page' => '4',
      'smtp_host' => 'smtp.mondomaine.com',
      'smtp_login' => 'login',
      'smtp_password' => 'password',
      'smtp_port' => '25',
      'smtp_secure' => 'ssl',
      'test_mails' => 'test@email.com',
      'bounce_email' => 'bounce@email.com',
      'subscriptions_page' => '4',
      'html_source' => '1',
      'industry' => 'e-commerce',
      'archive_linkname' => '[wysija_archive]',
      'subscribers_count_linkname' => '[wysija_subscribers_count]',
      'archive_lists' =>
      array (
        0 => '15',
      ),
      'commentform_linkname' => 'Oui, ajoutez moi à votre liste de diffusion !!!',
      'registerform' => 1,
      'registerform_linkname' => 'Oui, ajoutez moi à votre liste de diffusion 2',
      'registerform_lists' =>
      array (
        0 => '12',
        1 => '11',
        2 => '8',
      ),
      'viewinbrowser_linkname' => 'Problèmes d\'affichage ?? [link]Affichez cette newsletter dans votre navigateur.[/link]',
      'unsubscribe_linkname' => 'Se désabonner...',
      'analytics' => '1',
      'subscribers_count_lists' =>
      array (
        0 => '15',
      ),
      'premium_key' => '',
      'premium_val' => '',
      'last_save' => 1498810541,
      'sending_emails_each' => 'five_min',
      'sending_emails_number' => '25',
      'sending_method' => 'smtp',
      'manage_subscriptions_lists' =>
      array (
        0 => '3',
        1 => '12',
        2 => '11',
      ),
      'rolescap---administrator---newsletters' => false,
      'rolescap---editor---newsletters' => false,
      'rolescap---author---newsletters' => false,
      'rolescap---contributor---newsletters' => false,
      'rolescap---subscriber---newsletters' => false,
      'rolescap---administrator---subscribers' => false,
      'rolescap---editor---subscribers' => false,
      'rolescap---author---subscribers' => false,
      'rolescap---contributor---subscribers' => false,
      'rolescap---subscriber---subscribers' => false,
      'rolescap---administrator---config' => false,
      'rolescap---editor---config' => false,
      'rolescap---author---config' => false,
      'rolescap---contributor---config' => false,
      'rolescap---subscriber---config' => false,
      'rolescap---administrator---theme_tab' => false,
      'rolescap---editor---theme_tab' => false,
      'rolescap---author---theme_tab' => false,
      'rolescap---contributor---theme_tab' => false,
      'rolescap---subscriber---theme_tab' => false,
      'rolescap---administrator---style_tab' => false,
      'rolescap---editor---style_tab' => false,
      'rolescap---author---style_tab' => false,
      'rolescap---contributor---style_tab' => false,
      'rolescap---subscriber---style_tab' => false,
    );
    update_option('wysija', base64_encode(serialize($wysija_options)));
  }

}
