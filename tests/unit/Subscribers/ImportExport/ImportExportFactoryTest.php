<?php
namespace MailPoet\Test\Subscribers\ImportExport;

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;

class ImportExportFactoryTest extends \MailPoetTest {
  function _before() {
    $segment_1 = Segment::createOrUpdate(array('name' => 'Unconfirmed Segment'));
    $segment_2 = Segment::createOrUpdate(array('name' => 'Confirmed Segment'));

    $subscriber_1 = Subscriber::createOrUpdate(array(
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'status' => Subscriber::STATUS_UNCONFIRMED,
      'email' => 'john@mailpoet.com'
    ));

    $subscriber_2 = Subscriber::createOrUpdate(array(
      'first_name' => 'Mike',
      'last_name' => 'Smith',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'email' => 'mike@mailpoet.com'
    ));

    $association = SubscriberSegment::create();
    $association->subscriber_id = $subscriber_1->id;
    $association->segment_id = $segment_1->id;
    $association->save();

    $association = SubscriberSegment::create();
    $association->subscriber_id = $subscriber_2->id;
    $association->segment_id = $segment_2->id;
    $association->save();

    CustomField::createOrUpdate(array(
      'name' => 'Birthday',
      'type' => 'date'
    ));

    $this->importFactory = new ImportExportFactory('import');
    $this->exportFactory = new ImportExportFactory('export');
  }

  function testItCanGetSegmentsWithSubscriberCount() {
    $segments = $this->importFactory->getSegments();
    expect(count($segments))->equals(2);
    expect($segments[0]['name'])->equals('Confirmed Segment');
    expect($segments[0]['subscriberCount'])->equals(1);
    expect($segments[1]['name'])->equals('Unconfirmed Segment');
    expect($segments[1]['subscriberCount'])->equals(0);
  }

  function testItCanGetPublicSegmentsForImport() {
    $segments = $this->importFactory->getSegments();
    expect($segments[0]['subscriberCount'])->equals(1);
    expect($segments[1]['subscriberCount'])->equals(0);

    $subscriber = Subscriber::where(
      'email', 'mike@mailpoet.com'
    )->findOne();
    expect($subscriber->deleted_at)->null();
    $subscriber->trash();

    $subscriber = Subscriber::where(
      'email', 'mike@mailpoet.com'
    )->whereNull('deleted_at')->findOne();
    expect($subscriber)->false();

    $segments = $this->importFactory->getSegments();
    expect($segments[0]['subscriberCount'])->equals(0);
    expect($segments[1]['subscriberCount'])->equals(0);
  }

  function testItCanGetPublicSegmentsForExport() {
    $segments = $this->exportFactory->getSegments();
    expect(count($segments))->equals(2);
    $subscriber = Subscriber::where('email', 'john@mailpoet.com')
      ->findOne();
    $subscriber->deleted_at = date('Y-m-d H:i:s');
    $subscriber->save();
    $segments = $this->exportFactory->getSegments();
    expect(count($segments))->equals(1);
  }

  function testItCanGetSegmentsForExport() {
    $segments = $this->exportFactory->getSegments();
    expect(count($segments))->equals(2);

    expect($segments[0]['name'])->equals('Confirmed Segment');
    expect($segments[0]['subscriberCount'])->equals(1);
    expect($segments[1]['name'])->equals('Unconfirmed Segment');
    expect($segments[1]['subscriberCount'])->equals(1);
  }

  function testItCanGetSubscriberFields() {
    $subsriberFields = $this->importFactory->getSubscriberFields();
    $fields = array(
      'email',
      'first_name',
      'last_name'
    );
    foreach($fields as $field) {
      expect(in_array($field, array_keys($subsriberFields)))->true();
    }
    // export fields contain extra data
    $this->importFactory->action = 'export';
    $subsriberFields = $this->importFactory->getSubscriberFields();
    $export_fields = array(
      'email',
      'first_name',
      'last_name',
      'list_status',
      'global_status',
      'subscribed_ip'
    );
    foreach($export_fields as $field) {
      expect(in_array($field, array_keys($subsriberFields)))->true();
    }
  }

