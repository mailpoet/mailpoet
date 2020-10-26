<?php

namespace MailPoet\Test\Config;

use Helper\Database;
use MailPoet\Config\Activator;
use MailPoet\Config\MP2Migrator;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\CustomField;
use MailPoet\Models\Form;
use MailPoet\Models\MappingToExternalEntities;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Idiorm\ORM;

class MP2MigratorTest extends \MailPoetTest {
  public $MP2Migrator;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->MP2Migrator = new MP2Migrator($this->settings, ContainerWrapper::getInstance()->get(Activator::class));
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
    $WPUsersCount = ORM::for_table($wpdb->base_prefix . 'users')->count(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
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
    $lastImportedUserID = $this->settings->get('last_imported_user_id', 0);
    expect($lastImportedUserID)->equals(0);

    // Check if the last imported list ID is 0
    $lastImportedListID = $this->settings->get('last_imported_list_id', 0);
    expect($lastImportedListID)->equals(0);
  }

  /**
   * Test the stopImport function
   *
   */
  public function testStopImport() {
    delete_option('mailpoet_stopImport');
    $this->MP2Migrator->stopImport();
    $value = $this->settings->get('import_stopped', false);
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
    expect(Segment::count())->equals(4); // two regular lists, WP users list, WooCommerce customers list (not imported)

    // Check a segment data
    $this->initImport();
    $id = 999;
    $name = 'Test list';
    $description = 'Description of the test list';
    $timestamp = 1486319877;
    $wpdb->insert($wpdb->prefix . 'wysija_list', [
      'list_id' => $id,
      'name' => $name,
      'description' => $description,
      'is_enabled' => 1,
      'is_public' => 1,
      'created_at' => $timestamp,
    ]);
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
    $settings = [
      'required' => '1',
      'validate' => 'onlyLetterSp',
    ];
    $wpdb->insert($wpdb->prefix . 'wysija_custom_field', [
      'id' => $id,
      'name' => $name,
      'type' => $type,
      'required' => $required,
      'settings' => serialize($settings),
    ]);
    $this->invokeMethod($this->MP2Migrator, 'importCustomFields');
    $table = MP_CUSTOM_FIELDS_TABLE;
    $customField = $wpdb->get_row("SELECT * FROM $table WHERE id=$id");
    expect($customField->id)->equals($id);
    expect($customField->name)->equals($name);
    expect($customField->type)->equals('text');
    $customFieldParams = unserialize($customField->params);
    expect($customFieldParams['required'])->equals($settings['required']);
    expect($customFieldParams['validate'])->equals('alphanum');
    expect($customFieldParams['label'])->equals($name);
  }

  public function testImportSubscribers() {
    global $wpdb;

    // Check a subscriber data
    $this->initImport();
    $id = 999;
    $wpId = 1;
    $email = 'test@test.com';
    $firstname = 'Test firstname';
    $lastname = 'Test lastname';
    $ip = '127.0.0.1';
    $confirmedIp = $ip;
    $wpdb->insert($wpdb->prefix . 'wysija_user', [
      'user_id' => $id,
      'wpuser_id' => $wpId,
      'email' => $email,
      'firstname' => $firstname,
      'lastname' => $lastname,
      'ip' => $ip,
      'confirmed_ip' => $confirmedIp,
      'status' => '1',
    ]);
    $wpdb->insert($wpdb->prefix . 'wysija_user', [
      'user_id' => $id + 1,
      'wpuser_id' => $wpId,
      'email' => '1' . $email,
      'firstname' => $firstname,
      'lastname' => $lastname,
      'ip' => $ip,
      'confirmed_ip' => $confirmedIp,
      'status' => '0',
    ]);
    $this->invokeMethod($this->MP2Migrator, 'importSubscribers');
    $table = MP_SUBSCRIBERS_TABLE;
    $subscribers = $wpdb->get_results("SELECT * FROM $table WHERE email LIKE '%$email' ORDER BY id");
    expect($subscribers[0]->status)->equals('subscribed');
    expect($subscribers[1]->status)->equals('unconfirmed');
  }

