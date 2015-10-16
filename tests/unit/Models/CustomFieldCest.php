<?php

use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;

class CustomFieldCest {
  function _before() {
    $this->before_time = time();
    $this->data = array(
      'name' => 'city',
    );
    $this->customField = CustomField::create();
    $this->customField->hydrate($this->data);
    $this->saved = $this->customField->save();
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
  }

  function itCanBeCreated() {
    expect($this->saved)->equals(true);
  }

  function itHasToBeValid() {
    expect($this->saved)->equals(true);
    $empty_model = CustomField::create();
    expect($empty_model->save())->notEquals(true);
    $validations = $empty_model->getValidationErrors();
    expect(count($validations))->equals(1);
  }

  function itHasACreatedAtOnCreation() {
    $customField = CustomField::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($customField->created_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itHasAnUpdatedAtOnCreation() {
    $customField = CustomField::where('name', $this->data['name'])
      ->findOne();
    $time_difference = strtotime($customField->updated_at) >= $this->before_time;
    expect($time_difference)->equals(true);
  }

  function itKeepsTheCreatedAtOnUpdate() {
    $customField = CustomField::where('name', $this->data['name'])
      ->findOne();
    $old_created_at = $customField->created_at;
    $customField->name = 'new name';
    $customField->save();
    expect($old_created_at)->equals($customField->created_at);
  }

  function itUpdatesTheUpdatedAtOnUpdate() {
    $customField = CustomField::where('name', $this->data['name'])
      ->findOne();
    $update_time = time();
    $customField->name = 'new name';
    $customField->save();
    $time_difference = strtotime($customField->updated_at) >= $update_time;
    expect($time_difference)->equals(true);
  }

  function itCanHaveManySubscribers() {
    foreach ($this->subscribersData as $data) {
      $subscriber = Subscriber::create();
      $subscriber->hydrate($data);
      $subscriber->save();
      $association = SubscriberCustomField::create();
      $association->subscriber_id = $subscriber->id;
      $association->custom_field_id = $this->customField->id;
      $association->save();
    }
    $customField = CustomField::findOne($this->customField->id);
    $subscribers = $customField->subscribers()
      ->findArray();
    expect(count($subscribers))->equals(2);
  }

  function itCanStoreCustomFieldValue() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate($this->subscribersData[0]);
    $subscriber->save();
    $association = SubscriberCustomField::create();
    $association->subscriber_id = $subscriber->id;
    $association->custom_field_id = $this->customField->id;
    $association->value = 'test';
    $association->save();
    $customField = CustomField::findOne($this->customField->id);
    $subscriber = $customField->subscribers()
      ->findOne();
    expect($subscriber->value)->equals($association->value);
  }

  function _after() {
    ORM::forTable(CustomField::$_table)
      ->deleteMany();
    ORM::forTable(Subscriber::$_table)
      ->deleteMany();
    ORM::forTable(SubscriberCustomField::$_table)
      ->deleteMany();
  }
}