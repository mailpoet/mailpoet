<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\SubscriberCustomField;
use MailPoetVendor\Idiorm\ORM;

class SubscriberCustomFieldTest extends \MailPoetTest {
  public $data;
  public function __construct() {
    parent::__construct();
    $this->data = [
      [
        'custom_field_id' => 10,
        'subscriber_id' => 12,
        'value' => 'Test 1',
      ],
      [
        'custom_field_id' => 10,
        'subscriber_id' => 13,
        'value' => 'Test 2',
      ],
    ];
  }

  public function testItCanBeCreated() {
    $subscriberCustomField = SubscriberCustomField::create();
    $subscriberCustomField->custom_field_id = $this->data[0]['custom_field_id'];
    $subscriberCustomField->subscriber_id = $this->data[0]['subscriber_id'];
    $subscriberCustomField->value = $this->data[0]['value'];
    $subscriberCustomField->save();
    expect($subscriberCustomField->id())->greaterOrEquals(1);
    expect($subscriberCustomField->getErrors())->false();
  }

  public function testItCanCreateMultipleRecords() {
    $data = array_map('array_values', $this->data);
    SubscriberCustomField::createMultiple($data);
    $records = SubscriberCustomField::findArray();
    expect(count($records))->equals(2);
    expect($records[0]['value'])->equals('Test 1');
    expect($records[1]['value'])->equals('Test 2');
  }

  public function testItCanUpdateMultipleRecords() {
    $data = array_map('array_values', $this->data);
    SubscriberCustomField::createMultiple($data);
    $updated_data = $this->data;
    $updated_data[0]['value'] = 'Updated';
    $updated_data = array_map('array_values', $updated_data);
    SubscriberCustomField::updateMultiple($updated_data);
    $records = SubscriberCustomField::findArray();
    expect($records[0]['value'])->equals('Updated');
    expect($records[1]['value'])->equals('Test 2');
  }

  public function testItCanDeleteManySubscriberRelations() {
    $data = array_map('array_values', $this->data);
    SubscriberCustomField::createMultiple($data);
    SubscriberCustomField::deleteManySubscriberRelations(
      [
        $this->data[0]['subscriber_id'],
        $this->data[1]['subscriber_id'],
      ]
    );
    $records = SubscriberCustomField::findArray();
    expect($records)->isEmpty();
  }

  public function testItCanDeleteSubscriberRelations() {
    $data = array_map('array_values', $this->data);
    SubscriberCustomField::createMultiple($data);
    $subscriber = (object)['id' => $this->data[0]['subscriber_id']];
    SubscriberCustomField::deleteSubscriberRelations($subscriber);
    $records = SubscriberCustomField::findArray();
    expect($records)->count(1);
  }

  public function _after() {
    ORM::forTable(SubscriberCustomField::$_table)
      ->deleteMany();
  }
}
