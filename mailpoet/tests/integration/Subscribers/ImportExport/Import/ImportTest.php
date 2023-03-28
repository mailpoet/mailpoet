<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\ImportExport\Import;

use Codeception\Stub;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\WP;
use MailPoet\Subscribers\ImportExport\ImportExportRepository;
use MailPoet\Subscribers\SubscriberCustomFieldRepository;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscribers\SubscriberTagRepository;
use MailPoet\Tags\TagRepository;
use MailPoet\Test\DataFactories\Tag;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class ImportTest extends \MailPoetTest {
  /** @var array */
  public $subscribersCustomFields;
  /** @var array */
  public $subscribersData;
  /** @var Import */
  public $import;
  /** @var array */
  public $subscribersFields;
  /** @var array */
  public $testData;
  /** @var SegmentEntity */
  public $segment1;
  /** @var SegmentEntity */
  public $segment2;
  /** @var TagEntity */
  private $tag1;

  /** @var WP */
  private $wpSegment;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var ImportExportRepository */
  private $importExportRepository;

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscriberCustomFieldRepository */
  private $subscriberCustomFieldRepository;

  /** @var SubscribersRepository */
  private $subscriberRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var TagRepository */
  private $tagRepository;

  /** @var SubscriberTagRepository */
  private $subscribersTagRepository;

  public function _before(): void {
    $this->wpSegment = $this->diContainer->get(WP::class);
    $this->customFieldsRepository = $this->diContainer->get(CustomFieldsRepository::class);
    $this->importExportRepository = $this->diContainer->get(ImportExportRepository::class);
    $this->newsletterOptionsRepository = $this->diContainer->get(NewsletterOptionsRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscriberCustomFieldRepository = $this->diContainer->get(SubscriberCustomFieldRepository::class);
    $this->subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);
    $this->tagRepository = $this->diContainer->get(TagRepository::class);
    $this->subscribersTagRepository = $this->diContainer->get(SubscriberTagRepository::class);
    $customField = $this->customFieldsRepository->createOrUpdate([
      'name' => 'country',
      'type' => CustomFieldEntity::TYPE_TEXT,
    ]);
    $this->assertInstanceOf(CustomFieldEntity::class, $customField);
    $this->subscribersCustomFields = [$customField->getId()];
    $this->segment1 = $this->segmentsRepository->createOrUpdate('Segment 1');
    $this->segment2 = $this->segmentsRepository->createOrUpdate('Segment 2');
    $this->tag1 = (new Tag())->withName('Tag 1')->create();
    $this->testData = [
      'subscribers' => [
        [
          'Adam',
          'Smith',
          'Adam@smith.com', // capitalized to test normalization
          'France',
          '2014-05-31 18:42:35',
          '192.168.1.2',
          '2014-01-06 11:42:35',
          '192.168.2.2',
        ],
        [
          'Mary',
          'Jane',
          'mary@jane.com',
          'Brazil',
          '2014-05-31 18:42:35',
          '2001:0db8:0000:0000:0000:ff00:0042:8329',
          '2014-01-06 11:42:35',
          '2001:0db8:0000:0000:ff00:ff00:0042:8329',
        ],
      ],
      'columns' => [
        'first_name' => ['index' => 0],
        'last_name' => ['index' => 1],
        'email' => ['index' => 2],
        $customField->getId() => ['index' => 3],
        'created_at' => ['index' => 4],
        'subscribed_ip' => ['index' => 5],
        'confirmed_at' => ['index' => 6],
        'confirmed_ip' => ['index' => 7],
      ],
      'segments' => [
        $this->segment1->getId(),
      ],
      'tags' => [],
      'timestamp' => time(),
      'newSubscribersStatus' => SubscriberEntity::STATUS_SUBSCRIBED,
      'existingSubscribersStatus' => Import::STATUS_DONT_UPDATE,
      'updateSubscribers' => true,
    ];
    $this->subscribersFields = [
      'first_name',
      'last_name',
      'email',
      'created_at',
      'subscribed_ip',
      'confirmed_at',
      'confirmed_ip',
    ];
    $this->import = $this->createImportInstance($this->testData);
    $this->subscribersData = $this->import->transformSubscribersData(
      $this->testData['subscribers'],
      $this->testData['columns']
    );
  }

  public function testItConstructs(): void {
    expect(is_array($this->import->subscribersData))->true();
    expect($this->import->segmentsIds)->equals($this->testData['segments']);
    expect(is_array($this->import->subscribersFields))->true();
    expect(is_array($this->import->subscribersCustomFields))->true();
    expect($this->import->subscribersCount)->equals(2);
    expect($this->import->createdAt)->notEmpty();
    expect($this->import->updatedAt)->notEmpty();
  }

  public function testItChecksForRequiredDataFields(): void {
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

  public function testItValidatesColumnNames(): void {
    $data = $this->testData;
    $data['columns']['test) values ((ExtractValue(1,CONCAT(0x5c, (SELECT version())))))%23'] = true;
    try {
      $this->import->validateImportData($data);
      self::fail('Missing or invalid data exception not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('Missing or invalid import data.');
    }
  }

  public function testItValidatesSubscribersEmail(): void {
    // invalid email is removed from data object
    $data['email'] = [
      'àdam@smîth.com',
      'jane@doe.com',
    ];
    $result = $this->import->validateSubscribersData($data);
    $this->assertIsArray($result);
    expect($result['email'])->count(1);
    expect($result['email'][0])->equals('jane@doe.com');

    // valid email passes validation
    $data['email'] = [
      'adam@smith.com',
      'jane@doe.com',
    ];
    $result = $this->import->validateSubscribersData($data);
    expect($result)->equals($data);
  }

  public function testItValidatesSubscribersConfirmedAt(): void {
    // required email column
    $data['email'] = [
      'adam@smith.com',
      'jane@doe.com',
    ];
    // invalid confirmed_at is removed from data object
    $data['confirmed_at'] = [
      '2014-35-31 18:42:35',
      '2019-05-31 18:42:35',
    ];
    $result = $this->import->validateSubscribersData($data);
    $this->assertIsArray($result);
    expect($result['confirmed_at'])->count(1);
    expect($result['confirmed_at'][0])->equals('2019-05-31 18:42:35');

    // normalize confirmed_at
    $data['confirmed_at'] = [
      '2020-09-17T11:11:11Z',
      '2015-10-13T16:19:20-04:00',
    ];
    $result = $this->import->validateSubscribersData($data);
    $this->assertIsArray($result);
    expect($result['confirmed_at'])->equals([
      '2020-09-17 11:11:11',
      '2015-10-13 16:19:20',
    ]);
  }

  public function testItValidatesSubscribersConfirmedIP(): void {
    // required email column
    $data['email'] = [
      'adam@smith.com',
      'jane@doe.com',
    ];
    // invalid confirmed_ip is empty in data object
    $data['confirmed_ip'] = [
      '2019-05-31 18:42:35',
      '192.68.69.32',
    ];
    $result = $this->import->validateSubscribersData($data);
    $this->assertIsArray($result);
    expect($result['confirmed_ip'])->count(2);
    expect($result['confirmed_ip'][0])->isEmpty();
    expect($result['confirmed_ip'][1])->equals('192.68.69.32');

    // invalid IPv4 confirmed_ip is empty in the data object
    $data['confirmed_ip'] = [
      '392.68.69.32',
      '192.68.69.32',
    ];
    $result = $this->import->validateSubscribersData($data);
    $this->assertIsArray($result);
    expect($result['confirmed_ip'])->count(2);
    expect($result['confirmed_ip'][0])->isEmpty();
    expect($result['confirmed_ip'][1])->equals('192.68.69.32');

    // Empty confirmed_ip is empty in the data object
    $data['confirmed_ip'] = [
      '',
      '192.68.69.32',
    ];
    $result = $this->import->validateSubscribersData($data);
    $this->assertIsArray($result);
    expect($result['confirmed_ip'])->count(2);
    expect($result['confirmed_ip'][0])->isEmpty();
    expect($result['confirmed_ip'][1])->equals('192.68.69.32');

    // normalize confirmed_at
    $data['confirmed_ip'] = [
      '192.68.69.32', // IPv4
      '2001:0db8:85a3:08d3:1319:8a2e:0370:7334', //IPv6
    ];
    $result = $this->import->validateSubscribersData($data);
    $this->assertIsArray($result);
    expect($result['confirmed_ip'])->equals($data['confirmed_ip']);
  }

  public function testItThrowsErrorWhenNoValidSubscribersAreFoundDuringImport(): void {
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
      'tags' => [],
      'timestamp' => time(),
      'newSubscribersStatus' => SubscriberEntity::STATUS_SUBSCRIBED,
      'existingSubscribersStatus' => Import::STATUS_DONT_UPDATE,
      'updateSubscribers' => true,
    ];
    $import = $this->createImportInstance($data);
    try {
      $import->process();
      self::fail('No valid subscribers found exception not thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('No valid subscribers were found.');
    }
  }

  public function testItTransformsSubscribers(): void {
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

  public function testItSplitsSubscribers(): void {
    $subscribersData = $this->subscribersData;
    $subscribersDataExisting = [
      [
        'first_name' => 'Johnny',
        'last_name' => 'Walker',
        'email' => 'johnny@WaLker.com',
        'wp_user_id' => 13579,
        'created_at' => '2020-01-01 16:32:48',
        'subscribed_ip' => '127.0.0.1',
        'confirmed_at' => '2020-12-31 16:32:48',
        'confirmed_ip' => '127.1.1.1',
      ], [
        'first_name' => 'Steve',
        'last_name' => 'Sorrow',
        'email' => 'sTeve.sorrow@exaMple.com',
        'created_at' => '2020-01-01 16:32:48',
        'subscribed_ip' => '127.0.0.1',
        'confirmed_at' => '2020-12-31 16:32:48',
        'confirmed_ip' => '127.1.1.1',
      ],
    ];
    foreach ($subscribersDataExisting as $i => $existingSubscriber) {
      $this->createSubscriber(
        $existingSubscriber['first_name'],
        $existingSubscriber['last_name'],
        $existingSubscriber['email'],
        null,
        null,
        $existingSubscriber['wp_user_id'] ?? null,
        Carbon::createFromFormat('Y-m-d H:i:s', $existingSubscriber['created_at']) ?: null,
        $existingSubscriber['subscribed_ip'],
        Carbon::createFromFormat('Y-m-d H:i:s', $existingSubscriber['confirmed_at']) ?: null,
        $existingSubscriber['confirmed_ip']
      );
      $subscribersData['first_name'][] = $existingSubscriber['first_name'];
      $subscribersData['last_name'][] = $existingSubscriber['last_name'];
      $subscribersData['email'][] = strtolower($existingSubscriber['email']); // import emails are always lowercase
      $subscribersData[1][] = 'custom_field_' . $i;
      $subscribersData['created_at'][] = $existingSubscriber['created_at'];
      $subscribersData['subscribed_ip'][] = $existingSubscriber['subscribed_ip'];
      $subscribersData['confirmed_at'][] = $existingSubscriber['confirmed_at'];
      $subscribersData['confirmed_ip'][] = $existingSubscriber['confirmed_ip'];
    }
    list($existingSubscribers, $newSubscribers, $wpUsers, ) = $this->import->splitSubscribersData(
      $subscribersData
    );
    $this->assertIsArray($existingSubscribers);
    expect($existingSubscribers['email'][0])->equals($subscribersData['email'][2]);
    expect($existingSubscribers['email'][1])->equals($subscribersData['email'][3]);
    foreach ($newSubscribers as $field => $value) {
      expect($value[0])->equals($subscribersData[$field][0]);
    }
    expect($wpUsers)->equals([$subscribersDataExisting[0]['wp_user_id']]);
  }

  public function testItAddsMissingRequiredFieldsToSubscribersObject(): void {
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
      'tags' => [],
      'timestamp' => time(),
      'newSubscribersStatus' => SubscriberEntity::STATUS_SUBSCRIBED,
      'existingSubscribersStatus' => Import::STATUS_DONT_UPDATE,
      'updateSubscribers' => true,
    ];
    $import = $this->createImportInstance($data);
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

  public function testItGetsSubscriberFields(): void {
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

  public function testItGetsCustomSubscribersFields(): void {
    $data = [
      'one',
      'two',
      39,
    ];
    $fields = $this->import->getCustomSubscribersFields($data);
    expect($fields)->equals([39]);
  }

  public function testItAddsOrUpdatesSubscribers(): void {
    $subscribersData = [
      'data' => $this->subscribersData,
      'fields' => $this->subscribersFields,
    ];
    $this->import->createOrUpdateSubscribers(
      Import::ACTION_CREATE,
      $subscribersData
    );
    $subscribers = $this->subscriberRepository->findAll();
    expect(count($subscribers))->equals(2);
    expect($subscribers[0]->getEmail())
      ->equals($subscribersData['data']['email'][0]);
    $subscribersData['data']['first_name'][1] = 'MaryJane';
    $this->import->createOrUpdateSubscribers(
      Import::ACTION_UPDATE,
      $subscribersData
    );
    $this->entityManager->clear();
    $subscribers = $this->subscriberRepository->findAll();
    expect($subscribers[1]->getFirstName())
      ->equals($subscribersData['data']['first_name'][1]);
  }

  public function testItDeletesTrashedSubscribers(): void {
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
      Import::ACTION_CREATE,
      $subscribersData
    );
    $dbSubscribers = array_map(function (SubscriberEntity $subscriber): int {
      return (int)$subscriber->getId();
    }, $this->subscriberRepository->findAll());
    expect(count($dbSubscribers))->equals(2);
    $this->import->addSubscribersToSegments(
      $dbSubscribers,
      [$this->segment1->getId(), $this->segment2->getId()]
    );
    $subscribersSegments = $this->subscriberSegmentRepository->findAll();
    expect(count($subscribersSegments))->equals(4);
    $this->import->deleteExistingTrashedSubscribers(
      $subscribersData['data']
    );
    $subscribersSegments = $this->subscriberSegmentRepository->findAll();
    $dbSubscribers = $this->subscriberRepository->findAll();
    expect(count($subscribersSegments))->equals(2);
    expect(count($dbSubscribers))->equals(1);
  }

  public function testItCreatesOrUpdatesCustomFields(): void {
    $subscribersData = [
      'data' => $this->subscribersData,
      'fields' => $this->subscribersFields,
    ];
    $customField = $this->subscribersCustomFields[0];
    $this->import->createOrUpdateSubscribers(
      Import::ACTION_CREATE,
      $subscribersData,
      $this->subscribersFields
    );
    $dbSubscribers = array_map(function (SubscriberEntity $subscriber): array {
      return [
        'id' => $subscriber->getId(),
        'email' => $subscriber->getEmail(),
      ];
    }, $this->subscriberRepository->findAll());
    $this->import->createOrUpdateCustomFields(
      Import::ACTION_CREATE,
      $dbSubscribers,
      $subscribersData,
      $this->subscribersCustomFields
    );
    $subscribersCustomFields = $this->subscriberCustomFieldRepository->findAll();
    expect(count($subscribersCustomFields))->equals(2);
    expect($subscribersCustomFields[0]->getValue())
      ->equals($subscribersData['data'][$customField][0]);
    $subscribersData[$customField][1] = 'Rio';
    $this->import->createOrUpdateCustomFields(
      Import::ACTION_UPDATE,
      $dbSubscribers,
      $subscribersData,
      $this->subscribersCustomFields
    );
    $subscribersCustomFields = $this->subscriberCustomFieldRepository->findAll();
    expect($subscribersCustomFields[1]->getValue())
      ->equals($subscribersData['data'][$customField][1]);
  }

  public function testItAddsSubscribersToSegments(): void {
    $subscribersData = [
      'data' => $this->subscribersData,
      'fields' => $this->subscribersFields,
    ];
    $this->import->createOrUpdateSubscribers(
      Import::ACTION_CREATE,
      $subscribersData,
      $this->subscribersFields
    );
    $dbSubscribers = array_map(function (SubscriberEntity $subscriber): int {
      return (int)$subscriber->getId();
    }, $this->subscriberRepository->findAll());
    $this->import->addSubscribersToSegments(
      $dbSubscribers,
      [$this->segment1->getId(), $this->segment2->getId()]
    );
    // 2 subscribers * 2 segments
    foreach ($dbSubscribers as $dbSubscriber) {
      $subscriberSegment1 = $this->subscriberSegmentRepository->findOneBy([
        'subscriber' => $dbSubscriber,
        'segment' => $this->segment1->getId(),
      ]);
      expect($subscriberSegment1)->isInstanceOf(SubscriberSegmentEntity::class);
      $subscriberSegment2 = $this->subscriberSegmentRepository->findOneBy([
        'subscriber' => $dbSubscriber,
        'segment' => $this->segment2->getId(),
      ]);
      expect($subscriberSegment2)->isInstanceOf(SubscriberSegmentEntity::class);
    }
  }

  public function testItAddsTagsToSubscribers(): void {
    $subscribersData = [
      'data' => $this->subscribersData,
      'fields' => $this->subscribersFields,
    ];
    $this->import->createOrUpdateSubscribers(
      Import::ACTION_CREATE,
      $subscribersData,
      $this->subscribersFields
    );
    $dbSubscribers = array_map(function (SubscriberEntity $subscriber): int {
      return (int)$subscriber->getId();
    }, $this->subscriberRepository->findAll());
    // tagging
    $this->import->addTagsToSubscribers(
      $dbSubscribers,
      [$this->tag1->getName(), 'Tag 2'] // one tag is existing and second should be created
    );
    $tag2 = $this->tagRepository->findOneBy(['name' => 'Tag 2']);
    $this->assertInstanceOf(TagEntity::class, $tag2);
    // check added tags
    foreach ($dbSubscribers as $dbSubscriber) {
      $subscriberTag = $this->subscribersTagRepository->findOneBy([
        'subscriber' => $dbSubscriber,
        'tag' => $this->tag1,
      ]);
      expect($subscriberTag)->isInstanceOf(SubscriberTagEntity::class);
      $subscriberTag = $this->subscribersTagRepository->findOneBy([
        'subscriber' => $dbSubscriber,
        'tag' => $tag2,
      ]);
      expect($subscriberTag)->isInstanceOf(SubscriberTagEntity::class);
    }
  }

  public function testItDeletesExistingTrashedSubscribers(): void {
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
      Import::ACTION_CREATE,
      $subscribersData
    );
  }

  public function testItUpdatesSubscribers(): void {
    $result = $this->import->process();
    expect($result['updated'])->equals(0);
    $result = $this->import->process();
    expect($result['updated'])->equals(2);
    $this->import->updateSubscribers = false;
    $result = $this->import->process();
    expect($result['updated'])->equals(0);
  }

  public function testItDoesNotUpdateExistingSubscribersStatusWhenStatusColumnIsNotPresent(): void {
    $subscriber = $this->createSubscriber(
      'Adam',
      'Smith',
      'Adam@Smith.com',
      SubscriberEntity::STATUS_UNSUBSCRIBED
    );
    $this->import->process();

    $updatedSubscriber = $this->subscriberRepository->findOneBy(['email' => $subscriber->getEmail()]);
    $this->assertInstanceOf(SubscriberEntity::class, $updatedSubscriber);
    expect($updatedSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function testItImportsNewsSubscribersWithAllAdditionalParameters(): void {
    $data = $this->testData;
    $data['columns']['status'] = ['index' => 4];
    $data['subscribers'][0][] = 'unsubscribed';
    $data['subscribers'][1][] = 'unsubscribed';
    $import = $this->createImportInstance($data);
    $result = $import->process();
    /** @var SubscriberEntity[] $newSubscribers */
    $newSubscribers = $this->subscriberRepository->findBy(['email' => [
      $data['subscribers'][0][2],
      $data['subscribers'][1][2],
    ]]);
    expect($newSubscribers)->count(2);
    expect($newSubscribers[0]->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($newSubscribers[1]->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($newSubscribers[0]->getSource())->equals('imported');
    expect($newSubscribers[1]->getSource())->equals('imported');
    expect(strlen((string)$newSubscribers[0]->getLinkToken()))->equals(SubscriberEntity::LINK_TOKEN_LENGTH);
    expect(strlen((string)$newSubscribers[1]->getLinkToken()))->equals(SubscriberEntity::LINK_TOKEN_LENGTH);
    $lastSubscribed1 = $newSubscribers[0]->getLastSubscribedAt();
    $lastSubscribed2 = $newSubscribers[1]->getLastSubscribedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $lastSubscribed1);
    $this->assertInstanceOf(\DateTimeInterface::class, $lastSubscribed2);
    expect($lastSubscribed1->getTimestamp())->equals($this->testData['timestamp'], 1);
    expect($lastSubscribed2->getTimestamp())->equals($this->testData['timestamp'], 1);
  }

  public function testItDoesNotUpdateExistingSubscribersLastSubscribedAtWhenItIsPresent(): void {
    $data = $this->testData;
    $data['columns']['last_subscribed_at'] = ['index' => 4];
    $data['subscribers'][0][] = '2018-12-12 12:12:00';
    $data['subscribers'][1][] = '2018-12-12 12:12:00';
    $lastSubscribedAt = Carbon::createFromFormat('Y-m-d H:i:s', '2017-12-12 12:12:00');
    $this->assertInstanceOf(Carbon::class, $lastSubscribedAt);
    $import = $this->createImportInstance($data);
    $existingSubscriber = $this->createSubscriber(
      'Adam',
      'Smith',
      'Adam@Smith.com',
      null,
      $lastSubscribedAt
    );
    $import->process();

    $updatedSubscriber = $this->subscriberRepository->findOneBy(['email' => $existingSubscriber->getEmail()]);
    $this->assertInstanceOf(SubscriberEntity::class, $updatedSubscriber);
    expect($updatedSubscriber->getLastSubscribedAt())->equals(Carbon::createFromFormat('Y-m-d H:i:s', '2017-12-12 12:12:00'));
  }

  public function testItSynchronizesWpUsers(): void {
    $this->tester->createWordPressUser('mary@jane.com', 'editor');
    $beforeImport = $this->subscriberRepository->findOneBy(['email' => 'mary@jane.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $beforeImport);
    $data = $this->testData;
    $import = $this->createImportInstance($data);
    $import->process();

    $this->entityManager->clear();
    $imported = $this->subscriberRepository->findOneBy(['email' => 'mary@jane.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $imported);
    expect($imported->getFirstName())->equals($beforeImport->getFirstName()); // Subscriber name was synchronized from WP
    expect($imported->getFirstName())->notEquals('Mary');
    $this->tester->deleteWordPressUser('mary@jane.com');
  }

  public function testItDoesUpdateStatusExistingSubscriberWhenExistingSubscribersStatusIsSet(): void {
    $data = $this->testData;
    $data['existingSubscribersStatus'] = SubscriberEntity::STATUS_SUBSCRIBED;
    $lastSubscribedAt = Carbon::createFromFormat('Y-m-d H:i:s', '2020-08-08 08:08:00');
    $this->assertInstanceOf(Carbon::class, $lastSubscribedAt);
    $existingSubscriber = $this->createSubscriber(
      'Adam',
      'Smith',
      'Adam@Smith.com',
      SubscriberEntity::STATUS_UNSUBSCRIBED,
      $lastSubscribedAt
    );
    $import = $this->createImportInstance($data);
    $import->process();

    $this->entityManager->clear();
    $updatedSubscriber = $this->subscriberRepository->findOneBy(['email' => $existingSubscriber->getEmail()]);
    $this->assertInstanceOf(SubscriberEntity::class, $updatedSubscriber);
    expect($updatedSubscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItDoesStatusNewSubscriberWhenNewSubscribersStatusIsSet(): void {
    $data = $this->testData;
    $data['newSubscribersStatus'] = SubscriberEntity::STATUS_UNSUBSCRIBED;
    $import = $this->createImportInstance($data);
    $import->process();
    $newSubscriber = $this->subscriberRepository->findOneBy(['email' => 'Adam@Smith.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $newSubscriber);
    expect($newSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $newSubscriber = $this->subscriberRepository->findOneBy(['email' => 'mary@jane.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $newSubscriber);
    expect($newSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function testItRunsImport(): void {
    $result = $this->import->process();
    expect($result['created'])->equals(2);
    $subscriber = $this->subscriberRepository->findOneBy(['email' => 'mary@jane.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->subscriberRepository->remove($subscriber);
    $this->subscriberRepository->flush();
    $this->import->createdAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $this->import->updatedAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp') + 1);
    $this->import->requiredSubscribersFields['created_at'] = $this->import->createdAt;
    $result = $this->import->process();
    expect($result['created'])->equals(1);
    $dbSubscribers = array_map(function (SubscriberEntity $subscriber): int {
      return (int)$subscriber->getId();
    }, $this->subscriberRepository->findAll());
    // subscribers must be added to segments
    foreach ($dbSubscribers as $dbSubscriber) {
      $subscriberSegment = $this->subscriberSegmentRepository->findOneBy([
        'subscriber' => $dbSubscriber,
        'segment' => $this->testData['segments'][0],
      ]);
      expect($subscriberSegment)->isInstanceOf(SubscriberSegmentEntity::class);
    }
  }

  public function testItImportsSubscribersWithCustomFormat(): void {
    WPFunctions::set(Stub::make(new WPFunctions, [
      'getOption' => 'd/m/Y',
    ]));

    $data = $this->testData;

    $data['subscribers'][0][4] = '31/08/2021 10:33'; // valid only with d/m/Y
    $data['subscribers'][0][6] = '03/08/2021';
    $data['subscribers'][0][2] = 'europeandateformat@yopmail.com';

    $data['subscribers'][1][4] = '12/08/2021 11:33';
    $data['subscribers'][1][6] = '04/08/2021';
    $data['subscribers'][1][2] = 'europeandateformat2@yopmail.com';
    $import = $this->createImportInstance($data);
    $import->process();
    /** @var SubscriberEntity[] $newSubscribers */
    $newSubscribers = $this->subscriberRepository->findBy(['email' => [
      $data['subscribers'][0][2],
      $data['subscribers'][1][2],
    ]]);
    expect($newSubscribers)->count(2);
    WPFunctions::set(new WPFunctions());
  }

  public function testItOnlyAppliesCustomFormatToSitesWithCustomFormat(): void {
    WPFunctions::set(Stub::make(new WPFunctions, [
      'getOption' => 'm/d/Y',
    ]));

    $data = $this->testData;

    $data['subscribers'][0][4] = '31/08/2021 10:33'; // valid only with d/m/Y
    $data['subscribers'][0][6] = '03/08/2021';
    $data['subscribers'][0][2] = 'wrongdateformat@yopmail.com';

    $data['subscribers'][1][4] = '12/08/2021 11:33';
    $data['subscribers'][1][6] = '04/08/2021';
    $data['subscribers'][1][2] = 'correctdateformat2@yopmail.com';
    $import = $this->createImportInstance($data);
    $import->process();
    /** @var SubscriberEntity[] $newSubscribers */
    $newSubscribers = $this->subscriberRepository->findBy(['email' => [
      $data['subscribers'][0][2],
      $data['subscribers'][1][2],
    ]]);
    expect($newSubscribers)->count(1);
    expect($newSubscribers[0]->getEmail())->equals('correctdateformat2@yopmail.com');
    WPFunctions::set(new WPFunctions());
  }

  private function createImportInstance(array $data): Import {
    return new Import(
      $this->wpSegment,
      $this->customFieldsRepository,
      $this->importExportRepository,
      $this->newsletterOptionsRepository,
      $this->subscriberRepository,
      $this->tagRepository,
      $data
    );
  }

  private function createSubscriber(
    string $firstName,
    string $lastName,
    string $email,
    ?string $status = null,
    ?Carbon $lastSubscribedAt = null,
    ?int $wpUserid = null,
    ?Carbon $createdAt = null,
    ?string $subscribedIp = null,
    ?Carbon $confirmedAt = null,
    ?string $confirmedIp = null
  ): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setFirstName($firstName);
    $subscriber->setLastName($lastName);
    $subscriber->setEmail($email);
    if ($status) {
      $subscriber->setStatus($status);
    }
    if ($lastSubscribedAt) {
      $subscriber->setLastSubscribedAt($lastSubscribedAt);
    }
    if ($createdAt) {
      $subscriber->setCreatedAt($createdAt);
    }
    if ($subscribedIp) {
      $subscriber->setSubscribedIp($subscribedIp);
    }
    if ($confirmedAt) {
      $subscriber->setConfirmedAt($confirmedAt);
    }
    if ($confirmedIp) {
      $subscriber->setConfirmedIp($confirmedIp);
    }
    $subscriber->setWpUserId($wpUserid);
    $this->subscriberRepository->persist($subscriber);
    $this->subscriberRepository->flush();
    return $subscriber;
  }

  public function _after(): void {
    $this->truncateEntity(CustomFieldEntity::class);
  }
}
