<?php

namespace MailPoet\Test\Subscribers\ImportExport\Import;

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\Import\Import;
use MailPoetVendor\Idiorm\ORM;

class ImportTest extends \MailPoetTest {
  function _before() {
    $custom_field = CustomField::create();
    $custom_field->name = 'country';
    $custom_field->type = 'text';
    $custom_field->save();
    $this->subscribers_custom_fields = [(string)$custom_field->id];
    $this->segment_1 = Segment::createOrUpdate(['name' => 'Segment 1']);
    $this->segment_2 = Segment::createOrUpdate(['name' => 'Segment 2']);
    $this->test_data = [
      'subscribers' => [
        [
          'Adam',
          'Smith',
          'Adam@smith.com', // capitalized to test normalization
          'France',
        ],
        [
          'Mary',
          'Jane',
          'mary@jane.com',
          'Brazil',
        ],
      ],
      'columns' => [
        'first_name' => ['index' => 0],
        'last_name' => ['index' => 1],
        'email' => ['index' => 2],
        (string)$custom_field->id => ['index' => 3],
      ],
      'segments' => [
        $this->segment_1->id,
      ],
      'timestamp' => time(),
      'updateSubscribers' => true,
    ];
    $this->subscribers_fields = [
      'first_name',
      'last_name',
      'email',
    ];
    $this->import = new Import($this->test_data);
    $this->subscribers_data = $this->import->transformSubscribersData(
      $this->test_data['subscribers'],
      $this->test_data['columns']
    );
  }

  function testItConstructs() {
    expect(is_array($this->import->subscribers_data))->true();
    expect($this->import->segments_ids)->equals($this->test_data['segments']);
    expect(is_array($this->import->subscribers_fields))->true();
    expect(is_array($this->import->subscribers_custom_fields))->true();
    expect($this->import->subscribers_count)->equals(2);
    expect($this->import->created_at)->notEmpty();
    expect($this->import->updated_at)->notEmpty();
  }

  function testItChecksForRequiredDataFields() {
    $data = $this->test_data;
    // exception should be thrown when one or more fields do not exist
    unset($data['timestamp']);
    try {
      $this->import->validateImportData($data);
      self::fail('Missing or invalid data exception not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Missing or invalid import data.');
    }
    // exception should not be thrown when all fields exist
    $this->import->validateImportData($this->test_data);
  }

  function testItValidatesColumnNames() {
    $data = $this->test_data;
    $data['columns']['test) values ((ExtractValue(1,CONCAT(0x5c, (SELECT version())))))%23'] = true;
    try {
      $this->import->validateImportData($data);
      self::fail('Missing or invalid data exception not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Missing or invalid import data.');
    }
  }

  function testItValidatesSubscribersEmail() {
    $validation_rules = ['email' => 'email'];

    // invalid email is removed from data object
    $data['email'] = [
      'àdam@smîth.com',
      'jane@doe.com',
    ];
    $result = $this->import->validateSubscribersData($data, $validation_rules);
    expect($result['email'])->count(1);
    expect($result['email'][0])->equals('jane@doe.com');

    // valid email passes validation
    $data['email'] = [
      'adam@smith.com',
      'jane@doe.com',
    ];
    $result = $this->import->validateSubscribersData($data, $validation_rules);
    expect($result)->equals($data);
  }