  public function testImportSubscribersWithDblOptinDisabled() {
    global $wpdb;

    $this->initImport();
    $values = ['confirm_dbleoptin' => '0'];
    $encodedOption = base64_encode(serialize($values));
    update_option('wysija', $encodedOption);

    $id = 999;
    $wpId = 1;
    $email = 'test@test.com';
    $firstname = 'Test firstname';
    $lastname = 'Test lastname';
    $ip = '127.0.0.1';
    $confirmedIp = $ip;
    $wpdb->insert($wpdb->prefix . 'wysija_user', [
      'user_id' => $id,
      'wpuser_id' => $wpId,
      'email' => $email,
      'firstname' => $firstname,
      'lastname' => $lastname,
      'ip' => $ip,
      'confirmed_ip' => $confirmedIp,
      'status' => '1',
    ]);
    $wpdb->insert($wpdb->prefix . 'wysija_user', [
      'user_id' => $id + 1,
      'wpuser_id' => $wpId,
      'email' => '1' . $email,
      'firstname' => $firstname,
      'lastname' => $lastname,
      'ip' => $ip,
      'confirmed_ip' => $confirmedIp,
      'status' => '0',
    ]);
    $this->invokeMethod($this->MP2Migrator, 'loadDoubleOptinSettings');
    $this->invokeMethod($this->MP2Migrator, 'importSubscribers');
    $table = MP_SUBSCRIBERS_TABLE;
    $subscribers = $wpdb->get_results("SELECT * FROM $table WHERE email LIKE '%$email' ORDER BY id");
    expect($subscribers[0]->status)->equals('subscribed');
    expect($subscribers[1]->status)->equals('subscribed');
  }

  /**
   * Test the importSubscribers function
   *
   * @global object $wpdb
   */
  public function testSubscribersStatus() {
    global $wpdb;

    $this->initImport();
    $id = 999;
    $wpId = 1;
    $email = 'test@test.com';
    $firstname = 'Test firstname';
    $lastname = 'Test lastname';
    $ip = '127.0.0.1';
    $confirmedIp = $ip;
    $wpdb->insert($wpdb->prefix . 'wysija_user', [
      'user_id' => $id,
      'wpuser_id' => $wpId,
      'email' => $email,
      'firstname' => $firstname,
      'lastname' => $lastname,
      'ip' => $ip,
      'confirmed_ip' => $confirmedIp,
      'status' => '1',
    ]);
    $this->invokeMethod($this->MP2Migrator, 'importSubscribers');
    $table = MP_SUBSCRIBERS_TABLE;
    $subscriber = $wpdb->get_row("SELECT * FROM $table WHERE email='$email'");
    expect($subscriber->email)->equals($email);
    expect($subscriber->first_name)->equals($firstname); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($subscriber->last_name)->equals($lastname); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($subscriber->subscribed_ip)->equals($ip); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($subscriber->confirmed_ip)->equals($confirmedIp); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
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
    $listId = 998;
    $listName = 'Test list';
    $description = 'Description of the test list';
    $timestamp = 1486319877;
    $wpdb->insert($wpdb->prefix . 'wysija_list', [
      'list_id' => $listId,
      'name' => $listName,
      'description' => $description,
      'is_enabled' => 1,
      'is_public' => 1,
      'created_at' => $timestamp,
    ]);

    // Insert a user
    $userId = 999;
    $wpId = 1;
    $email = 'test@test.com';
    $wpdb->insert($wpdb->prefix . 'wysija_user', [
      'user_id' => $userId,
      'wpuser_id' => $wpId,
      'email' => $email,
    ]);

    // Insert a user list
    $wpdb->insert($wpdb->prefix . 'wysija_user_list', [
      'list_id' => $listId,
      'user_id' => $userId,
      'sub_date' => $timestamp,
    ]);

    $this->invokeMethod($this->MP2Migrator, 'importSegments');
    $this->invokeMethod($this->MP2Migrator, 'importSubscribers');
    $importedSegmentsMapping = $this->MP2Migrator->getImportedMapping('segments');
    $importedSubscribersMapping = $this->MP2Migrator->getImportedMapping('subscribers');
    $table = MP_SUBSCRIBER_SEGMENT_TABLE;
    $segmentId = $importedSegmentsMapping[$listId];
    $subscriberId = $importedSubscribersMapping[$userId];
    $subscriberSegment = $wpdb->get_row("SELECT * FROM $table WHERE subscriber_id='$subscriberId' AND segment_id='$segmentId'");
    expect($subscriberSegment)->notNull();
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
    $cfId = 1;
    $cfName = 'Custom field key';
    $cfType = 'input';
    $cfRequired = 1;
    $cfSettings = [
      'required' => '1',
      'validate' => 'onlyLetterSp',
    ];
    $wpdb->insert($wpdb->prefix . 'wysija_custom_field', [
      'id' => $cfId,
      'name' => $cfName,
      'type' => $cfType,
      'required' => $cfRequired,
      'settings' => serialize($cfSettings),
    ]);

    // Insert a user
    $userId = 999;
    $wpId = 1;
    $email = 'test@test.com';
    $customFieldValue = 'Test custom field value';
    $wpdb->insert($wpdb->prefix . 'wysija_user', [
      'user_id' => $userId,
      'wpuser_id' => $wpId,
      'email' => $email,
      'cf_' . $cfId => $customFieldValue,
    ]);

    $this->invokeMethod($this->MP2Migrator, 'importCustomFields');
    $this->invokeMethod($this->MP2Migrator, 'importSubscribers');
    $importedSubscribersMapping = $this->MP2Migrator->getImportedMapping('subscribers');
    $table = MP_SUBSCRIBER_CUSTOM_FIELD_TABLE;
    $subscriberId = $importedSubscribersMapping[$userId];
    $subscriberCustomField = $wpdb->get_row("SELECT * FROM $table WHERE subscriber_id='$subscriberId' AND custom_field_id='$cfId'");
    expect($subscriberCustomField->value)->equals($customFieldValue);
  }

