<?php

use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;

class CustomFieldCest {
  function _before() {
    $this->custom_field = CustomField::createOrUpdate(array(
      'name' => 'Birthdate',
      'type' => 'date'
    ));

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
    expect($this->custom_field->name)->equals('Birthdate');
  }

  function itHasAType() {
    expect($this->custom_field->type)->equals('date');
  }

  function itCanDecodeParams() {
    $custom_field = $this->custom_field->asArray();
    expect($custom_field['params'])->hasKey('label');
  }

  function itHasDefaultParams() {
    $params = unserialize($this->custom_field->params);
    expect($params['label'])->equals('Birthdate');
  }

  function itHasToBeValid() {
    $invalid_custom_field = CustomField::create();

    $result = $invalid_custom_field->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('You need to specify a name.');
    expect($errors[1])->equals('You need to specify a type.');
  }

  function itCanBeUpdated() {
    $custom_field = $this->custom_field->asArray();
    $custom_field['name'] = 'Favorite color';
    $custom_field['type'] = 'text';

    $custom_field = CustomField::createOrUpdate($custom_field);

    expect($custom_field->getErrors())->false();

    $updated_custom_field = CustomField::findOne($custom_field->id);
    expect($updated_custom_field->name)->equals('Favorite color');
    expect($updated_custom_field->type)->equals('text');
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