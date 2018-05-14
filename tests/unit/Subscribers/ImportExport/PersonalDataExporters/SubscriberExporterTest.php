<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;

class SubscriberExporterTest extends \MailPoetTest {

  /** @var SubscriberExporter */
  private $exporter;

  function _before() {
    $this->exporter = new SubscriberExporter();
  }

  function testExportWorksWhenSubscriberNotFound() {
    $result = $this->exporter->export('email.that@doesnt.exists');
    expect($result)->internalType('array');
    expect($result)->hasKey('data');
    expect($result['data'])->equals(array());
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  function testExportSubscriberWithoutCustomFields() {
    Subscriber::createOrUpdate(array(
      'email' => 'email.that@has.no.custom.fields',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => 'unconfirmed',
      'created_at' => '2018-05-03 10:30:08',
    ));
    $result = $this->exporter->export('email.that@has.no.custom.fields');
    expect($result)->internalType('array');
    expect($result)->hasKey('data');
    expect($result)->hasKey('done');
    expect($result['data'])->internalType('array');
    expect($result['data'])->count(1);
    expect($result['done'])->equals(true);
    expect($result['data'][0])->hasKey('group_id');
    expect($result['data'][0])->hasKey('group_label');
    expect($result['data'][0])->hasKey('item_id');
    expect($result['data'][0])->hasKey('data');
    $expected = array(
      array('name' => 'First Name', 'value' => 'John'),
      array('name' => 'Last Name', 'value' => 'Doe'),
      array('name' => 'Email', 'value' => 'email.that@has.no.custom.fields'),
      array('name' => 'Status', 'value' => 'unconfirmed'),
      array('name' => 'Created at', 'value' => '2018-05-03 10:30:08'),
    );
    expect($result['data'][0]['data'])->equals($expected);
  }

  function testExportSubscriberWithIPs() {
    Subscriber::createOrUpdate(array(
      'email' => 'email.that@has.ip.addresses',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => 'unconfirmed',
      'created_at' => '2018-05-03 10:30:08',
      'subscribed_ip' => 'IP1',
      'confirmed_ip' => 'IP2',
    ));
    $result = $this->exporter->export('email.that@has.ip.addresses');
    expect($result['data'][0]['data'])->contains(array('name' => 'Subscribed IP', 'value' => 'IP1'));
    expect($result['data'][0]['data'])->contains(array('name' => 'Confirmed IP', 'value' => 'IP2'));
  }

  function testExportSubscriberWithCustomField() {
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'email.that@has.custom.fields',
    ));
    $custom_field1 = CustomField::createOrUpdate(array(
      'name' => 'Custom field1',
      'type' => 'input'
    ));
    CustomField::createOrUpdate(array(
      'name' => 'Custom field2',
      'type' => 'input'
    ));
    $subscriber->setCustomField($custom_field1->id(), 'Value');
    $subscriber->setCustomField('123545657', 'Value');
    $result = $this->exporter->export('email.that@has.custom.fields');
    expect($result['data'][0]['data'])->contains(array('name' => 'Custom field1', 'value' => 'Value'));
  }

}
