<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class SubscriberPersonalDataEraserTest extends \MailPoetTest {

  /** @var SubscriberPersonalDataEraser */
  private $eraser;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberFactory */
  private $subscribersFactory;

  public function _before() {
    parent::_before();
    $this->eraser = $this->diContainer->get(SubscriberPersonalDataEraser::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->subscribersFactory = new SubscriberFactory();
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
    $customFieldFactory = new CustomFieldFactory();

    $subscriber = $this->subscribersFactory
      ->withEmail('eraser.test.email.that@has.custom.fields')
      ->create();

    $customField1 = $customFieldFactory
      ->withName('Custom field1')
      ->withType('input')
      ->create();
    $customField2 = $customFieldFactory
      ->withName('Custom field2')
      ->withType('input')
      ->create();

    $subscriberCustomField1 = new SubscriberCustomFieldEntity($subscriber, $customField1, 'Value');
    $this->entityManager->persist($subscriberCustomField1);
    $subscriberCustomField2 = new SubscriberCustomFieldEntity($subscriber, $customField2, 'Value');
    $this->entityManager->persist($subscriberCustomField2);
    $this->entityManager->flush();

    $this->eraser->erase('eraser.test.email.that@has.custom.fields');

    $subscriberCustomFieldRepository = $this->diContainer->get(SubscriberCustomFieldRepository::class);
    $subscriberCustomFields = $subscriberCustomFieldRepository->findBy(['subscriber' => $subscriber]);
    expect($subscriberCustomFields)->count(2);
    expect($subscriberCustomFields[0]->getValue())->equals('');
    expect($subscriberCustomFields[1]->getValue())->equals('');
  }

  public function testItDeletesSubscriberData() {
    $subscriber = $this->subscribersFactory
      ->withEmail('subscriber@for.anon.test')
      ->withFirstName('John')
      ->withLastName('Doe')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withCreatedAt(new Carbon('2018-05-03 10:30:08'))
      ->withSubscribedIp('IP1')
      ->withConfirmedIp('IP2')
      ->withUnconfirmedData('xyz')
      ->create();
    $this->eraser->erase('subscriber@for.anon.test');
    $subscriberAfter = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberAfter);
    expect($subscriberAfter->getFirstName())->equals('Anonymous');
    expect($subscriberAfter->getLastName())->equals('Anonymous');
    expect($subscriberAfter->getStatus())->equals('unsubscribed');
    expect($subscriberAfter->getSubscribedIp())->equals('0.0.0.0');
    expect($subscriberAfter->getConfirmedIp())->equals('0.0.0.0');
    expect($subscriberAfter->getUnconfirmedData())->equals('');
  }

  public function testItDeletesSubscriberEmailAddress() {
    $subscriber = $this->subscribersFactory
      ->withEmail('subscriber@for.anon.test')
      ->create();

    $this->eraser->erase('subscriber@for.anon.test');
    $subscriberAfter = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberAfter);
    expect($subscriberAfter->getEmail())->notEquals('subscriber@for.anon.test');
  }
}
