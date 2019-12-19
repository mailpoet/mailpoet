<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;

class CustomFieldTest extends \MailPoetTest {
  public $data;
  public $subscribers;
  public $custom_field;
  public function _before() {
    parent::_before();
    $this->data = [
      'name' => 'City',
      'type' => CustomField::TYPE_TEXT,
      'params' => [
        'label' => 'What is your city?',
      ],
    ];
    $this->custom_field = CustomField::createOrUpdate($this->data);

    $this->subscribers = [
      [
        'first_name' => 'John',
        'last_name' => 'Mailer',
        'email' => 'john@mailpoet.com',
      ],
      [
        'first_name' => 'Mike',
        'last_name' => 'Smith',
        'email' => 'mike@maipoet.com',
      ],
    ];
  }

  public function testItCanBeCreated() {
    expect($this->custom_field->id() > 0)->true();
    expect($this->custom_field->getErrors())->false();
  }

  public function testItCanBeUpdated() {
    expect($this->custom_field->name)->equals($this->data['name']);

    $updated_custom_field = CustomField::createOrUpdate([
      'id' => $this->custom_field->id,
      'name' => 'Country',
    ]);

    expect($updated_custom_field->getErrors())->false();
    expect($updated_custom_field->name)->equals('Country');
    expect($updated_custom_field->id)->equals($this->custom_field->id);
  }

  public function testItHasAName() {
    expect($this->custom_field->name)->equals($this->data['name']);
  }

  public function testItHasAType() {
    expect($this->custom_field->type)->equals($this->data['type']);
  }

  public function testItHasSerializedParams() {
    $params = unserialize($this->custom_field->params);
    expect($params)->equals($this->data['params']);
  }

  public function testItCanDecodeParams() {
    $custom_field = $this->custom_field->asArray();
    expect($custom_field['params'])->equals($this->data['params']);
  }

  public function testItHasToBeValid() {
    $invalid_custom_field = CustomField::create();

    $result = $invalid_custom_field->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('Please specify a name.');
    expect($errors[1])->equals('Please specify a type.');
  }

  public function testItHasACreatedAtOnCreation() {
    $custom_field = CustomField::findOne($this->custom_field->id);
    expect($custom_field->created_at)->notNull();
  }

  public function testItHasAnUpdatedAtOnCreation() {
    $custom_field = CustomField::findOne($this->custom_field->id);
    expect($custom_field->updated_at)
      ->equals($custom_field->created_at);
  }

  public function testItUpdatesTheUpdatedAtOnUpdate() {
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

  public function testItCanHaveManySubscribers() {
    foreach ($this->subscribers as $subscriber) {
      $subscriber = Subscriber::createOrUpdate($subscriber);

      $association = SubscriberCustomField::create();
      $association->subscriber_id = $subscriber->id;
      $association->custom_field_id = $this->custom_field->id;
      $association->value = '';
      $association->save();
    }
    $custom_field = CustomField::findOne($this->custom_field->id);
    $subscribers = $custom_field->subscribers()->findArray();
    expect(count($subscribers))->equals(2);
  }

  public function testItCanHaveAValue() {
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

  public function _after() {
    CustomField::deleteMany();
    Subscriber::deleteMany();
    SubscriberCustomField::deleteMany();
  }
}
