<?php

use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\BootStrapMenu;

class BootStrapMenuCest {
  function _before() {
    $this->segmentsData = array(
      array(
        'name' => 'first',
        'description' => 'some description'
      ),
      array(
        'name' => 'second',
        'description' => 'some description'
      )
    );
    $this->subscribersData = array(
      array(
        'first_name' => 'John',
        'last_name' => 'Mailer',
        'status' => 'unconfirmed',
        'email' => 'john@mailpoet.com'
      ),
      array(
        'first_name' => 'Mike',
        'last_name' => 'Smith',
        'status' => 'subscribed',
        'email' => 'mike@maipoet.com'
      )
    );
    $this->customFieldsData = array(
      'name' => 'DOB',
      'type' => 'date',
    );
    $customField = CustomField::create();
    $customField->hydrate($this->customFieldsData);
    $customField->save();
    $this->bootStrapImportMenu = new BootStrapMenu('import');
    $this->bootStrapExportMenu = new BootStrapMenu('export');
  }

  function itCanGetSegmentsWithSubscriberCount() {
    $this->_createSegmentsAndSubscribers();
    $segments = $this->bootStrapImportMenu->getSegments();
    expect(count($segments))->equals(2);
    expect($segments[0]['name'])->equals($this->segmentsData[0]['name']);
    expect($segments[0]['subscriberCount'])->equals(1);
    expect($segments[1]['subscriberCount'])->equals(1);
  }

  function itCanGetSegmentsForImportWithoutTrashedSubscribers() {
    $this->_createSegmentsAndSubscribers();
    $segments = $this->bootStrapImportMenu->getSegments();
    expect(count($segments))->equals(2);
    $subscriber = Subscriber::findOne(1);
    $subscriber->deleted_at = date('Y-m-d H:i:s');
    $subscriber->save();
    $segments = $this->bootStrapImportMenu->getSegments();
    expect(count($segments))->equals(1);
  }

  function itCanGetSegmentsForExportWithoutTrashedSubscribers() {
    $this->_createSegmentsAndSubscribers();
    $segments = $this->bootStrapExportMenu->getSegments();
    expect(count($segments))->equals(2);
    $subscriber = Subscriber::findOne(1);
    $subscriber->deleted_at = date('Y-m-d H:i:s');
    $subscriber->save();
    $segments = $this->bootStrapExportMenu->getSegments();
    expect(count($segments))->equals(1);
  }

  function itCanGetSegmentsForExport() {
    $this->_createSegmentsAndSubscribers();
    $segments = $this->bootStrapExportMenu->getSegments();
    expect(count($segments))->equals(2);
    expect($segments[0]['name'])->equals($this->segmentsData[0]['name']);
    expect($segments[0]['subscriberCount'])->equals(1);
    expect($segments[1]['subscriberCount'])->equals(1);
  }

  function itCanGetSegmentsWithConfirmedSubscribersForExport() {
    $this->_createSegmentsAndSubscribers();
    $segments = $this->bootStrapExportMenu->getSegments(
      $withConfirmedSubscribers = true
    );
    expect(count($segments))->equals(1);
    expect($segments[0]['name'])->equals($this->segmentsData[1]['name']);
  }

  function itCanGetSubscriberFields() {
    $subsriberFields = $this->bootStrapImportMenu->getSubscriberFields();
    $fields = array(
      'email',
      'first_name',
      'last_name',
      'status'
    );
    foreach ($fields as $field) {
      expect(in_array($field, array_keys($subsriberFields)))->true();
    }
  }

  function itCanFormatSubsciberFields() {
    $formattedSubscriberFields =
      $this->bootStrapImportMenu->formatSubscriberFields(
        $this->bootStrapImportMenu->getSubscriberFields()
      );
    $fields = array(
      'id',
      'name',
      'type',
      'custom'
    );
    foreach ($fields as $field) {
      expect(in_array($field, array_keys($formattedSubscriberFields[0])))
        ->true();
    }
    expect($formattedSubscriberFields[0]['custom'])->false();
  }

  function itCanGetSubsciberCustomFields() {
    $subscriberCustomFields =
      $this->bootStrapImportMenu
        ->getSubscriberCustomFields();
    expect($subscriberCustomFields[0]['type'])
      ->equals($this->customFieldsData['type']);
  }

  function itCanFormatSubsciberCustomFields() {
    $formattedSubscriberCustomFields =
      $this->bootStrapImportMenu->formatSubscriberCustomFields(
        $this->bootStrapImportMenu->getSubscriberCustomFields()
      );
    $fields = array(
      'id',
      'name',
      'type',
      'custom'
    );
    foreach ($fields as $field) {
      expect(in_array($field, array_keys($formattedSubscriberCustomFields[0])))
        ->true();
    }
    expect($formattedSubscriberCustomFields[0]['custom'])->true();
  }

