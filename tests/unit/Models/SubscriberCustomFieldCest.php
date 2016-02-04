<?php
use MailPoet\Models\SubscriberCustomField;

class SubscriberCustomFieldCest {
  function __construct() {
    $this->data = array(
      array(
        'custom_field_id' => 10,
        'subscriber_id' => 12,
        'value' => 'Test 1'
      ),
      array(
        'custom_field_id' => 10,
        'subscriber_id' => 13,
        'value' => 'Test 2'
      )
    );
  }

  function itCanBeCreated() {
    $subscriberCustomField = SubscriberCustomField::create();
    $subscriberCustomField->hydrate($this->data[0]);
    $subscriberCustomField->save();
    expect($subscriberCustomField->id() > 0)->true();
    expect($subscriberCustomField->getErrors())->false();
  }

  function itCanCreateOrUpdateMultipleRecords() {
    SubscriberCustomField::createMultiple($this->data);
    $records = SubscriberCustomField::findArray();
    expect(count($records))->equals(2);
    expect($records[1]['value'])->equals($this->data[1]['value']);
    $updatedData = $this->data;
    $updatedData[0]['value'] = 'updated';
    SubscriberCustomField::updateMultiple($updatedData);
    $records = SubscriberCustomField::findArray();
    expect($records[0]['value'])->equals($updatedData[0]['value']);
  }

  function _after() {
    ORM::forTable(SubscriberCustomField::$_table)
      ->deleteMany();
  }
}