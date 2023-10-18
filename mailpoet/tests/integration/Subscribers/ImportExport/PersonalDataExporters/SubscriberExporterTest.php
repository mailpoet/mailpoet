<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class SubscriberExporterTest extends \MailPoetTest {

  /** @var SubscriberExporter */
  private $exporter;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  public function _before() {
    parent::_before();
    $this->exporter = new SubscriberExporter(
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(CustomFieldsRepository::class)
    );
    $this->subscriberFactory = new SubscriberFactory();
  }

  public function testExportWorksWhenSubscriberNotFound() {
    $result = $this->exporter->export('email.that@doesnt.exists');
    expect($result)->array();
    verify($result)->arrayHasKey('data');
    verify($result['data'])->equals([]);
    verify($result)->arrayHasKey('done');
    verify($result['done'])->equals(true);
  }

  public function testExportSubscriberWithoutCustomFields() {
    $email = 'email.that@has.no.custom.fields';
    $this->subscriberFactory
      ->withFirstName('John')
      ->withLastName('Doe')
      ->withEmail($email)
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCreatedAt(new Carbon('2018-05-03 10:30:08'))
      ->create();

    $result = $this->exporter->export($email);
    expect($result)->array();
    verify($result)->arrayHasKey('data');
    verify($result)->arrayHasKey('done');
    expect($result['data'])->array();
    verify($result['data'])->arrayCount(1);
    verify($result['done'])->equals(true);
    verify($result['data'][0])->arrayHasKey('group_id');
    verify($result['data'][0])->arrayHasKey('group_label');
    verify($result['data'][0])->arrayHasKey('item_id');
    verify($result['data'][0])->arrayHasKey('data');
    $expected = [
      ['name' => 'First Name', 'value' => 'John'],
      ['name' => 'Last Name', 'value' => 'Doe'],
      ['name' => 'Email', 'value' => 'email.that@has.no.custom.fields'],
      ['name' => 'Status', 'value' => 'unconfirmed'],
      ['name' => 'Created at', 'value' => '2018-05-03 10:30:08'],
      ['name' => "Subscriber's subscription source", 'value' => 'Unknown'],
    ];
    verify($result['data'][0]['data'])->equals($expected);
  }

  public function testExportSubscriberWithSource() {
    $email = 'email.with@source.com';
    $this->subscriberFactory
      ->withFirstName('John')
      ->withLastName('Doe')
      ->withEmail($email)
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCreatedAt(new Carbon('2018-05-03 10:30:08'))
      ->withSource(Source::FORM)
      ->create();

    $result = $this->exporter->export($email);
    expect($result['data'][0]['data'])->contains([
      'name' => "Subscriber's subscription source",
      'value' => 'Subscription via a MailPoet subscription form',
    ]);
  }

  public function testExportSubscriberWithIPs() {
    $email = 'email.that@has.ip.addresses';
    $this->subscriberFactory
      ->withFirstName('John')
      ->withLastName('Doe')
      ->withEmail($email)
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCreatedAt(new Carbon('2018-05-03 10:30:08'))
      ->withSubscribedIp('IP1')
      ->withConfirmedIp('IP2')
      ->create();

    $result = $this->exporter->export($email);
    expect($result['data'][0]['data'])->contains(['name' => 'Subscribed IP', 'value' => 'IP1']);
    expect($result['data'][0]['data'])->contains(['name' => 'Confirmed IP', 'value' => 'IP2']);
  }

  public function testExportSubscriberWithCustomField() {
    $email = 'email.that@has.custom.fields';
    $subscriber = $this->subscriberFactory
      ->withEmail($email)
      ->create();

    $customFieldFactory = new CustomFieldFactory();
    $customField1 = $customFieldFactory
      ->withName('Custom field1')
      ->withType(CustomFieldEntity::TYPE_TEXT)
      ->withSubscriber($subscriber->getId(), 'Value')
      ->create();

    $result = $this->exporter->export($email);
    expect($result['data'][0]['data'])->contains(['name' => 'Custom field1', 'value' => 'Value']);
  }
}
