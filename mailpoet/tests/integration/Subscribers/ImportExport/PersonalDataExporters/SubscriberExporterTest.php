<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Subscribers\SubscribersRepository;

class SubscriberExporterTest extends \MailPoetTest {

  /** @var SubscriberExporter */
  private $exporter;

  public function _before() {
    parent::_before();
    $this->exporter = new SubscriberExporter(
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(CustomFieldsRepository::class)
    );
  }

  public function testExportWorksWhenSubscriberNotFound() {
    $result = $this->exporter->export('email.that@doesnt.exists');
    expect($result)->array();
    expect($result)->hasKey('data');
    expect($result['data'])->equals([]);
    expect($result)->hasKey('done');
    expect($result['done'])->equals(true);
  }

  public function testExportSubscriberWithoutCustomFields() {
    Subscriber::createOrUpdate([
      'email' => 'email.that@has.no.custom.fields',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => 'unconfirmed',
      'created_at' => '2018-05-03 10:30:08',
    ]);
    $result = $this->exporter->export('email.that@has.no.custom.fields');
    expect($result)->array();
    expect($result)->hasKey('data');
    expect($result)->hasKey('done');
    expect($result['data'])->array();
    expect($result['data'])->count(1);
    expect($result['done'])->equals(true);
    expect($result['data'][0])->hasKey('group_id');
    expect($result['data'][0])->hasKey('group_label');
    expect($result['data'][0])->hasKey('item_id');
    expect($result['data'][0])->hasKey('data');
    $expected = [
      ['name' => 'First Name', 'value' => 'John'],
      ['name' => 'Last Name', 'value' => 'Doe'],
      ['name' => 'Email', 'value' => 'email.that@has.no.custom.fields'],
      ['name' => 'Status', 'value' => 'unconfirmed'],
      ['name' => 'Created at', 'value' => '2018-05-03 10:30:08'],
      ['name' => "Subscriber's subscription source", 'value' => 'Unknown'],
    ];
    expect($result['data'][0]['data'])->equals($expected);
  }

  public function testExportSubscriberWithSource() {
    Subscriber::createOrUpdate([
      'email' => 'email.with@source.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => 'unconfirmed',
      'created_at' => '2018-05-03 10:30:08',
      'source' => 'form',
    ]);
    $result = $this->exporter->export('email.with@source.com');
    expect($result['data'][0]['data'])->contains([
      'name' => "Subscriber's subscription source",
      'value' => 'Subscription via a MailPoet subscription form',
    ]);
  }

  public function testExportSubscriberWithIPs() {
    Subscriber::createOrUpdate([
      'email' => 'email.that@has.ip.addresses',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => 'unconfirmed',
      'created_at' => '2018-05-03 10:30:08',
      'subscribed_ip' => 'IP1',
      'confirmed_ip' => 'IP2',
    ]);
    $result = $this->exporter->export('email.that@has.ip.addresses');
    expect($result['data'][0]['data'])->contains(['name' => 'Subscribed IP', 'value' => 'IP1']);
    expect($result['data'][0]['data'])->contains(['name' => 'Confirmed IP', 'value' => 'IP2']);
  }

  public function testExportSubscriberWithCustomField() {
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'email.that@has.custom.fields',
    ]);
    $customField1 = CustomField::createOrUpdate([
      'name' => 'Custom field1',
      'type' => 'input',
    ]);
    CustomField::createOrUpdate([
      'name' => 'Custom field2',
      'type' => 'input',
    ]);
    $subscriber->setCustomField($customField1->id(), 'Value');
    $subscriber->setCustomField('123545657', 'Value');
    $result = $this->exporter->export('email.that@has.custom.fields');
    expect($result['data'][0]['data'])->contains(['name' => 'Custom field1', 'value' => 'Value']);
  }
}
