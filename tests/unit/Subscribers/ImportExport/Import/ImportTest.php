<?php

use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\Import\Import;
use MailPoet\Util\Helpers;

class ImportTest extends MailPoetTest {
  function _before() {
    $custom_field = CustomField::create();
    $custom_field->name = 'country';
    $custom_field->type = 'text';
    $custom_field->save();
    $this->subscribers_custom_fields = array((string)$custom_field->id);
    $this->segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1'));
    $this->segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2'));
    $this->data = array(
      'subscribers' => array(
        array(
          'Adam',
          'Smith',
          'adam@smith.com',
          'France'
        ),
        array(
          'Mary',
          'Jane',
          'mary@jane.com',
          'Brazil'
        )
      ),
      'columns' => array(
        'first_name' => array('index' => 0),
        'last_name' => array('index' => 1),
        'email' => array('index' => 2),
        (string)$custom_field->id => array('index' => 3)
      ),
      'segments' => array(
        $this->segment_1->id
      ),
      'timestamp' => time(),
      'updateSubscribers' => true
    );
    $this->subscribers_fields = array(
      'first_name',
      'last_name',
      'email'
    );
    $this->import = new Import($this->data);
    $this->subscribers_data = $this->import->transformSubscribersData(
      $this->data['subscribers'],
      $this->data['columns']
    );
  }

  function testItConstructs() {
    expect(is_array($this->import->subscribers_data))->true();
    expect($this->import->segments_ids)->equals($this->data['segments']);
    expect(is_array($this->import->subscribers_fields))->true();
    expect(is_array($this->import->subscribers_custom_fields))->true();
    expect($this->import->subscribers_count)->equals(2);
    expect($this->import->created_at)->notEmpty();
    expect($this->import->updated_at)->notEmpty();
  }

  function testItChecksForRequiredDataFields() {
    $data = $this->data;
    // exception should be thrown when one or more fields do not exist
    unset($data['timestamp']);
    try {
      $this->import->validateImportData($data);
      self::fail('Missing or invalid data exception not thrown.');
    } catch(Exception $e) {
      expect($e->getMessage())->equals('Missing or invalid import data.');
    }
    // exception should not be thrown when all fields exist
    $this->import->validateImportData($this->data);
  }

  function testItValidatesColumnNames() {
    $data = $this->data;
    $data['columns']['test) values ((ExtractValue(1,CONCAT(0x5c, (SELECT version())))))%23'] = true;
    try {
      $this->import->validateImportData($data);
      self::fail('Missing or invalid data exception not thrown.');
    } catch(Exception $e) {
      expect($e->getMessage())->equals('Missing or invalid import data.');
    }
  }

  function testItTransformsSubscribers() {
    $custom_field = $this->subscribers_custom_fields[0];
    expect($this->import->subscribers_data['first_name'][0])
      ->equals($this->data['subscribers'][0][0]);
    expect($this->import->subscribers_data['last_name'][0])
      ->equals($this->data['subscribers'][0][1]);
    expect($this->import->subscribers_data['email'][0])
      ->equals($this->data['subscribers'][0][2]);
    expect($this->import->subscribers_data[$custom_field][0])
      ->equals($this->data['subscribers'][0][3]);
  }

