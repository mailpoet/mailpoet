<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\ImportExport\Export;

use MailPoet\Config\Env;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\ImportExport\ImportExportRepository;
use MailPoet\Subscribers\SubscriberCustomFieldRepository;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;

class ExportTest extends \MailPoetTest {
  /** @var array */
  public $segmentsData;

  /** @var array */
  public $customFieldsData;

  /** @var array */
  public $subscribersData;

  /** @var array */
  public $subscriberFields;

  /** @var array */
  public $jSONData;

  /** @var Export */
  public $export;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var ImportExportRepository */
  private $importExportRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberCustomFieldRepository */
  private $subscriberCustomFieldRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  public function _before() {
    parent::_before();
    $this->customFieldsRepository = $this->diContainer->get(CustomFieldsRepository::class);
    $this->importExportRepository = $this->diContainer->get(ImportExportRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->subscriberSegmentRepository = $this->diContainer->get(SubscriberSegmentRepository::class);
    $this->subscriberCustomFieldRepository = $this->diContainer->get(SubscriberCustomFieldRepository::class);

    $this->jSONData = json_decode((string)file_get_contents(dirname(__FILE__) . '/ExportTestData.json'), true);
    $this->subscriberFields = [
      'first_name' => 'First name',
      'last_name' => 'Last name',
      'email' => 'Email',
      1 => 'Country',
    ];
    $this->subscribersData = [
      [
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com',
      ],
      [
        'first_name' => 'Mary',
        'last_name' => 'Jane',
        'email' => 'mary@jane.com',
        'status' => SubscriberEntity::STATUS_SUBSCRIBED,
        1 => 'Brazil',
      ],
      [
        'first_name' => 'John',
        'last_name' => 'Kookoo',
        'email' => 'john@kookoo.com',
      ],
      [
        'first_name' => 'Paul',
        'last_name' => 'Newman',
        'email' => 'paul@newman.com',
      ],
    ];
    $this->customFieldsData = [
      [
        'name' => 'Country',
        'type' => CustomFieldEntity::TYPE_TEXT,
      ],
    ];
    $this->segmentsData = [
      ['name' => 'Newspapers'],
      ['name' => 'Journals'],
    ];
    foreach ($this->subscribersData as $subscriber) {
      $this->createSubscriber($subscriber['first_name'], $subscriber['last_name'], $subscriber['email'], $subscriber['status'] ?? null);
    }
    foreach ($this->segmentsData as $segment) {
      $this->createSegment($segment['name']);
    }
    foreach ($this->customFieldsData as $customField) {
      $this->createCustomField($customField['name'], $customField['type']);
    }
    $subscriber1 = $this->subscribersRepository->findOneById(1);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber2 = $this->subscribersRepository->findOneById(2);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $subscriber3 = $this->subscribersRepository->findOneById(3);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);
    $customField = $this->customFieldsRepository->findOneById(1);
    $this->assertInstanceOf(CustomFieldEntity::class, $customField);
    $segment1 = $this->segmentsRepository->findOneById(1);
    $this->assertInstanceOf(SegmentEntity::class, $segment1);
    $segment2 = $this->segmentsRepository->findOneById(2);
    $this->assertInstanceOf(SegmentEntity::class, $segment2);
    $this->createSubscriberCustomField($subscriber2, $customField, $this->subscribersData[1][1]);

    $this->createSubscriberSegment($subscriber1, $segment1, SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->createSubscriberSegment($subscriber1, $segment2, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegment($subscriber2, $segment1, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriberSegment($subscriber3, $segment2, SubscriberEntity::STATUS_SUBSCRIBED);

    $this->export = $this->createExport($this->jSONData);
  }

  public function testItCanConstruct() {
    expect($this->export->exportFormatOption)
      ->equals('csv');
    expect($this->export->subscriberFields)
      ->equals(
        [
          'email',
          'first_name',
          '1',
        ]
      );
    expect($this->export->subscriberCustomFields)
      ->equals($this->export->getSubscriberCustomFields());
    expect($this->export->formattedSubscriberFields)
      ->equals(
        $this->export->formatSubscriberFields(
          $this->export->subscriberFields,
          $this->export->subscriberCustomFields
        )
      );
    expect($this->export->formattedSubscriberFieldsWithList)
      ->equals(
        \array_merge(
          $this->export->formattedSubscriberFields,
          [__('List', 'mailpoet')]
        )
      );
    expect(
      preg_match(
        '|' .
        preg_quote(Env::$tempPath, '|') . '/MailPoet_export_[a-z0-9]{15}.' .
        $this->export->exportFormatOption .
        '|', $this->export->exportFile)
    )->equals(1);
    expect(
      preg_match(
        '|' .
        preg_quote(Env::$tempUrl, '|') . '/' .
        basename($this->export->exportFile) .
        '|', $this->export->exportFileURL)
    )->equals(1);
  }

  public function testItCanGetSubscriberCustomFields() {
    $source = $this->customFieldsRepository->findOneBy(['name' => $this->customFieldsData[0]['name']]);
    $this->assertInstanceOf(CustomFieldEntity::class, $source);
    $target = $this->export->getSubscriberCustomFields();
    expect($target)->equals([$source->getId() => $source->getName()]);
  }

  public function testItCanFormatSubscriberFields() {
    $formattedSubscriberFields = $this->export->formatSubscriberFields(
      array_keys($this->subscriberFields),
      $this->export->getSubscriberCustomFields()
    );
    expect($formattedSubscriberFields)
      ->equals(array_values($this->subscriberFields));
  }

  public function testItProperlyReturnsSubscriberCustomFields() {
    $subscribers = $this->export->getSubscribers() ?? [];
    foreach ($subscribers as $subscriber) {
      if ($subscriber['email'] === $this->subscribersData[1]) {
        expect($subscriber['Country'])
          ->equals($this->subscribersData[1][1]);
      }
    }
  }

  public function testItCanGetSubscribers() {
    $jsonData = $this->jSONData;
    $jsonData['segments'] = [1];
    $export = $this->createExport($jsonData);
    $subscribers = $export->getSubscribers();
    expect($subscribers)->count(2);

    $jsonData['segments'] = [2];
    $export = $this->createExport($jsonData);
    $subscribers = $export->getSubscribers();
    expect($subscribers)->count(2);
  }

  public function testItCanGetSubscribersOnlyWithoutSegments() {
    $jsonData = $this->jSONData;
    $jsonData['segments'] = [0];
    $export = $this->createExport($jsonData);
    $subscribers = $export->getSubscribers() ?? [];
    expect($subscribers)->count(1);
    expect($subscribers[0]['segment_name'])->equals('Not In Segment');
  }

  public function testItRequiresWritableExportFile() {
    try {
      $this->export->exportPath = '/fake_folder';
      $this->export->process();
      $this->fail('Export did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())
        ->equals("The export file could not be saved on the server.");
    }
  }

  public function testItCanProcess() {
    try {
      $this->export->exportFile = $this->export->getExportFile('csv');
      $this->export->exportFormatOption = 'csv';
      $result = $this->export->process();
    } catch (\Exception $e) {
      $this->fail('Export to .csv process threw an exception');
    }
    expect($result['totalExported'])->equals(4);
    expect($result['exportFileURL'])->notEmpty();

    try {
      $this->export->exportFile = $this->export->getExportFile('xlsx');
      $this->export->exportFormatOption = 'xlsx';
      $result = $this->export->process();
    } catch (\Exception $e) {
      $this->fail('Export to .xlsx process threw an exception');
    }
    expect($result['totalExported'])->equals(4);
    expect($result['exportFileURL'])->notEmpty();
  }

  private function createCustomField(string $name, string $type): CustomFieldEntity {
    $customField = new CustomFieldEntity();
    $customField->setName($name);
    $customField->setType($type);
    $this->customFieldsRepository->persist($customField);
    $this->customFieldsRepository->flush();
    return $customField;
  }

  private function createSubscriberCustomField(
    SubscriberEntity $subscriber,
    CustomFieldEntity $customField,
    string $value
  ): SubscriberCustomFieldEntity {
    $subscriberCustomField = new SubscriberCustomFieldEntity($subscriber, $customField, $value);
    $this->subscriberCustomFieldRepository->persist($subscriberCustomField);
    $this->subscriberCustomFieldRepository->flush();
    return $subscriberCustomField;
  }

  private function createSubscriberSegment(SubscriberEntity $subscriber, SegmentEntity $segment, string $status): SubscriberSegmentEntity {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, $status);
    $this->subscriberSegmentRepository->persist($subscriberSegment);
    $this->customFieldsRepository->flush();
    return $subscriberSegment;
  }

  private function createSegment(string $name): SegmentEntity {
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DEFAULT, '');
    $this->segmentsRepository->persist($segment);
    $this->segmentsRepository->flush();
    return $segment;
  }

  private function createSubscriber(
    string $firstName,
    string $lastName,
    string $email,
    ?string $status = null
  ): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setFirstName($firstName);
    $subscriber->setLastName($lastName);
    $subscriber->setEmail($email);
    if ($status) {
      $subscriber->setStatus($status);
    }
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();
    return $subscriber;
  }

  private function createExport(array $jsonData): Export {
    return new Export(
      $this->customFieldsRepository,
      $this->importExportRepository,
      $this->segmentsRepository,
      $jsonData
    );
  }

  public function _after() {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(CustomFieldEntity::class);
    $this->truncateEntity(SubscriberCustomFieldEntity::class);
  }
}
