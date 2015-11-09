<?php

use MailPoet\Import\BootstrapMenu;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;

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
        'email' => 'john@mailpoet.com'
      ),
      array(
        'first_name' => 'Mike',
        'last_name' => 'Smith',
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
    $this->bootStrapMenu = new BootstrapMenu();
  }

  function itCanGetSegments() {
    $this->_createSegmentsAndSubscribers();
    $segments = $this->bootStrapMenu->getSegments();
    expect(count($segments))->equals(2);
    expect($segments[0]['name'])->equals($this->segmentsData[0]['name']);
    expect($segments[0]['subscriberCount'])->equals(1);
    expect($segments[1]['subscriberCount'])->equals(0);
  }

  function itCanGetSubscriberFields() {
    $subsriberFields = $this->bootStrapMenu->getSubscriberFields();
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
      $this->bootStrapMenu->formatSubscriberFields(
        $this->bootStrapMenu->getSubscriberFields()
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
      $this->bootStrapMenu
        ->getSubscriberCustomFields();
    expect($subscriberCustomFields[0]['type'])
      ->equals($this->customFieldsData['type']);
  }

  function itCanFormatSubsciberCustomFields() {
    $formattedSubscriberCustomFields =
      $this->bootStrapMenu->formatSubscriberCustomFields(
        $this->bootStrapMenu->getSubscriberCustomFields()
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

  function itCanFormatFieldsForSelect2() {
    $bootStrapMenu = clone($this->bootStrapMenu);
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
          $bootStrapMenu->subscriberFields
        )
      )
    );
    $select2FieldsWithCustomFields = array_merge(
      $select2FieldsWithoutCustomFields,
      array(
        array(
          'name' => __('User columns'),
          'children' => $bootStrapMenu->formatSubscriberCustomFields(
            $bootStrapMenu->subscriberCustomFields
          )
        )
      ));
    $formattedFieldsForSelect2 = $bootStrapMenu->formatFieldsForSelect2(
      $bootStrapMenu->subscriberFields,
      $bootStrapMenu->subscriberCustomFields
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithCustomFields);
    $bootStrapMenu->subscriberCustomFields = false;
    $formattedFieldsForSelect2 = $bootStrapMenu->formatFieldsForSelect2(
      $bootStrapMenu->subscriberFields,
      $bootStrapMenu->subscriberCustomFields
    );
    expect($formattedFieldsForSelect2)->equals($select2FieldsWithoutCustomFields);
  }

  function itCanBootstrap() {
    $customField = CustomField::create();
    $customField->hydrate($this->customFieldsData);
    $customField->save();
    $bootstrap = clone($this->bootStrapMenu);
    $this->_createSegmentsAndSubscribers();
    $bootstrap->segments = $bootstrap->getSegments();
    $menu = $bootstrap->bootstrap();
    expect(count(json_decode($menu['segments'], true)))->equals(2);
    // email, first_name, last_name, status + 1 custom field
    expect(count(json_decode($menu['subscriberFields'], true)))->equals(5);
    // action, system columns, user columns
    expect(count(json_decode($menu['subscriberFieldsSelect2'], true)))->equals(3);
    expect($menu['maxPostSize'])->equals(ini_get('post_max_size'));
    expect($menu['maxPostSizeBytes'])->equals(
      (int) ini_get('post_max_size') * 1048576
    );
  }

  function _createSegmentsAndSubscribers() {
    foreach ($this->segmentsData as $segmentData) {
      $segment = Segment::create();
      $segment->hydrate($segmentData);
      $segment->save();
    }
    foreach ($this->subscribersData as $index => $subscriberData) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($subscriberData);
      $subscriber->save();
      $association = SubscriberSegment::create();
      $association->subscriber_id = $subscriber->id;
      $association->segment_id = $index;
      $association->save();
    };
  }

  function _after() {
    ORM::forTable(Subscriber::$_table)
      ->deleteMany();
    ORM::forTable(CustomField::$_table)
      ->deleteMany();
    ORM::forTable(Segment::$_table)
      ->deleteMany();
    ORM::forTable(SubscriberSegment::$_table)
      ->deleteMany();
  }
}