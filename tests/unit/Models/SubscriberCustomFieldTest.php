<?php
use MailPoet\Models\SubscriberCustomField;

class SubscriberCustomFieldTest extends MailPoetTest {
  function __construct() {
    $this->data = array(
      array(
        10, // custom_field_id
        12, // subscriber_id
        'Test 1' // value
      ),
      array(
        10, // custom_field_id
        13, // subscriber_id
        'Test 2' // value
      )
    );
  }

  function testItCanBeCreated() {
    $subscriberCustomField = SubscriberCustomField::create();
    $subscriberCustomField->custom_field_id = $this->data[0][0];
    $subscriberCustomField->subscriber_id = $this->data[0][1];
    $subscriberCustomField->value = $this->data[0][2];
    $subscriberCustomField->save();
    expect($subscriberCustomField->id())->greaterOrEquals(1);
    expect($subscriberCustomField->getErrors())->false();
  }

  function testItCanCreateMultipleRecords() {
    SubscriberCustomField::createMultiple($this->data);
    $records = SubscriberCustomField::findArray();
    expect(count($records))->equals(2);
    expect($records[0]['value'])->equals('Test 1');
    expect($records[1]['value'])->equals('Test 2');
  }

  function testItCanUpdateMultipleRecords() {
    SubscriberCustomField::createMultiple($this->data);
    $updated_data = $this->data;
    $updated_data[0][2] = 'Updated';
    SubscriberCustomField::updateMultiple($updated_data);
    $records = SubscriberCustomField::findArray();
    expect($records[0]['value'])->equals('Updated');
    expect($records[1]['value'])->equals('Test 2');
  }

  function _after() {
    ORM::forTable(SubscriberCustomField::$_table)
      ->deleteMany();
  }
}