  function testItCanFormatSubsciberFields() {
    $formattedSubscriberFields =
      $this->importFactory->formatSubscriberFields(
        $this->importFactory->getSubscriberFields()
      );
    $fields = array(
      'id',
      'name',
      'type',
      'custom'
    );
    foreach($fields as $field) {
      expect(in_array($field, array_keys($formattedSubscriberFields[0])))
        ->true();
    }
    expect($formattedSubscriberFields[0]['custom'])->false();
  }

  function testItCanGetSubsciberCustomFields() {
    $subscriberCustomFields =
      $this->importFactory
        ->getSubscriberCustomFields();
    expect($subscriberCustomFields[0]['type'])
      ->equals('date');
  }

  function testItCanFormatSubsciberCustomFields() {
    $formattedSubscriberCustomFields =
      $this->importFactory->formatSubscriberCustomFields(
        $this->importFactory->getSubscriberCustomFields()
      );
    $fields = array(
      'id',
      'name',
      'type',
      'custom'
    );
    foreach($fields as $field) {
      expect(in_array($field, array_keys($formattedSubscriberCustomFields[0])))
        ->true();
    }
    expect($formattedSubscriberCustomFields[0]['custom'])->true();
  }

  function testItCanFormatFieldsForSelect2Import() {
    $ImportExportFactory = clone($this->importFactory);
    $select2FieldsWithoutCustomFields = array(
      array(
        'name' => 'Actions',
        'children' => array(
          array(
            'id' => 'ignore',
            'name' => 'Ignore field...',
          ),
          array(
            'id' => 'create',
            'name' => 'Create new field...'
          ),
        )
      ),
      array(
        'name' => 'System fields',
        'children' => $ImportExportFactory->formatSubscriberFields(
          $ImportExportFactory->getSubscriberFields()
        )
      )
    );
    $select2FieldsWithCustomFields = array_merge(
      $select2FieldsWithoutCustomFields,
      array(
        array(
          'name' => 'User fields',
          'children' => $ImportExportFactory->formatSubscriberCustomFields(
            $ImportExportFactory->getSubscriberCustomFields()
          )
        )
      ));
    $formattedFieldsForSelect2 = $ImportExportFactory->formatFieldsForSelect2(
      $ImportExportFactory->getSubscriberFields(),
      $ImportExportFactory->getSubscriberCustomFields()
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithCustomFields);
    $formattedFieldsForSelect2 = $ImportExportFactory->formatFieldsForSelect2(
      $ImportExportFactory->getSubscriberFields(),
      array()
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithoutCustomFields);
  }

  function testItCanFormatFieldsForSelect2Export() {
    $ImportExportFactory = clone($this->exportFactory);
    $select2FieldsWithoutCustomFields = array(
      array(
        'name' => 'Actions',
        'children' => array(
          array(
            'id' => 'select',
            'name' => 'Select all...',
          ),
          array(
            'id' => 'deselect',
            'name' => 'Deselect all...'
          ),
        )
      ),
      array(
        'name' => 'System fields',
        'children' => $ImportExportFactory->formatSubscriberFields(
          $ImportExportFactory->getSubscriberFields()
        )
      )
    );
    $select2FieldsWithCustomFields = array_merge(
      $select2FieldsWithoutCustomFields,
      array(
        array(
          'name' => 'User fields',
          'children' => $ImportExportFactory->formatSubscriberCustomFields(
            $ImportExportFactory->getSubscriberCustomFields()
          )
        )
      ));
    $formattedFieldsForSelect2 = $ImportExportFactory->formatFieldsForSelect2(
      $ImportExportFactory->getSubscriberFields(),
      $ImportExportFactory->getSubscriberCustomFields()
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithCustomFields);
    $formattedFieldsForSelect2 = $ImportExportFactory->formatFieldsForSelect2(
      $ImportExportFactory->getSubscriberFields(),
      array()
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithoutCustomFields);
  }

  function testItCanBootStrapImport() {
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

  function testItCanBootStrapExport() {
    $export = clone($this->importFactory);
    $exportMenu = $export->bootstrap();
    expect(count(json_decode($exportMenu['segments'], true)))
      ->equals(2);
    // action, system fields, user fields
    expect(count(json_decode($exportMenu['subscriberFieldsSelect2'], true)))
      ->equals(3);
  }

  function _after() {
    Subscriber::deleteMany();
    Segment::deleteMany();
    SubscriberSegment::deleteMany();
    CustomField::deleteMany();
  }
}
