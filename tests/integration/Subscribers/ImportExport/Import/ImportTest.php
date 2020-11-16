<?php

namespace MailPoet\Test\Subscribers\ImportExport\Import;

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Segments\WP;
use MailPoet\Subscribers\ImportExport\Import\Import;
use MailPoetVendor\Idiorm\ORM;

class ImportTest extends \MailPoetTest {
  public $subscribersCustomFields;
  public $subscribersData;
  public $import;
  public $subscribersFields;
  public $testData;
  public $segment2;
  public $segment1;

  /** @var WP */
  private $wpSegment;

  public function _before() {
    $this->wpSegment = $this->diContainer->get(WP::class);
    $customField = CustomField::create();
    $customField->name = 'country';
    $customField->type = 'text';
    $customField->save();
    $this->subscribersCustomFields = [(string)$customField->id];
    $this->segment1 = Segment::createOrUpdate(['name' => 'Segment 1']);
    $this->segment2 = Segment::createOrUpdate(['name' => 'Segment 2']);
    $this->testData = [
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
        (string)$customField->id => ['index' => 3],
      ],
      'segments' => [
        $this->segment1->id,
      ],
      'timestamp' => time(),
      'newSubscribersStatus' => Subscriber::STATUS_SUBSCRIBED,
      'existingSubscribersStatus' => Import::STATUS_DONT_UPDATE,
      'updateSubscribers' => true,
    ];
    $this->subscribersFields = [
      'first_name',
      'last_name',
      'email',
    ];
    $this->import = new Import($this->wpSegment, $this->testData);
    $this->subscribersData = $this->import->transformSubscribersData(
      $this->testData['subscribers'],
      $this->testData['columns']
    );
  }

  public function testItConstructs() {
    expect(is_array($this->import->subscribersData))->true();
    expect($this->import->segmentsIds)->equals($this->testData['segments']);
    expect(is_array($this->import->subscribersFields))->true();
    expect(is_array($this->import->subscribersCustomFields))->true();
    expect($this->import->subscribersCount)->equals(2);
    expect($this->import->createdAt)->notEmpty();
    expect($this->import->updatedAt)->notEmpty();
  }

  public function testItChecksForRequiredDataFields() {
    $data = $this->testData;
    // exception should be thrown when one or more fields do not exist
    unset($data['timestamp']);
    try {
      $this->import->validateImportData($data);
      self::fail('Missing or invalid data exception not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Missing or invalid import data.');
    }
    // exception should not be thrown when all fields exist
    $this->import->validateImportData($this->testData);
  }

  public function testItValidatesColumnNames() {
    $data = $this->testData;
    $data['columns']['test) values ((ExtractValue(1,CONCAT(0x5c, (SELECT version())))))%23'] = true;
    try {
      $this->import->validateImportData($data);
      self::fail('Missing or invalid data exception not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Missing or invalid import data.');
    }
  }

  public function testItValidatesSubscribersEmail() {
    $validationRules = ['email' => 'email'];

    // invalid email is removed from data object
    $data['email'] = [
      'àdam@smîth.com',
      'jane@doe.com',
    ];
    $result = $this->import->validateSubscribersData($data, $validationRules);
    expect($result['email'])->count(1);
    expect($result['email'][0])->equals('jane@doe.com');

    // valid email passes validation
    $data['email'] = [
      'adam@smith.com',
      'jane@doe.com',
    ];
    $result = $this->import->validateSubscribersData($data, $validationRules);
    expect($result)->equals($data);
  }

  public function testItThrowsErrorWhenNoValidSubscribersAreFoundDuringImport() {
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
      'newSubscribersStatus' => Subscriber::STATUS_SUBSCRIBED,
      'existingSubscribersStatus' => Import::STATUS_DONT_UPDATE,
      'updateSubscribers' => true,
    ];
    $import = new Import($this->wpSegment, $data);
    try {
      $import->process();
      self::fail('No valid subscribers found exception not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('No valid subscribers were found.');
    }
  }

  public function testItTransformsSubscribers() {
    $customField = $this->subscribersCustomFields[0];
    expect($this->import->subscribersData['first_name'][0])
      ->equals($this->testData['subscribers'][0][0]);
    expect($this->import->subscribersData['last_name'][0])
      ->equals($this->testData['subscribers'][0][1]);
    expect($this->import->subscribersData['email'][0])
      ->equals($this->testData['subscribers'][0][2]);
    expect($this->import->subscribersData[$customField][0])
      ->equals($this->testData['subscribers'][0][3]);
  }

  public function testItSplitsSubscribers() {
    $subscribersData = $this->subscribersData;
    $subscribersDataExisting = [
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
    foreach ($subscribersDataExisting as $i => $existingSubscriber) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($existingSubscriber);
      $subscriber->save();
      $subscribersData['first_name'][] = $existingSubscriber['first_name'];
      $subscribersData['last_name'][] = $existingSubscriber['last_name'];
      $subscribersData['email'][] = strtolower($existingSubscriber['email']); // import emails are always lowercase
      $subscribersData[1][] = 'custom_field_' . $i;
    }
    list($existingSubscribers, $newSubscribers, $wpUsers, ) = $this->import->splitSubscribersData(
      $subscribersData
    );
    expect($existingSubscribers['email'][0])->equals($subscribersData['email'][2]);
    expect($existingSubscribers['email'][1])->equals($subscribersData['email'][3]);
    foreach ($newSubscribers as $field => $value) {
      expect($value[0])->equals($subscribersData[$field][0]);
    }
    expect($wpUsers)->equals([$subscribersDataExisting[0]['wp_user_id']]);
  }

  public function testItAddsMissingRequiredFieldsToSubscribersObject() {
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
      'newSubscribersStatus' => Subscriber::STATUS_SUBSCRIBED,
      'existingSubscribersStatus' => Import::STATUS_DONT_UPDATE,
      'updateSubscribers' => true,
    ];
    $import = new Import($this->wpSegment, $data);
    $subscribersData = [
      'data' => $import->subscribersData,
      'fields' => $import->subscribersFields,
    ];
    $result = $import->addMissingRequiredFields(
      $subscribersData
    );
    // "created_at", "status", "first_name" and "last_name" fields are added and populated with default values
    expect(in_array('status', $result['fields']))->true();
    expect(count($result['data']['status']))->equals($import->subscribersCount);
    expect($result['data']['status'][0])->equals($import->requiredSubscribersFields['status']);
    expect(in_array('first_name', $result['fields']))->true();
    expect(count($result['data']['first_name']))->equals($import->subscribersCount);
    expect($result['data']['first_name'][0])->equals($import->requiredSubscribersFields['first_name']);
    expect(in_array('last_name', $result['fields']))->true();
    expect(count($result['data']['last_name']))->equals($import->subscribersCount);
    expect($result['data']['last_name'][0])->equals($import->requiredSubscribersFields['last_name']);
    expect(in_array('created_at', $result['fields']))->true();
    expect(count($result['data']['created_at']))->equals($import->subscribersCount);
    expect($result['data']['created_at'][0])->equals($import->createdAt);
  }

  public function testItGetsSubscriberFields() {
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

  public function testItGetsCustomSubscribersFields() {
    $data = [
      'one',
      'two',
      39,
    ];
    $fields = $this->import->getCustomSubscribersFields($data);
    expect($fields)->equals([39]);
  }

  public function testItAddsOrUpdatesSubscribers() {
    $subscribersData = [
      'data' => $this->subscribersData,
      'fields' => $this->subscribersFields,
    ];
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribersData
    );
    $subscribers = Subscriber::findArray();
    expect(count($subscribers))->equals(2);
    expect($subscribers[0]['email'])
      ->equals($subscribersData['data']['email'][0]);
    $subscribersData['data']['first_name'][1] = 'MaryJane';
    $this->import->createOrUpdateSubscribers(
      'update',
      $subscribersData,
      $customFields = false
    );
    $subscribers = Subscriber::findArray();
    expect($subscribers[1]['first_name'])
      ->equals($subscribersData['data']['first_name'][1]);
  }

  public function testItDeletesTrashedSubscribers() {
    $subscribersData = [
      'data' => $this->subscribersData,
      'fields' => $this->subscribersFields,
    ];
    $subscribersData['data']['deleted_at'] = [
      null,
      date('Y-m-d H:i:s'),
    ];
    $subscribersData['fields'][] = 'deleted_at';
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribersData
    );
    $dbSubscribers = array_column(
      Subscriber::select('id')
        ->findArray(),
      'id'
    );
    expect(count($dbSubscribers))->equals(2);
    $this->import->addSubscribersToSegments(
      $dbSubscribers,
      [$this->segment1->id, $this->segment2->id]
    );
    $subscribersSegments = SubscriberSegment::findArray();
    expect(count($subscribersSegments))->equals(4);
    $this->import->deleteExistingTrashedSubscribers(
      $subscribersData['data']
    );
    $subscribersSegments = SubscriberSegment::findArray();
    $dbSubscribers = Subscriber::findArray();
    expect(count($subscribersSegments))->equals(2);
    expect(count($dbSubscribers))->equals(1);
  }

  public function testItCreatesOrUpdatesCustomFields() {
    $subscribersData = [
      'data' => $this->subscribersData,
      'fields' => $this->subscribersFields,
    ];
    $customField = $this->subscribersCustomFields[0];
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribersData,
      $this->subscribersFields
    );
    $dbSubscribers = Subscriber::selectMany(['id','email'])->findArray();
    $this->import->createOrUpdateCustomFields(
      'create',
      $dbSubscribers,
      $subscribersData,
      $this->subscribersCustomFields
    );
    $subscribersCustomFields = SubscriberCustomField::findArray();
    expect(count($subscribersCustomFields))->equals(2);
    expect($subscribersCustomFields[0]['value'])
      ->equals($subscribersData['data'][$customField][0]);
    $subscribersData[$customField][1] = 'Rio';
    $this->import->createOrUpdateCustomFields(
      'update',
      $dbSubscribers,
      $subscribersData,
      $this->subscribersCustomFields
    );
    $subscribersCustomFields = SubscriberCustomField::findArray();
    expect($subscribersCustomFields[1]['value'])
      ->equals($subscribersData['data'][$customField][1]);
  }

  public function testItAddsSubscribersToSegments() {
    $subscribersData = [
      'data' => $this->subscribersData,
      'fields' => $this->subscribersFields,
    ];
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribersData,
      $this->subscribersFields
    );
    $dbSubscribers = array_column(
      Subscriber::select('id')
        ->findArray(),
      'id'
    );
    $this->import->addSubscribersToSegments(
      $dbSubscribers,
      [$this->segment1->id, $this->segment2->id]
    );
    // 2 subscribers * 2 segments
    foreach ($dbSubscribers as $dbSubscriber) {
      $subscriberSegment1 = SubscriberSegment::where('subscriber_id', $dbSubscriber)
        ->where('segment_id', $this->segment1->id)
        ->findOne();
      expect($subscriberSegment1)->notEmpty();
      $subscriberSegment2 = SubscriberSegment::where('subscriber_id', $dbSubscriber)
        ->where('segment_id', $this->segment2->id)
        ->findOne();
      expect($subscriberSegment2)->notEmpty();
    }
  }

  public function testItDeletesExistingTrashedSubscribers() {
    $subscribersData = [
      'data' => $this->subscribersData,
      'fields' => $this->subscribersFields,
    ];
    $subscribersData['fields'][] = 'deleted_at';
    $subscribersData['data']['deleted_at'] = [
      null,
      date('Y-m-d H:i:s'),
    ];
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribersData
    );
  }

  public function testItUpdatesSubscribers() {
    $result = $this->import->process();
    expect($result['updated'])->equals(0);
    $result = $this->import->process();
    expect($result['updated'])->equals(2);
    $this->import->updateSubscribers = false;
    $result = $this->import->process();
    expect($result['updated'])->equals(0);
  }

  public function testItDoesNotUpdateExistingSubscribersStatusWhenStatusColumnIsNotPresent() {
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
    $updatedSubscriber = Subscriber::where('email', $subscriber->email)->findOne();
    expect($updatedSubscriber->status)->equals('unsubscribed');
  }

  public function testItImportsNewsSubscribersWithAllAdditionalParameters() {
    $data = $this->testData;
    $data['columns']['status'] = ['index' => 4];
    $data['subscribers'][0][] = 'unsubscribed';
    $data['subscribers'][1][] = 'unsubscribed';
    $import = new Import($this->wpSegment, $data);
    $result = $import->process();
    $newSubscribers = Subscriber::whereAnyIs([
      ['email' => $data['subscribers'][0][2]],
      ['email' => $data['subscribers'][1][2]],
      ]
    )->findMany();
    expect($newSubscribers)->count(2);
    expect($newSubscribers[0]->status)->equals('subscribed');
    expect($newSubscribers[1]->status)->equals('subscribed');
    expect($newSubscribers[0]->source)->equals('imported');
    expect($newSubscribers[1]->source)->equals('imported');
    expect(strlen($newSubscribers[0]->link_token))->equals(Subscriber::LINK_TOKEN_LENGTH);
    expect(strlen($newSubscribers[1]->link_token))->equals(Subscriber::LINK_TOKEN_LENGTH);
    $testTime = date('Y-m-d H:i:s', $this->testData['timestamp']);
    expect($newSubscribers[0]->last_subscribed_at)->equals($testTime);
    expect($newSubscribers[1]->last_subscribed_at)->equals($testTime);
  }

  public function testItDoesNotUpdateExistingSubscribersLastSubscribedAtWhenItIsPresent() {
    $data = $this->testData;
    $data['columns']['last_subscribed_at'] = ['index' => 4];
    $data['subscribers'][0][] = '2018-12-12 12:12:00';
    $data['subscribers'][1][] = '2018-12-12 12:12:00';
    $import = new Import($this->wpSegment, $data);
    $existingSubscriber = Subscriber::create();
    $existingSubscriber->hydrate(
      [
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'Adam@Smith.com',
        'last_subscribed_at' => '2017-12-12 12:12:00',
      ]);
    $existingSubscriber->save();
    $import->process();
    $updatedSubscriber = Subscriber::where('email', $existingSubscriber->email)->findOne();
    expect($updatedSubscriber->lastSubscribedAt)->equals('2017-12-12 12:12:00');
  }

  public function testItSynchronizesWpUsers() {
    $this->tester->createWordPressUser('mary@jane.com', 'editor');
    $beforeImport = Subscriber::where('email', 'mary@jane.com')->findOne();
    $data = $this->testData;
    $import = new Import($this->wpSegment, $data);
    $import->process();
    $imported = Subscriber::where('email', 'mary@jane.com')->findOne();
    expect($imported->firstName)->equals($beforeImport->firstName); // Subscriber name was synchronized from WP
    expect($imported->firstName)->notEquals('Mary');
    $this->tester->deleteWordPressUser('mary@jane.com');
  }

  public function testItDoesUpdateStatusExistingSubscriberWhenExistingSubscribersStatusIsSet() {
    $data = $this->testData;
    $data['existingSubscribersStatus'] = Subscriber::STATUS_SUBSCRIBED;
    $existingSubscriber = Subscriber::create();
    $existingSubscriber->hydrate(
      [
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'Adam@Smith.com',
        'status' => Subscriber::STATUS_UNSUBSCRIBED,
        'last_subscribed_at' => '2020-08-08 08:08:00',
      ]);
    $import = new Import($this->wpSegment, $data);
    $import->process();
    $updatedSubscriber = Subscriber::where('email', $existingSubscriber->email)->findOne();
    expect($updatedSubscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItDoesStatusNewSubscriberWhenNewSubscribersStatusIsSet() {
    $data = $this->testData;
    $data['newSubscribersStatus'] = Subscriber::STATUS_UNSUBSCRIBED;
    $import = new Import($this->wpSegment, $data);
    $import->process();
    $newSubscriber = Subscriber::where('email', 'Adam@Smith.com')->findOne();
    expect($newSubscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
    $newSubscriber = Subscriber::where('email', 'mary@jane.com')->findOne();
    expect($newSubscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function testItRunsImport() {
    $result = $this->import->process();
    expect($result['created'])->equals(2);
    Subscriber::where('email', 'mary@jane.com')
      ->findOne()
      ->delete();
    $timestamp = time() + 1;
    $this->import->createdAt = $this->import->requiredSubscribersFields['created_at'] = date('Y-m-d H:i:s', $timestamp);
    $this->import->updatedAt = date('Y-m-d H:i:s', $timestamp + 1);
    $result = $this->import->process();
    expect($result['created'])->equals(1);
    $dbSubscribers = array_column(
      Subscriber::select('id')->findArray(),
      'id'
    );
    // subscribers must be added to segments
    foreach ($dbSubscribers as $dbSubscriber) {
      $subscriberSegment = SubscriberSegment::where('subscriber_id', $dbSubscriber)
        ->where('segment_id', $this->testData['segments'][0])
        ->findOne();
      expect($subscriberSegment)->notEmpty();
    }
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
  }
}