  function testItThrowsErrorWhenNoValidSubscribersAreFoundDuringImport() {
    $data = [
      'subscribers' => [
        [
          'Adam',
          'Smith',
          'àdam@smîth.com',
          'France',
        ],
      ],
      'columns' => [
        'first_name' => ['index' => 0],
        'last_name' => ['index' => 1],
        'email' => ['index' => 2],
      ],
      'segments' => [],
      'timestamp' => time(),
      'updateSubscribers' => true,
    ];
    $import = new Import($data);
    try {
      $import->process();
      self::fail('No valid subscribers found exception not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('No valid subscribers were found.');
    }
  }

  function testItTransformsSubscribers() {
    $custom_field = $this->subscribers_custom_fields[0];
    expect($this->import->subscribers_data['first_name'][0])
      ->equals($this->test_data['subscribers'][0][0]);
    expect($this->import->subscribers_data['last_name'][0])
      ->equals($this->test_data['subscribers'][0][1]);
    expect($this->import->subscribers_data['email'][0])
      ->equals($this->test_data['subscribers'][0][2]);
    expect($this->import->subscribers_data[$custom_field][0])
      ->equals($this->test_data['subscribers'][0][3]);
  }

  function testItSplitsSubscribers() {
    $subscribers_data = $this->subscribers_data;
    $subscribers_data_existing = [
      [
        'first_name' => 'Johnny',
        'last_name' => 'Walker',
        'email' => 'johnny@WaLker.com',
        'wp_user_id' => 13579,
      ],  [
        'first_name' => 'Steve',
        'last_name' => 'Sorrow',
        'email' => 'sTeve.sorrow@exaMple.com',
      ],
    ];
    foreach ($subscribers_data_existing as $i => $existing_subscriber) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($existing_subscriber);
      $subscriber->save();
      $subscribers_data['first_name'][] = $existing_subscriber['first_name'];
      $subscribers_data['last_name'][] = $existing_subscriber['last_name'];
      $subscribers_data['email'][] = strtolower($existing_subscriber['email']); // import emails are always lowercase
      $subscribers_data[1][] = 'custom_field_' . $i;
    }
    list($existing_subscribers, $new_subscribers, $wp_users, ) = $this->import->splitSubscribersData(
      $subscribers_data
    );
    expect($existing_subscribers['email'][0])->equals($subscribers_data['email'][2]);
    expect($existing_subscribers['email'][1])->equals($subscribers_data['email'][3]);
    foreach ($new_subscribers as $field => $value) {
      expect($value[0])->equals($subscribers_data[$field][0]);
    }
    expect($wp_users)->equals([$subscribers_data_existing[0]['wp_user_id']]);
  }

  function testItAddsMissingRequiredFieldsToSubscribersObject() {
    $data = [
      'subscribers' => [
        [
          'adam@smith.com',
        ],
        [
          'mary@jane.com',
        ],
      ],
      'columns' => [
        'email' => ['index' => 0],
      ],
      'segments' => [1],
      'timestamp' => time(),
      'updateSubscribers' => true,
    ];
    $import = new Import($data);
    $subscribers_data = [
      'data' => $import->subscribers_data,
      'fields' => $import->subscribers_fields,
    ];
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
    $data = [
      'one',
      'two',
      39,
    ];
    $fields = $this->import->getSubscribersFields($data);
    expect($fields)->equals(
      [
        'one',
        'two',
      ]);
  }

  function testItGetsCustomSubscribersFields() {
    $data = [
      'one',
      'two',
      39,
    ];
    $fields = $this->import->getCustomSubscribersFields($data);
    expect($fields)->equals([39]);
  }

