<?php declare(strict_types = 1);

namespace MailPoet\Test\Models;

use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;

class CustomFieldTest extends \MailPoetTest {
  public $data;
  public $subscribers;
  public $customField;

  public function _before() {
    parent::_before();
    $this->data = [
      'name' => 'City',
      'type' => CustomField::TYPE_TEXT,
      'params' => [
        'label' => 'What is your city?',
      ],
    ];
    $this->customField = CustomField::createOrUpdate($this->data);

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
    expect($this->customField->id() > 0)->true();
    expect($this->customField->getErrors())->false();
  }

  public function testItCanBeUpdated() {
    expect($this->customField->name)->equals($this->data['name']);

    $updatedCustomField = CustomField::createOrUpdate([
      'id' => $this->customField->id,
      'name' => 'Country',
    ]);

    expect($updatedCustomField->getErrors())->false();
    expect($updatedCustomField->name)->equals('Country');
    expect($updatedCustomField->id)->equals($this->customField->id);
  }

  public function testItHasAName() {
    expect($this->customField->name)->equals($this->data['name']);
  }

  public function testItHasAType() {
    expect($this->customField->type)->equals($this->data['type']);
  }

  public function testItHasSerializedParams() {
    $params = unserialize($this->customField->params);
    expect($params)->equals($this->data['params']);
  }

  public function testItCanDecodeParams() {
    $customField = $this->customField->asArray();
    expect($customField['params'])->equals($this->data['params']);
  }

  public function testItHasToBeValid() {
    $invalidCustomField = CustomField::create();

    $result = $invalidCustomField->save();
    $errors = $result->getErrors();

    expect(is_array($errors))->true();
    expect($errors[0])->equals('Please specify a name.');
    expect($errors[1])->equals('Please specify a type.');
  }

  public function testItHasACreatedAtOnCreation() {
    $customField = CustomField::findOne($this->customField->id);
    $this->assertInstanceOf(CustomField::class, $customField);
    expect($customField->createdAt)->notNull();
  }

  public function testItHasAnUpdatedAtOnCreation() {
    $customField = CustomField::findOne($this->customField->id);
    $this->assertInstanceOf(CustomField::class, $customField);
    expect($customField->updatedAt)->equals($customField->createdAt);
  }

  public function testItUpdatesTheUpdatedAtOnUpdate() {
    $customField = CustomField::findOne($this->customField->id);
    $this->assertInstanceOf(CustomField::class, $customField);
    $createdAt = $customField->createdAt;

    sleep(1);

    $customField->name = 'Country';
    $customField->save();

    $updatedCustomField = CustomField::findOne($customField->id);
    $this->assertInstanceOf(CustomField::class, $updatedCustomField);
    expect($updatedCustomField->createdAt)->equals($createdAt);
    $isTimeUpdated = (
      $updatedCustomField->updatedAt > $updatedCustomField->createdAt
    );
    expect($isTimeUpdated)->true();
  }

  public function testItCanHaveManySubscribers() {
    foreach ($this->subscribers as $subscriber) {
      $subscriber = Subscriber::createOrUpdate($subscriber);

      $association = SubscriberCustomField::create();
      $association->subscriberId = $subscriber->id;
      $association->customFieldId = $this->customField->id;
      $association->value = '';
      $association->save();
    }
    $customField = CustomField::findOne($this->customField->id);
    $this->assertInstanceOf(CustomField::class, $customField);
    $subscribers = $customField->subscribers()->findArray();
    expect(count($subscribers))->equals(2);
  }

  public function testItCanHaveAValue() {
    $subscriber = Subscriber::createOrUpdate($this->subscribers[0]);

    $association = SubscriberCustomField::create();
    $association->subscriberId = $subscriber->id;
    $association->customFieldId = $this->customField->id;
    $association->value = '12/12/2012';
    $association->save();
    $customField = CustomField::findOne($this->customField->id);
    $this->assertInstanceOf(CustomField::class, $customField);
    $subscriber = $customField->subscribers()->findOne();
    expect($subscriber->value)->equals($association->value);
  }

  public function _after() {
    parent::_after();
    CustomField::deleteMany();
    Subscriber::deleteMany();
    SubscriberCustomField::deleteMany();
  }
}
