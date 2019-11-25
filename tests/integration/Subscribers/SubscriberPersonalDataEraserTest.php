<?php

namespace MailPoet\Subscribers;

use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoetVendor\Idiorm\ORM;

class SubscriberPersonalDataEraserTest extends \MailPoetTest {

  /** @var SubscriberPersonalDataEraser */
  private $eraser;

  function _before() {
    parent::_before();
    $this->eraser = new SubscriberPersonalDataEraser();
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
  }

  function testExportWorksWhenSubscriberNotFound() {
    $result = $this->eraser->erase('email.that@doesnt.exists');
    expect($result)->internalType('array');
    expect($result)->hasKey('items_removed');
    expect($result['items_removed'])->equals(0);
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  function testItDeletesCustomFields() {
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'eraser.test.email.that@has.custom.fields',
    ]);
    $custom_field1 = CustomField::createOrUpdate([
      'name' => 'Custom field1',
      'type' => 'input',
    ]);
    $custom_field2 = CustomField::createOrUpdate([
      'name' => 'Custom field2',
      'type' => 'input',
    ]);
    $subscriber->setCustomField($custom_field1->id(), 'Value');
    $subscriber->setCustomField($custom_field2->id(), 'Value');

    $this->eraser->erase('eraser.test.email.that@has.custom.fields');

    $subscriber_custom_fields = SubscriberCustomField::where('subscriber_id', $subscriber->id())->findMany();
    expect($subscriber_custom_fields)->count(2);
    expect($subscriber_custom_fields[0]->value)->equals('');
    expect($subscriber_custom_fields[1]->value)->equals('');

  }

  function testItDeletesSubscriberData() {
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
    $subscriber_after = Subscriber::findOne($subscriber->id());
    expect($subscriber_after->first_name)->equals('Anonymous');
    expect($subscriber_after->last_name)->equals('Anonymous');
    expect($subscriber_after->status)->equals('unsubscribed');
    expect($subscriber_after->subscribed_ip)->equals('0.0.0.0');
    expect($subscriber_after->confirmed_ip)->equals('0.0.0.0');
    expect($subscriber_after->unconfirmed_data)->equals('');
  }

  function testItDeletesSubscriberEmailAddress() {
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'subscriber@for.anon.test',
    ]);
    $this->eraser->erase('subscriber@for.anon.test');
    $subscriber_after = Subscriber::findOne($subscriber->id());
    expect($subscriber_after->email)->notEquals('subscriber@for.anon.test');
  }
}
