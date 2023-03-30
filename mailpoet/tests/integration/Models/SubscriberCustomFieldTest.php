<?php declare(strict_types = 1);

namespace MailPoet\Test\Models;

use MailPoet\Models\SubscriberCustomField;

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
    $subscriberCustomField->customFieldId = $this->data[0]['custom_field_id'];
    $subscriberCustomField->subscriberId = $this->data[0]['subscriber_id'];
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
    $updatedData = $this->data;
    $updatedData[0]['value'] = 'Updated';
    $updatedData = array_map('array_values', $updatedData);
    SubscriberCustomField::updateMultiple($updatedData);
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
    parent::_after();
    SubscriberCustomField::deleteMany();
  }
}
