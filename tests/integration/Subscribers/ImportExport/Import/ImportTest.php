<?php

namespace MailPoet\Subscribers\ImportExport\Import;

use DateTime;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\WP;
use MailPoet\Subscribers\ImportExport\ImportExportRepository;
use MailPoet\Subscribers\SubscriberCustomFieldRepository;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class ImportTest extends \MailPoetTest {
  public $subscribersCustomFields;
  public $subscribersData;
  /** @var Import */
  public $import;
  public $subscribersFields;
  public $testData;
  /** @var SegmentEntity */
  public $segment1;
  /** @var SegmentEntity */
  public $segment2;

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

  public function _before() {
    $this->wpSegment = $this->diContainer->get(WP::class);
    $this->customFieldsRepository = $this->diContainer->get(CustomFieldsRepository::class);
    $this->importExportRepository = $this->diContainer->get(ImportExportRepository::class);
    $this->newsletterOptionsRepository = $this->diContainer->get(NewsletterOptionsRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscriberCustomFieldRepository = $this->diContainer->get(SubscriberCustomFieldRepository::class);
    $this->subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);
    $customField = $this->customFieldsRepository->createOrUpdate([
      'name' => 'country', 
      'type' => CustomFieldEntity::TYPE_TEXT, 
    ]);
    assert($customField instanceof CustomFieldEntity);
    $this->subscribersCustomFields = [$customField->getId()];
    $this->segment1 = $this->segmentsRepository->createOrUpdate('Segment 1');
    $this->segment2 = $this->segmentsRepository->createOrUpdate('Segment 2');
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
        $customField->getId() => ['index' => 3],
      ],
      'segments' => [
        $this->segment1->getId(),
      ],
      'timestamp' => time(),
      'newSubscribersStatus' => SubscriberEntity::STATUS_SUBSCRIBED,
      'existingSubscribersStatus' => Import::STATUS_DONT_UPDATE,
      'updateSubscribers' => true,
    ];
    $this->subscribersFields = [
      'first_name',
      'last_name',
      'email',
    ];
    $this->import = $this->createImportInstance($this->testData);
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
    // invalid email is removed from data object
    $data['email'] = [
      'àdam@smîth.com',
      'jane@doe.com',
    ];
    $result = $this->import->validateSubscribersData($data);
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
      $this->createSubscriber(
        $existingSubscriber['first_name'],
        $existingSubscriber['last_name'],
        $existingSubscriber['email'],
        null,
        null,
        $existingSubscriber['wp_user_id'] ?? null
      );
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

  public function testItCreatesOrUpdatesCustomFields() {
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

  public function testItAddsSubscribersToSegments() {
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
      Import::ACTION_CREATE,
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
    $subscriber = $this->createSubscriber(
      'Adam',
      'Smith',
      'Adam@Smith.com',
      SubscriberEntity::STATUS_UNSUBSCRIBED
    );
    $this->import->process();

    $updatedSubscriber = $this->subscriberRepository->findOneBy(['email' => $subscriber->getEmail()]);
    assert($updatedSubscriber instanceof SubscriberEntity);
    expect($updatedSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function testItImportsNewsSubscribersWithAllAdditionalParameters() {
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
    $testTime = Carbon::createFromTimestamp($this->testData['timestamp']);
    expect($newSubscribers[0]->getLastSubscribedAt())->equals($testTime);
    expect($newSubscribers[1]->getLastSubscribedAt())->equals($testTime);
  }

  public function testItDoesNotUpdateExistingSubscribersLastSubscribedAtWhenItIsPresent() {
    $data = $this->testData;
    $data['columns']['last_subscribed_at'] = ['index' => 4];
    $data['subscribers'][0][] = '2018-12-12 12:12:00';
    $data['subscribers'][1][] = '2018-12-12 12:12:00';
    $lastSubscribedAt = Carbon::createFromFormat('Y-m-d H:i:s', '2017-12-12 12:12:00');
    assert($lastSubscribedAt instanceof Carbon);
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
    assert($updatedSubscriber instanceof SubscriberEntity);
    expect($updatedSubscriber->getLastSubscribedAt())->equals(Carbon::createFromFormat('Y-m-d H:i:s', '2017-12-12 12:12:00'));
  }

  public function testItSynchronizesWpUsers() {
    $this->tester->createWordPressUser('mary@jane.com', 'editor');
    $beforeImport = $this->subscriberRepository->findOneBy(['email' => 'mary@jane.com']);
    assert($beforeImport instanceof SubscriberEntity);
    $data = $this->testData;
    $import = $this->createImportInstance($data);
    $import->process();

    $this->entityManager->clear();
    $imported = $this->subscriberRepository->findOneBy(['email' => 'mary@jane.com']);
    assert($imported instanceof SubscriberEntity);
    expect($imported->getFirstName())->equals($beforeImport->getFirstName()); // Subscriber name was synchronized from WP
    expect($imported->getFirstName())->notEquals('Mary');
    $this->tester->deleteWordPressUser('mary@jane.com');
  }

  public function testItDoesUpdateStatusExistingSubscriberWhenExistingSubscribersStatusIsSet() {
    $data = $this->testData;
    $data['existingSubscribersStatus'] = SubscriberEntity::STATUS_SUBSCRIBED;
    $lastSubscribedAt = Carbon::createFromFormat('Y-m-d H:i:s', '2020-08-08 08:08:00');
    assert($lastSubscribedAt instanceof Carbon);
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
    assert($updatedSubscriber instanceof SubscriberEntity);
    expect($updatedSubscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItDoesStatusNewSubscriberWhenNewSubscribersStatusIsSet() {
    $data = $this->testData;
    $data['newSubscribersStatus'] = SubscriberEntity::STATUS_UNSUBSCRIBED;
    $import = $this->createImportInstance($data);
    $import->process();
    $newSubscriber = $this->subscriberRepository->findOneBy(['email' => 'Adam@Smith.com']);
    assert($newSubscriber instanceof SubscriberEntity);
    expect($newSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $newSubscriber = $this->subscriberRepository->findOneBy(['email' => 'mary@jane.com']);
    assert($newSubscriber instanceof SubscriberEntity);
    expect($newSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function testItRunsImport() {
    $result = $this->import->process();
    expect($result['created'])->equals(2);
    $subscriber = $this->subscriberRepository->findOneBy(['email' => 'mary@jane.com']);
    assert($subscriber instanceof SubscriberEntity);
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

  private function createImportInstance(array $data): Import {
    return new Import(
      $this->wpSegment,
      $this->customFieldsRepository,
      $this->importExportRepository,
      $this->newsletterOptionsRepository,
      $this->subscriberRepository,
      $data
    );
  }

  private function createSubscriber(
    string $firstName,
    string $lastName,
    string $email,
    ?string $status = null,
    ?DateTime $lastSubscribedAt = null,
    ?int $wpUserid = null
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
    $subscriber->setWpUserId($wpUserid);
    $this->subscriberRepository->persist($subscriber);
    $this->subscriberRepository->flush();
    return $subscriber;
  }

  public function _after() {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(CustomFieldEntity::class);
    $this->truncateEntity(SubscriberCustomFieldEntity::class);
  }
}