  /**
   * Test the getImportedMapping function
   *
   */
  public function testGetImportedMapping() {
    $this->initImport();
    $mapping = new MappingToExternalEntities();
    $oldId = 999;
    $newId = 500;
    $type = 'testMapping';
    $mapping->create([
      'old_id' => $oldId,
      'type' => $type,
      'new_id' => $newId,
    ]);
    $result = $this->invokeMethod($this->MP2Migrator, 'getImportedMapping', ['testMapping']);
    expect($result[$oldId])->equals($newId);
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
    $listId = 2;

    // Insert a MP2 list
    $wpdb->insert($wpdb->prefix . 'wysija_list', [
      'list_id' => $listId,
      'name' => 'Test list',
      'description' => 'Test list description',
      'is_enabled' => 1,
      'is_public' => 1,
      'created_at' => 1486319877,
    ]);
    $this->invokeMethod($this->MP2Migrator, 'importSegments');
    $importedSegmentsMapping = $this->MP2Migrator->getImportedMapping('segments');

    // Insert a MP2 form
    $data = [
      'version' => 0.4,
      'settings' => [
        'on_success' => 'message',
        'success_message' => 'Test message',
        'lists' => [$listId],
        'lists_selected_by' => 'admin',
      ],
      'body' => [
        [
          'name' => 'E-mail',
          'type' => 'input',
          'field' => 'email',
          'params' => [
            'label' => 'E-mail',
            'required' => 1,
          ],
        ],
      ],
    ];
    $wpdb->insert($wpdb->prefix . 'wysija_form', [
      'form_id' => $id,
      'name' => $name,
      'data' => base64_encode(serialize($data)),
    ]);
    $this->invokeMethod($this->MP2Migrator, 'importForms');
    $table = MP_FORMS_TABLE;
    $form = $wpdb->get_row("SELECT * FROM $table WHERE id=" . 1);
    expect($form->name)->equals($name);
    $settings = unserialize(($form->settings));
    expect($settings['on_success'])->equals('message');
    expect($settings['success_message'])->equals('Test message');
    expect($settings['segments'][0])->equals($importedSegmentsMapping[$listId]);
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

    $result = $this->invokeMethod($this->MP2Migrator, 'replaceMP2Shortcodes', ['[total_subscribers]']);
    expect($result)->equals('[mailpoet_subscribers_count]');

    $result = $this->invokeMethod($this->MP2Migrator, 'replaceMP2Shortcodes', ['Total: [total_subscribers]']);
    expect($result)->equals('Total: [mailpoet_subscribers_count]');

    $result = $this->invokeMethod($this->MP2Migrator, 'replaceMP2Shortcodes', ['Total: [total_subscribers] found']);
    expect($result)->equals('Total: [mailpoet_subscribers_count] found');

    $result = $this->invokeMethod($this->MP2Migrator, 'replaceMP2Shortcodes', ['[wysija_subscribers_count list_id="1,2" ]']);
    expect($result)->notEquals('mailpoet_subscribers_count segments=1,2');

    $this->loadMP2Fixtures();
    $this->invokeMethod($this->MP2Migrator, 'importSegments');
    $result = $this->invokeMethod($this->MP2Migrator, 'replaceMP2Shortcodes', ['[wysija_subscribers_count list_id="1,2" ]']);
    $importedSegmentsMapping = $this->MP2Migrator->getImportedMapping('segments');
    expect($result)->equals(sprintf('[mailpoet_subscribers_count segments=%d,%d]', $importedSegmentsMapping[1], $importedSegmentsMapping[2]));
  }

  /**
   * Test the getMappedSegmentIds function
   *
   */
  public function testGetMappedSegmentIds() {
    $this->initImport();

    $lists = [1, 2];
    $this->loadMP2Fixtures();
    $this->invokeMethod($this->MP2Migrator, 'importSegments');
    $importedSegmentsMapping = $this->MP2Migrator->getImportedMapping('segments');
    $result = $this->invokeMethod($this->MP2Migrator, 'getMappedSegmentIds', [$lists]);
    $expectedLists = [$importedSegmentsMapping[1],$importedSegmentsMapping[2]];
    expect($result)->equals($expectedLists);
  }

