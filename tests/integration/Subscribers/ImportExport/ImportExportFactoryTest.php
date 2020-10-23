<?php

namespace MailPoet\Test\Subscribers\ImportExport;

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;

class ImportExportFactoryTest extends \MailPoetTest {
  public $exportFactory;
  public $importFactory;

  public function _before() {
    parent::_before();
    $segment1 = Segment::createOrUpdate(['name' => 'Unconfirmed Segment']);
    $segment2 = Segment::createOrUpdate(['name' => 'Confirmed Segment']);

    $subscriber1 = Subscriber::createOrUpdate([
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'status' => Subscriber::STATUS_UNCONFIRMED,
      'email' => 'john@mailpoet.com',
    ]);

    $subscriber2 = Subscriber::createOrUpdate([
      'first_name' => 'Mike',
      'last_name' => 'Smith',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'email' => 'mike@mailpoet.com',
    ]);

    $association = SubscriberSegment::create();
    $association->subscriberId = $subscriber1->id;
    $association->segmentId = $segment1->id;
    $association->save();

    $association = SubscriberSegment::create();
    $association->subscriberId = $subscriber2->id;
    $association->segmentId = $segment2->id;
    $association->save();

    CustomField::createOrUpdate([
      'name' => 'Birthday',
      'type' => 'date',
    ]);

    $this->importFactory = new ImportExportFactory('import');
    $this->exportFactory = new ImportExportFactory('export');
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

    $subscriber = Subscriber::where(
      'email', 'mike@mailpoet.com'
    )->findOne();
    expect($subscriber->deletedAt)->null();
    $subscriber->trash();

    $subscriber = Subscriber::where(
      'email', 'mike@mailpoet.com'
    )->whereNull('deleted_at')->findOne();
    expect($subscriber)->false();

    $segments = $this->importFactory->getSegments();
    expect($segments[0]['count'])->equals(0);
    expect($segments[1]['count'])->equals(0);
  }

  public function testItCanGetPublicSegmentsForExport() {
    $segments = $this->exportFactory->getSegments();
    expect(count($segments))->equals(2);
    $subscriber = Subscriber::where('email', 'john@mailpoet.com')
      ->findOne();
    $subscriber->deletedAt = date('Y-m-d H:i:s');
    $subscriber->save();
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
    expect(count(json_decode($importMenu['segments'], true)))
      ->equals(2);
    // email, first_name, last_name + 1 custom field
    expect(count(json_decode($importMenu['subscriberFields'], true)))
      ->equals(4);
    // action, system fields, user fields
    expect(count(json_decode($importMenu['subscriberFieldsSelect2'], true)))
      ->equals(3);
    expect($importMenu['maxPostSize'])->equals(ini_get('post_max_size'));
    expect($importMenu['maxPostSizeBytes'])->equals(
      (int)ini_get('post_max_size') * 1048576
    );
  }

  public function testItCanBootStrapExport() {
    $export = clone($this->importFactory);
    $exportMenu = $export->bootstrap();
    expect(count(json_decode($exportMenu['segments'], true)))
      ->equals(2);
    // action, system fields, user fields
    expect(count(json_decode($exportMenu['subscriberFieldsSelect2'], true)))
      ->equals(3);
  }

  public function _after() {
    Subscriber::deleteMany();
    Segment::deleteMany();
    SubscriberSegment::deleteMany();
    CustomField::deleteMany();
  }
}
