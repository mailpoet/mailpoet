<?php

use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;

class CustomFieldCest {
  function _before() {
    $this->data = array(
      'name' => 'City',
      'type' => 'text',
      'params' => array(
        'label' => 'What is your city?'
      )
    );
    $this->custom_field = CustomField::createOrUpdate($this->data);

    $this->subscribers = array(
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
    expect($this->custom_field->id() > 0)->true();
    expect($this->custom_field->getErrors())->false();
  }

  function itHasAName() {
    expect($this->custom_field->name)->equals($this->data['name']);
  }

  function itHasAType() {
    expect($this->custom_field->type)->equals($this->data['type']);
  }

  function itHasSerializedParams() {
    $params = unserialize($this->custom_field->params);
    expect($params)->equals($this->data['params']);
  }

  function itCanDecodeParams() {
    $custom_field = $this->custom_field->asArray();
    expect($custom_field['params'])->equals($this->data['params']);
  }

  function itHasToBeValid() {
    $invalid_custom_field = CustomField::create();

    $result = $invalid_custom_field->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('You need to specify a name.');
    expect($errors[1])->equals('You need to specify a type.');
  }

  function itHasACreatedAtOnCreation() {
    $custom_field = CustomField::findOne($this->custom_field->id);
    expect($custom_field->created_at)->notNull();
    expect($custom_field->created_at)->notEquals('0000-00-00 00:00:00');
  }

  function itHasAnUpdatedAtOnCreation() {
    $custom_field = CustomField::findOne($this->custom_field->id);
    expect($custom_field->updated_at)
      ->equals($custom_field->created_at);
  }

  function itUpdatesTheUpdatedAtOnUpdate() {
    $custom_field = CustomField::findOne($this->custom_field->id);
    $created_at = $custom_field->created_at;

    sleep(1);

    $custom_field->name = 'Country';
    $custom_field->save();

    $updated_custom_field = CustomField::findOne($custom_field->id);
    expect($updated_custom_field->created_at)->equals($created_at);
    $is_time_updated = (
      $updated_custom_field->updated_at > $updated_custom_field->created_at
    );
    expect($is_time_updated)->true();
  }

  function itCanHaveManySubscribers() {
    foreach($this->subscribers as $subscriber) {
      $subscriber = Subscriber::createOrUpdate($subscriber);

      $association = SubscriberCustomField::create();
      $association->subscriber_id = $subscriber->id;
      $association->custom_field_id = $this->custom_field->id;
      $association->save();
    }
    $custom_field = CustomField::findOne($this->custom_field->id);
    $subscribers = $custom_field->subscribers()->findArray();
    expect(count($subscribers))->equals(2);
  }

  function itCanHaveAValue() {
    $subscriber = Subscriber::createOrUpdate($this->subscribers[0]);

    $association = SubscriberCustomField::create();
    $association->subscriber_id = $subscriber->id;
    $association->custom_field_id = $this->custom_field->id;
    $association->value = '12/12/2012';
    $association->save();
    $custom_field = CustomField::findOne($this->custom_field->id);
    $subscriber = $custom_field->subscribers()->findOne();
    expect($subscriber->value)->equals($association->value);
  }

  function _after() {
    CustomField::deleteMany();
    Subscriber::deleteMany();
    SubscriberCustomField::deleteMany();
  }
}