  /**
   * Test the replaceListIds function
   *
   */
  public function testReplaceListIds() {
    $this->initImport();

    $lists = [
      [
        'list_id' => 1,
        'name' => 'List 1',
        ],
      [
        'list_id' => 2,
        'name' => 'List 2',
        ],
    ];
    $this->loadMP2Fixtures();
    $this->invokeMethod($this->MP2Migrator, 'importSegments');
    $importedSegmentsMapping = $this->MP2Migrator->getImportedMapping('segments');
    $result = $this->invokeMethod($this->MP2Migrator, 'replaceListIds', [$lists]);
    $expectedLists = [
      [
        'id' => $importedSegmentsMapping[1],
        'name' => 'List 1',
        ],
      [
        'id' => $importedSegmentsMapping[2],
        'name' => 'List 2',
        ],
    ];
    expect($result)->equals($expectedLists);
  }

  /**
   * Test the mapFrequencyInterval function
   *
   */
  public function testMapFrequencyInterval() {
    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', ['one_min']);
    expect($result)->equals(1);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', ['two_min']);
    expect($result)->equals(2);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', ['five_min']);
    expect($result)->equals(5);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', ['ten_min']);
    expect($result)->equals(10);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', ['fifteen_min']);
    expect($result)->equals(15);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', ['thirty_min']);
    expect($result)->equals(15);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', ['hourly']);
    expect($result)->equals(15);

    $result = $this->invokeMethod($this->MP2Migrator, 'mapFrequencyInterval', ['two_hours']);
    expect($result)->equals(15);
  }

  /**
   * Test the importSettings function
   *
   */
  public function testImportSettings() {
    $this->loadMP2OptionsFixtures();

    $this->invokeMethod($this->MP2Migrator, 'importSettings');

    $sender = $this->settings->get('sender');
    expect($sender['name'])->equals('Sender');
    expect($sender['address'])->equals('sender@email.com');

    $replyTo = $this->settings->get('reply_to');
    expect($replyTo['name'])->equals('Reply');
    expect($replyTo['address'])->equals('reply@email.com');

    $bounce = $this->settings->get('bounce');
    expect($bounce['address'])->equals('bounce@email.com');

    $notification = $this->settings->get('notification');
    expect($notification['address'])->equals('notification@email.com');

    $subscribe = $this->settings->get('subscribe');
    expect($subscribe['on_comment']['enabled'])->equals(1);
    expect($subscribe['on_comment']['label'])->equals('Oui, ajoutez moi à votre liste de diffusion !!!');
    expect($subscribe['on_register']['enabled'])->equals(1);
    expect($subscribe['on_register']['label'])->equals('Oui, ajoutez moi à votre liste de diffusion 2');

    $subscription = $this->settings->get('subscription');
    expect($subscription['pages']['unsubscribe'])->equals(2);
    expect($subscription['pages']['confirmation'])->equals(4);
    expect($subscription['pages']['manage'])->equals(4);

    $signupConfirmation = $this->settings->get('signup_confirmation');
    expect($signupConfirmation['enabled'])->equals(1);

    $analytics = $this->settings->get('analytics');
    expect($analytics['enabled'])->equals(1);

    $mtaGroup = $this->settings->get('mta_group');
    expect($mtaGroup)->equals('smtp');

    $mta = $this->settings->get('mta');
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
    $wysijaOptions = [
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
       [
        'ctaupdate' => 1,
       ],
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
       [
        0 => '15',
        1 => '3',
       ],
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
       [
        0 => '15',
       ],
      'commentform_linkname' => 'Oui, ajoutez moi à votre liste de diffusion !!!',
      'registerform' => 1,
      'registerform_linkname' => 'Oui, ajoutez moi à votre liste de diffusion 2',
      'registerform_lists' =>
       [
        0 => '12',
        1 => '11',
        2 => '8',
       ],
      'viewinbrowser_linkname' => 'Problèmes d\'affichage ?? [link]Affichez cette newsletter dans votre navigateur.[/link]',
      'unsubscribe_linkname' => 'Se désabonner...',
      'analytics' => '1',
      'subscribers_count_lists' =>
       [
        0 => '15',
       ],
      'premium_key' => '',
      'premium_val' => '',
      'last_save' => 1498810541,
      'sending_emails_each' => 'five_min',
      'sending_emails_number' => '25',
      'sending_method' => 'smtp',
      'manage_subscriptions_lists' =>
       [
        0 => '3',
        1 => '12',
        2 => '11',
       ],
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
    ];
    update_option('wysija', base64_encode(serialize($wysijaOptions)));
  }
}