  function testItAddsOrUpdatesSubscribers() {
    $subscribers_data = [
      'data' => $this->subscribers_data,
      'fields' => $this->subscribers_fields,
    ];
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
    $subscribers_data = [
      'data' => $this->subscribers_data,
      'fields' => $this->subscribers_fields,
    ];
    $subscribers_data['data']['deleted_at'] = [
      null,
      date('Y-m-d H:i:s'),
    ];
    $subscribers_data['fields'][] = 'deleted_at';
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribers_data
    );
    $db_subscribers = array_column(
      Subscriber::select('id')
        ->findArray(),
      'id'
    );
    expect(count($db_subscribers))->equals(2);
    $this->import->addSubscribersToSegments(
      $db_subscribers,
      [$this->segment_1->id, $this->segment_2->id]
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
    $subscribers_data = [
      'data' => $this->subscribers_data,
      'fields' => $this->subscribers_fields,
    ];
    $custom_field = $this->subscribers_custom_fields[0];
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribers_data,
      $this->subscribers_fields
    );
    $db_subscribers = Subscriber::selectMany(['id','email'])->findArray();
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
    $subscribers_data = [
      'data' => $this->subscribers_data,
      'fields' => $this->subscribers_fields,
    ];
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribers_data,
      $this->subscribers_fields
    );
    $db_subscribers = array_column(
      Subscriber::select('id')
        ->findArray(),
      'id'
    );
    $this->import->addSubscribersToSegments(
      $db_subscribers,
      [$this->segment_1->id, $this->segment_2->id]
    );
    // 2 subscribers * 2 segments
    foreach ($db_subscribers as $db_subscriber) {
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
    $subscribers_data = [
      'data' => $this->subscribers_data,
      'fields' => $this->subscribers_fields,
    ];
    $subscribers_data['fields'][] = 'deleted_at';
    $subscribers_data['data']['deleted_at'] = [
      null,
      date('Y-m-d H:i:s'),
    ];
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
      [
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'Adam@Smith.com',
        'status' => 'unsubscribed',
      ]);
    $subscriber->save();
    $result = $this->import->process();
    $updated_subscriber = Subscriber::where('email', $subscriber->email)->findOne();
    expect($updated_subscriber->status)->equals('unsubscribed');
  }

  function testItDoesNotUpdateExistingSubscribersStatusWhenStatusColumnIsPresent() {
    $data = $this->test_data;
    $data['columns']['status'] = ['index' => 4];
    $data['subscribers'][0][] = 'subscribed';
    $data['subscribers'][1][] = 'subscribed';
    $import = new Import($data);
    $existing_subscriber = Subscriber::create();
    $existing_subscriber->hydrate(
      [
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'Adam@Smith.com',
        'status' => 'unsubscribed',
      ]);
    $existing_subscriber->save();
    $result = $import->process();
    $updated_subscriber = Subscriber::where('email', $existing_subscriber->email)->findOne();
    expect($updated_subscriber->status)->equals('unsubscribed');
  }

  function testItImportsNewsSubscribersWithAllAdditionalParameters() {
    $data = $this->test_data;
    $data['columns']['status'] = ['index' => 4];
    $data['subscribers'][0][] = 'unsubscribed';
    $data['subscribers'][1][] = 'unsubscribed';
    $import = new Import($data);
    $result = $import->process();
    $new_subscribers = Subscriber::whereAnyIs([
      ['email' => $data['subscribers'][0][2]],
      ['email' => $data['subscribers'][1][2]],
      ]
    )->findMany();
    expect($new_subscribers)->count(2);
    expect($new_subscribers[0]->status)->equals('subscribed');
    expect($new_subscribers[1]->status)->equals('subscribed');
    expect($new_subscribers[0]->source)->equals('imported');
    expect($new_subscribers[1]->source)->equals('imported');
    expect(strlen($new_subscribers[0]->link_token))->equals(Subscriber::LINK_TOKEN_LENGTH);
    expect(strlen($new_subscribers[1]->link_token))->equals(Subscriber::LINK_TOKEN_LENGTH);
    $test_time = date('Y-m-d H:i:s', $this->test_data['timestamp']);
    expect($new_subscribers[0]->last_subscribed_at)->equals($test_time);
    expect($new_subscribers[1]->last_subscribed_at)->equals($test_time);
  }

  function testItDoesNotUpdateExistingSubscribersLastSubscribedAtWhenItIsPresent() {
    $data = $this->test_data;
    $data['columns']['last_subscribed_at'] = ['index' => 4];
    $data['subscribers'][0][] = '2018-12-12 12:12:00';
    $data['subscribers'][1][] = '2018-12-12 12:12:00';
    $import = new Import($data);
    $existing_subscriber = Subscriber::create();
    $existing_subscriber->hydrate(
      [
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'Adam@Smith.com',
        'last_subscribed_at' => '2017-12-12 12:12:00',
      ]);
    $existing_subscriber->save();
    $import->process();
    $updated_subscriber = Subscriber::where('email', $existing_subscriber->email)->findOne();
    expect($updated_subscriber->last_subscribed_at)->equals('2017-12-12 12:12:00');
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
    $db_subscribers = array_column(
      Subscriber::select('id')->findArray(),
      'id'
    );
    // subscribers must be added to segments
    foreach ($db_subscribers as $db_subscriber) {
      $subscriber_segment = SubscriberSegment::where('subscriber_id', $db_subscriber)
        ->where('segment_id', $this->test_data['segments'][0])
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
