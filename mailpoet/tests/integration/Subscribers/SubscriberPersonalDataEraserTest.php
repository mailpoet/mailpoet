<?php

namespace MailPoet\Subscribers;

use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoetVendor\Idiorm\ORM;

class SubscriberPersonalDataEraserTest extends \MailPoetTest {

  /** @var SubscriberPersonalDataEraser */
  private $eraser;

  public function _before() {
    parent::_before();
    $this->eraser = new SubscriberPersonalDataEraser();
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
  }

  public function testExportWorksWhenSubscriberNotFound() {
    $result = $this->eraser->erase('email.that@doesnt.exists');
    expect($result)->array();
    expect($result)->hasKey('items_removed');
    expect($result['items_removed'])->equals(0);
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  public function testItDeletesCustomFields() {
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'eraser.test.email.that@has.custom.fields',
    ]);
    $customField1 = CustomField::createOrUpdate([
      'name' => 'Custom field1',
      'type' => 'input',
    ]);
    $customField2 = CustomField::createOrUpdate([
      'name' => 'Custom field2',
      'type' => 'input',
    ]);
    $subscriber->setCustomField($customField1->id(), 'Value');
    $subscriber->setCustomField($customField2->id(), 'Value');

    $this->eraser->erase('eraser.test.email.that@has.custom.fields');

    $subscriberCustomFields = SubscriberCustomField::where('subscriber_id', $subscriber->id())->findMany();
    expect($subscriberCustomFields)->count(2);
    expect($subscriberCustomFields[0]->value)->equals('');
    expect($subscriberCustomFields[1]->value)->equals('');

  }

  public function testItDeletesSubscriberData() {
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'subscriber@for.anon.test',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => 'subscribed',
      'created_at' => '2018-05-03 10:30:08',
      'subscribed_ip' => 'IP1',
      'confirmed_ip' => 'IP2',
      'unconfirmed_data' => 'xyz',
    ]);
    $this->eraser->erase('subscriber@for.anon.test');
    $subscriberAfter = Subscriber::findOne($subscriber->id());
    expect($subscriberAfter->firstName)->equals('Anonymous');
    expect($subscriberAfter->lastName)->equals('Anonymous');
    expect($subscriberAfter->status)->equals('unsubscribed');
    expect($subscriberAfter->subscribedIp)->equals('0.0.0.0');
    expect($subscriberAfter->confirmedIp)->equals('0.0.0.0');
    expect($subscriberAfter->unconfirmedData)->equals('');
  }

  public function testItDeletesSubscriberEmailAddress() {
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'subscriber@for.anon.test',
    ]);
    $this->eraser->erase('subscriber@for.anon.test');
    $subscriberAfter = Subscriber::findOne($subscriber->id());
    expect($subscriberAfter->email)->notEquals('subscriber@for.anon.test');
  }
}