  function testItSplitsSubscribers() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(
      array(
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'Adam@Smith.com',
        'wp_user_id' => 1
      ));
    $subscriber->save();
    list($existing_subscribers, $new_subscribers, $wp_users, ) = $this->import->splitSubscribersData(
      $this->subscribers_data, $this->subscribers_fields
    );
    expect($existing_subscribers['email'][0])->equals($this->subscribers_data['email'][0]);
    expect($new_subscribers['email'][0])->equals($this->subscribers_data['email'][1]);
    expect($wp_users[0])->equals($subscriber->wp_user_id);
  }

  function testItAddsMissingRequiredFieldsToSubscribersObject() {
    $data = array(
      'subscribers' => array(
        array(
          'adam@smith.com'
        ),
        array(
          'mary@jane.com'
        )
      ),
      'columns' => array(
        'email' => array('index' => 0)
      ),
      'segments' => array(1),
      'timestamp' => time(),
      'updateSubscribers' => true
    );
    $import = new Import($data);
    $subscribers_data = array(
      'data' => $import->subscribers_data,
      'fields' => $import->subscribers_fields
    );
    $result = $import->addMissingRequiredFields(
      $subscribers_data
    );
    // "created_at", "status", "first_name" and "last_name" fields are added and populated with default values
    expect(in_array('status', $result['fields']))->true();
    expect(count($result['data']['status']))->equals($import->subscribers_count);
    expect($result['data']['status'][0])->equals($import->required_subscribers_fields['status']);
    expect(in_array('first_name', $result['fields']))->true();
    expect(count($result['data']['first_name']))->equals($import->subscribers_count);
    expect($result['data']['first_name'][0])->equals($import->required_subscribers_fields['first_name']);
    expect(in_array('last_name', $result['fields']))->true();
    expect(count($result['data']['last_name']))->equals($import->subscribers_count);
    expect($result['data']['last_name'][0])->equals($import->required_subscribers_fields['last_name']);
    expect(in_array('created_at', $result['fields']))->true();
    expect(count($result['data']['created_at']))->equals($import->subscribers_count);
    expect($result['data']['created_at'][0])->equals($import->created_at);
  }

  function testItGetsSubscriberFields() {
    $data = array(
      'one',
      'two',
      39
    );
    $fields = $this->import->getSubscribersFields($data);
    expect($fields)->equals(
      array(
        'one',
        'two'
      ));
  }

  function testItGetsCustomSubscribersFields() {
    $data = array(
      'one',
      'two',
      39
    );
    $fields = $this->import->getCustomSubscribersFields($data);
    expect($fields)->equals(array(39));
  }

  function testItFiltersSubscribersStatus() {
    $subscribers_data = array(
      'fields' => array('status'),
      'data' => array(
        'status' => array(
          #subscribed
          'subscribed',
          'confirmed',
          1,
          '1',
          'true',
          #unconfirmed
          'unconfirmed',
          0,
          "0",
          #unsubscribed
          'unsubscribed',
          -1,
          '-1',
          'false',
          #bounced
          'bounced',
          #unexpected
          'qwerty',
          null
        ),
      )
    );
    $result = $this->import->filterSubscribersStatus($subscribers_data);
    expect($result['data'])->equals(
      array(
        'status' => array(
          'subscribed',
          'subscribed',
          'subscribed',
          'subscribed',
          'subscribed',
          'unconfirmed',
          'unconfirmed',
          'unconfirmed',
          'unsubscribed',
          'unsubscribed',
          'unsubscribed',
          'unsubscribed',
          'bounced',
          'subscribed',
          'subscribed'
        )
      )
    );
  }

  function testItAddsOrUpdatesSubscribers() {
    $subscribers_data = array(
      'data' => $this->subscribers_data,
      'fields' => $this->subscribers_fields
    );
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribers_data
    );
    $subscribers = Subscriber::findArray();
    expect(count($subscribers))->equals(2);
    expect($subscribers[0]['email'])
      ->equals($subscribers_data['data']['email'][0]);
    $subscribers_data['data']['first_name'][1] = 'MaryJane';
    $this->import->createOrUpdateSubscribers(
      'update',
      $subscribers_data,
      $custom_fields = false
    );
    $subscribers = Subscriber::findArray();
    expect($subscribers[1]['first_name'])
      ->equals($subscribers_data['data']['first_name'][1]);
  }

  function testItDeletesTrashedSubscribers() {
    $subscribers_data = array(
      'data' => $this->subscribers_data,
      'fields' => $this->subscribers_fields
    );
    $subscribers_data['data']['deleted_at'] = array(
      null,
      date('Y-m-d H:i:s')
    );
    $subscribers_data['fields'][] = 'deleted_at';
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribers_data
    );
    $db_subscribers = Helpers::arrayColumn(
      Subscriber::select('id')
        ->findArray(),
      'id'
    );
    expect(count($db_subscribers))->equals(2);
    $this->import->addSubscribersToSegments(
      $db_subscribers,
      array($this->segment_1->id, $this->segment_2->id)
    );
    $subscribers_segments = SubscriberSegment::findArray();
    expect(count($subscribers_segments))->equals(4);
    $this->import->deleteExistingTrashedSubscribers(
      $subscribers_data['data']
    );
    $subscribers_segments = SubscriberSegment::findArray();
    $db_subscribers = Subscriber::findArray();
    expect(count($subscribers_segments))->equals(2);
    expect(count($db_subscribers))->equals(1);
  }

  function testItCreatesOrUpdatesCustomFields() {
    $subscribers_data = array(
      'data' => $this->subscribers_data,
      'fields' => $this->subscribers_fields
    );
    $custom_field = $this->subscribers_custom_fields[0];
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribers_data,
      $this->subscribers_fields
    );
    $db_subscribers = Subscriber::selectMany(array('id','email'))->findArray();
    $this->import->createOrUpdateCustomFields(
      'create',
      $db_subscribers,
      $subscribers_data,
      $this->subscribers_custom_fields
    );
    $subscribers_custom_fields = SubscriberCustomField::findArray();
    expect(count($subscribers_custom_fields))->equals(2);
    expect($subscribers_custom_fields[0]['value'])
      ->equals($subscribers_data['data'][$custom_field][0]);
    $subscribers_data[$custom_field][1] = 'Rio';
    $this->import->createOrUpdateCustomFields(
      'update',
      $db_subscribers,
      $subscribers_data,
      $this->subscribers_custom_fields
    );
    $subscribers_custom_fields = SubscriberCustomField::findArray();
    expect($subscribers_custom_fields[1]['value'])
      ->equals($subscribers_data['data'][$custom_field][1]);
  }

  function testItAddsSubscribersToSegments() {
    $subscribers_data = array(
      'data' => $this->subscribers_data,
      'fields' => $this->subscribers_fields
    );
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribers_data,
      $this->subscribers_fields
    );
    $db_subscribers = Helpers::arrayColumn(
      Subscriber::select('id')
        ->findArray(),
      'id'
    );
    $this->import->addSubscribersToSegments(
      $db_subscribers,
      array($this->segment_1->id, $this->segment_2->id)
    );
    // 2 subscribers * 2 segments
    foreach($db_subscribers as $db_subscriber) {
      $subscriber_segment_1 = SubscriberSegment::where('subscriber_id', $db_subscriber)
        ->where('segment_id', $this->segment_1->id)
        ->findOne();
      expect($subscriber_segment_1)->notEmpty();
      $subscriber_segment_2 = SubscriberSegment::where('subscriber_id', $db_subscriber)
        ->where('segment_id', $this->segment_2->id)
        ->findOne();
      expect($subscriber_segment_2)->notEmpty();
    }
  }

  function testItDeletesExistingTrashedSubscribers() {
    $subscribers_data = array(
      'data' => $this->subscribers_data,
      'fields' => $this->subscribers_fields
    );
    $subscribers_data['fields'][] = 'deleted_at';
    $subscribers_data['data']['deleted_at'] = array(
      null,
      date('Y-m-d H:i:s')
    );
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribers_data
    );
  }

  function testItUpdatesSubscribers() {
    $result = $this->import->process();
    expect($result['updated'])->equals(0);
    $result = $this->import->process();
    expect($result['updated'])->equals(2);
    $this->import->update_subscribers = false;
    $result = $this->import->process();
    expect($result['updated'])->equals(0);
  }

  function testItDoesNotUpdateExistingSubscribersStatusWhenStatusColumnIsNotPresent() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(
      array(
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'Adam@Smith.com',
        'status' => 'unsubscribed'
      ));
    $subscriber->save();
    $result = $this->import->process();
    $updated_subscriber = Subscriber::where('email', $subscriber->email)->findOne();
    expect($updated_subscriber->status)->equals('unsubscribed');
  }

  function testItUpdatesExistingSubscribersStatusWhenStatusColumnIsPresent() {
    $data = $this->data;
    $data['columns']['status'] = array('index' => 4);
    $data['subscribers'][0][] = 'unsubscribed';
    $data['subscribers'][1][] = 'subscribed';
    $import = new Import($data);
    $existing_subscriber = Subscriber::create();
    $existing_subscriber->hydrate(
      array(
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'Adam@Smith.com',
        'status' => 'subscribed'
      ));
    $existing_subscriber->save();
    $result = $import->process();
    $updated_subscriber = Subscriber::where('email', $existing_subscriber->email)->findOne();
    expect($updated_subscriber->status)->equals('unsubscribed');
  }


  function testItRunsImport() {
    $result = $this->import->process();
    expect($result['created'])->equals(2);
    Subscriber::where('email', 'mary@jane.com')
      ->findOne()
      ->delete();
    $timestamp = time() + 1;
    $this->import->created_at = $this->import->required_subscribers_fields['created_at'] = date('Y-m-d H:i:s', $timestamp);
    $this->import->updated_at = date('Y-m-d H:i:s', $timestamp + 1);
    $result = $this->import->process();
    expect($result['created'])->equals(1);
    $db_subscribers = Helpers::arrayColumn(
      Subscriber::select('id')->findArray(),
      'id'
    );
    // subscribers must be added to segments
    foreach($db_subscribers as $db_subscriber) {
      $subscriber_segment = SubscriberSegment::where('subscriber_id', $db_subscriber)
        ->where('segment_id', $this->data['segments'][0])
        ->findOne();
      expect($subscriber_segment)->notEmpty();
    }
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
  }
}