  function itCanFormatFieldsForSelect2Import() {
    $bootStrapMenu = clone($this->bootStrapImportMenu);
    $select2FieldsWithoutCustomFields = array(
      array(
        'name' => 'Actions',
        'children' => array(
          array(
            'id' => 'ignore',
            'name' => 'Ignore column...',
          ),
          array(
            'id' => 'create',
            'name' => 'Create new column...'
          ),
        )
      ),
      array(
        'name' => 'System columns',
        'children' => $bootStrapMenu->formatSubscriberFields(
          $bootStrapMenu->getSubscriberFields()
        )
      )
    );
    $select2FieldsWithCustomFields = array_merge(
      $select2FieldsWithoutCustomFields,
      array(
        array(
          'name' => __('User columns'),
          'children' => $bootStrapMenu->formatSubscriberCustomFields(
            $bootStrapMenu->getSubscriberCustomFields()
          )
        )
      ));
    $formattedFieldsForSelect2 = $bootStrapMenu->formatFieldsForSelect2(
      $bootStrapMenu->getSubscriberFields(),
      $bootStrapMenu->getSubscriberCustomFields()
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithCustomFields);
    $formattedFieldsForSelect2 = $bootStrapMenu->formatFieldsForSelect2(
      $bootStrapMenu->getSubscriberFields(),
      array()
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithoutCustomFields);
  }

  function itCanFormatFieldsForSelect2Export() {
    $bootStrapMenu = clone($this->bootStrapExportMenu);
    $select2FieldsWithoutCustomFields = array(
      array(
        'name' => 'Actions',
        'children' => array(
          array(
            'id' => 'select',
            'name' => __('Select all...'),
          ),
          array(
            'id' => 'deselect',
            'name' => __('Deselect all...')
          ),
        )
      ),
      array(
        'name' => 'System columns',
        'children' => $bootStrapMenu->formatSubscriberFields(
          $bootStrapMenu->getSubscriberFields()
        )
      )
    );
    $select2FieldsWithCustomFields = array_merge(
      $select2FieldsWithoutCustomFields,
      array(
        array(
          'name' => __('User columns'),
          'children' => $bootStrapMenu->formatSubscriberCustomFields(
            $bootStrapMenu->getSubscriberCustomFields()
          )
        )
      ));
    $formattedFieldsForSelect2 = $bootStrapMenu->formatFieldsForSelect2(
      $bootStrapMenu->getSubscriberFields(),
      $bootStrapMenu->getSubscriberCustomFields()
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithCustomFields);
    $formattedFieldsForSelect2 = $bootStrapMenu->formatFieldsForSelect2(
      $bootStrapMenu->getSubscriberFields(),
      array()
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithoutCustomFields);
  }

  function itCanBootStrapImport() {
    $customField = CustomField::create();
    $customField->hydrate($this->customFieldsData);
    $customField->save();
    $this->_createSegmentsAndSubscribers();
    $import = clone($this->bootStrapImportMenu);
    $importMenu = $import->bootstrap();
    expect(count(json_decode($importMenu['segments'], true)))
      ->equals(2);
    // email, first_name, last_name, status + 1 custom field
    expect(count(json_decode($importMenu['subscriberFields'], true)))
      ->equals(5);
    // action, system columns, user columns
    expect(count(json_decode($importMenu['subscriberFieldsSelect2'], true)))
      ->equals(3);
    expect($importMenu['maxPostSize'])->equals(ini_get('post_max_size'));
    expect($importMenu['maxPostSizeBytes'])->equals(
      (int) ini_get('post_max_size') * 1048576
    );
  }

  function itCanBootStrapExport() {
    $customField = CustomField::create();
    $customField->hydrate($this->customFieldsData);
    $customField->save();
    $this->_createSegmentsAndSubscribers();
    $export = clone($this->bootStrapImportMenu);
    $exportMenu = $export->bootstrap();
    expect(count(json_decode($exportMenu['segments'], true)))
      ->equals(2);
    // action, system columns, user columns
    expect(count(json_decode($exportMenu['subscriberFieldsSelect2'], true)))
      ->equals(3);
  }

  function _createSegmentsAndSubscribers() {
    foreach ($this->segmentsData as $segmentData) {
      $segment = Segment::create();
      $segment->hydrate($segmentData);
      $segment->save();
    }
    foreach ($this->subscribersData as $subscriberData) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriberData);
      $subscriber->save();
      $association = SubscriberSegment::create();
      $association->subscriber_id = $subscriber->id;
      $association->segment_id = $subscriber->id;
      $association->save();
    };
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
  }
}