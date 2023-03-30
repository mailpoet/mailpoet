<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscribers\ImportExport;

use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class ImportExportFactoryTest extends \MailPoetTest {
  /** @var ImportExportFactory */
  public $exportFactory;
  /** @var ImportExportFactory */
  public $importFactory;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    parent::_before();

    $segmentFactory = new SegmentFactory();
    $subscriberFactory = new SubscriberFactory();
    $customFieldFactory = new CustomFieldFactory();

    $segment1 = $segmentFactory->withName('Unconfirmed Segment')->create();
    $segment2 = $segmentFactory->withName('Confirmed Segment')->create();

    $subscriberFactory
      ->withFirstName('John')
      ->withLastName('Mailer')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withEmail('john@mailpoet.com')
      ->withSegments([$segment1])
      ->create();

    $subscriberFactory
      ->withFirstName('Mike')
      ->withLastName('Smith')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withEmail('mike@mailpoet.com')
      ->withSegments([$segment2])
      ->create();

    $customFieldFactory
      ->withName('Birthday')
      ->withType(CustomFieldEntity::TYPE_DATE)
      ->create();

    $this->importFactory = new ImportExportFactory('import');
    $this->exportFactory = new ImportExportFactory('export');
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
  }

  public function testItCanGetSegmentsWithSubscriberCount() {
    $segments = $this->importFactory->getSegments();
    expect(count($segments))->equals(2);
    expect($segments[0]['name'])->equals('Confirmed Segment');
    expect($segments[0]['count'])->equals(1);
    expect($segments[1]['name'])->equals('Unconfirmed Segment');
    expect($segments[1]['count'])->equals(0);
  }

  public function testItCanGetPublicSegmentsForImport() {
    $segments = $this->importFactory->getSegments();
    expect($segments[0]['count'])->equals(1);
    expect($segments[1]['count'])->equals(0);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'mike@mailpoet.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getDeletedAt())->null();

    $this->subscribersRepository->bulkTrash([$subscriber->getId()]);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'mike@mailpoet.com', 'deletedAt' => null]);
    expect($subscriber)->null();

    $segments = $this->importFactory->getSegments();
    expect($segments[0]['count'])->equals(0);
    expect($segments[1]['count'])->equals(0);
  }

  public function testItCanGetPublicSegmentsForExport() {
    $segments = $this->exportFactory->getSegments();
    expect(count($segments))->equals(2);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'john@mailpoet.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscriber->setDeletedAt(new Carbon());
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();

    $segments = $this->exportFactory->getSegments();
    expect(count($segments))->equals(1);
  }

  public function testItCanGetSegmentsForExport() {
    $segments = $this->exportFactory->getSegments();
    expect(count($segments))->equals(2);

    expect($segments[0]['name'])->equals('Confirmed Segment');
    expect($segments[0]['count'])->equals(1);
    expect($segments[1]['name'])->equals('Unconfirmed Segment');
    expect($segments[1]['count'])->equals(1);
  }

  public function testItCanGetSubscriberFields() {
    $subsriberFields = $this->importFactory->getSubscriberFields();
    $fields = [
      'email',
      'first_name',
      'last_name',
    ];
    foreach ($fields as $field) {
      expect(in_array($field, array_keys($subsriberFields)))->true();
    }
    // export fields contain extra data
    $this->importFactory->action = 'export';
    $subsriberFields = $this->importFactory->getSubscriberFields();
    $exportFields = [
      'email',
      'first_name',
      'last_name',
      'list_status',
      'global_status',
      'subscribed_ip',
    ];
    foreach ($exportFields as $field) {
      expect(in_array($field, array_keys($subsriberFields)))->true();
    }
  }

  public function testItCanFormatSubscriberFields() {
    $formattedSubscriberFields =
      $this->importFactory->formatSubscriberFields(
        $this->importFactory->getSubscriberFields()
      );
    $fields = [
      'id',
      'name',
      'type',
      'custom',
    ];
    foreach ($fields as $field) {
      expect(in_array($field, array_keys($formattedSubscriberFields[0])))
        ->true();
    }
    expect($formattedSubscriberFields[0]['custom'])->false();
  }

  public function testItCanGetSubscriberCustomFields() {
    $subscriberCustomFields =
      $this->importFactory
        ->getSubscriberCustomFields();
    expect($subscriberCustomFields[0]['type'])
      ->equals('date');
  }

  public function testItCanFormatSubscriberCustomFields() {
    $formattedSubscriberCustomFields =
      $this->importFactory->formatSubscriberCustomFields(
        $this->importFactory->getSubscriberCustomFields()
      );
    $fields = [
      'id',
      'name',
      'type',
      'custom',
    ];
    foreach ($fields as $field) {
      expect(in_array($field, array_keys($formattedSubscriberCustomFields[0])))
        ->true();
    }
    expect($formattedSubscriberCustomFields[0]['custom'])->true();
  }

  public function testItCanFormatFieldsForSelect2Import() {
    $importExportFactory = clone($this->importFactory);
    $select2FieldsWithoutCustomFields = [
      [
        'name' => 'Actions',
        'text' => 'Actions',
        'children' => [
          [
            'id' => 'ignore',
            'name' => 'Ignore field...',
            'text' => 'Ignore field...',
          ],
          [
            'id' => 'create',
            'name' => 'Create new field...',
            'text' => 'Create new field...',
          ],
        ],
      ],
      [
        'name' => 'System fields',
        'text' => 'System fields',
        'children' => $importExportFactory->formatSubscriberFields(
          $importExportFactory->getSubscriberFields()
        ),
      ],
    ];
    $select2FieldsWithCustomFields = array_merge(
      $select2FieldsWithoutCustomFields,
      [
        [
          'name' => 'User fields',
          'text' => 'User fields',
          'children' => $importExportFactory->formatSubscriberCustomFields(
            $importExportFactory->getSubscriberCustomFields()
          ),
        ],
      ]);
    $formattedFieldsForSelect2 = $importExportFactory->formatFieldsForSelect2(
      $importExportFactory->getSubscriberFields(),
      $importExportFactory->getSubscriberCustomFields()
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithCustomFields);
    $formattedFieldsForSelect2 = $importExportFactory->formatFieldsForSelect2(
      $importExportFactory->getSubscriberFields(),
      []
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithoutCustomFields);
  }

  public function testItCanFormatFieldsForSelect2Export() {
    $importExportFactory = clone($this->exportFactory);
    $select2FieldsWithoutCustomFields = [
      [
        'name' => 'Actions',
        'text' => 'Actions',
        'children' => [
          [
            'id' => 'select',
            'name' => 'Select all...',
            'text' => 'Select all...',
          ],
          [
            'id' => 'deselect',
            'name' => 'Deselect all...',
            'text' => 'Deselect all...',
          ],
        ],
      ],
      [
        'name' => 'System fields',
        'text' => 'System fields',
        'children' => $importExportFactory->formatSubscriberFields(
          $importExportFactory->getSubscriberFields()
        ),
      ],
    ];
    $select2FieldsWithCustomFields = array_merge(
      $select2FieldsWithoutCustomFields,
      [
        [
          'name' => 'User fields',
          'text' => 'User fields',
          'children' => $importExportFactory->formatSubscriberCustomFields(
            $importExportFactory->getSubscriberCustomFields()
          ),
        ],
      ]);
    $formattedFieldsForSelect2 = $importExportFactory->formatFieldsForSelect2(
      $importExportFactory->getSubscriberFields(),
      $importExportFactory->getSubscriberCustomFields()
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithCustomFields);
    $formattedFieldsForSelect2 = $importExportFactory->formatFieldsForSelect2(
      $importExportFactory->getSubscriberFields(),
      []
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithoutCustomFields);
  }

  public function testItCanBootStrapImport() {
    $import = clone($this->importFactory);
    $importMenu = $import->bootstrap();
    expect(count((array)json_decode($importMenu['segments'], true)))
      ->equals(2);
    // email, first_name, last_name, subscribed_ip, created_at, confirmed_ip, confirmed_at + 1 custom field
    expect(count((array)json_decode($importMenu['subscriberFields'], true)))
      ->equals(8);
    // action, system fields, user fields
    expect(count((array)json_decode($importMenu['subscriberFieldsSelect2'], true)))
      ->equals(3);
    expect($importMenu['maxPostSize'])->equals(ini_get('post_max_size'));
    expect($importMenu['maxPostSizeBytes'])->equals(
      (int)ini_get('post_max_size') * 1048576
    );
  }

  public function testItCanBootStrapExport() {
    $export = clone($this->importFactory);
    $exportMenu = $export->bootstrap();
    expect(count((array)json_decode($exportMenu['segments'], true)))
      ->equals(2);
    // action, system fields, user fields
    expect(count((array)json_decode($exportMenu['subscriberFieldsSelect2'], true)))
      ->equals(3);
  }

  public function _after() {
    parent::_after();
    $this->clearSubscribersCountCache();
  }
}
