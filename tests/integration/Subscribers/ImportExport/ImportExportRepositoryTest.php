<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\ImportExport;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscriberCustomFieldRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;

class ImportExportRepositoryTest extends \MailPoetTest {
  /** @var ImportExportRepository */
  private $repository;
  /** @var CustomFieldsRepository */
  private $customFieldsRepository;
  /** @var SubscribersRepository */
  private $subscribersRepository;
  /** @var SubscriberCustomFieldRepository */
  private $subscriberCustomFieldRepository;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->repository = $this->diContainer->get(ImportExportRepository::class);
    $this->customFieldsRepository = $this->diContainer->get(CustomFieldsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->subscriberCustomFieldRepository = $this->diContainer->get(SubscriberCustomFieldRepository::class);
  }

  public function testItInsertMultipleSubscribers(): void {
    $columns = [
      'email',
      'first_name',
      'last_name',
    ];
    $data = [
      ['user1@test.com', 'One', 'User'],
      ['user2@test.com', 'Two', 'User'],
    ];
    $count = $this->repository->insertMultiple(
      SubscriberEntity::class,
      $columns,
      $data
    );

    expect($count)->equals(2);
    $subscribers = $this->subscribersRepository->findAll();
    expect($subscribers)->count(2);
    $user1 = $subscribers[0];
    assert($user1 instanceof SubscriberEntity);
    expect($user1->getEmail())->equals('user1@test.com');
    expect($user1->getFirstName())->equals('One');
    expect($user1->getLastName())->equals('User');
    $user2 = $subscribers[1];
    assert($user2 instanceof SubscriberEntity);
    expect($user2->getEmail())->equals('user2@test.com');
    expect($user2->getFirstName())->equals('Two');
    expect($user2->getLastName())->equals('User');
  }

  public function testItUpdateMultipleSubscribers(): void {
    $this->createSubscriber('user1@test.com', 'One', 'User');
    $this->createSubscriber('user2@test.com', 'Two', 'User');
    $columns = [
      'email',
      'first_name',
      'last_name',
    ];
    $data = [
      ['user1@test.com', 'OneOne', 'UserOne'],
      ['user2@test.com', 'TwoTwo', 'UserTwo'],
    ];
    $updatedAt = Carbon::createFromTimestamp(time());
    $count = $this->repository->updateMultiple(
      SubscriberEntity::class,
      $columns,
      $data,
      $updatedAt
    );

    expect($count)->equals(2);
    $this->entityManager->clear();
    $subscribers = $this->subscribersRepository->findAll();
    expect($subscribers)->count(2);
    $user1 = $subscribers[0];
    assert($user1 instanceof SubscriberEntity);
    expect($user1->getEmail())->equals('user1@test.com');
    expect($user1->getFirstName())->equals('OneOne');
    expect($user1->getLastName())->equals('UserOne');
    expect($user1->getUpdatedAt())->equals($updatedAt);
    $user2 = $subscribers[1];
    assert($user2 instanceof SubscriberEntity);
    expect($user2->getEmail())->equals('user2@test.com');
    expect($user2->getFirstName())->equals('TwoTwo');
    expect($user2->getLastName())->equals('UserTwo');
    expect($user2->getUpdatedAt())->equals($updatedAt);
  }

  public function testItInsertMultipleSubscriberCustomFields(): void {
    $subscriber1 = $this->createSubscriber('user1@test.com', 'One', 'User');
    $subscriber2 = $this->createSubscriber('user2@test.com', 'Two', 'User');
    $customField = $this->createCustomField('age');
    $columns = [
      'subscriber_id',
      'custom_field_id',
      'value',
    ];
    $data = [
      [$subscriber1->getId(), $customField->getId(), 20],
      [$subscriber2->getId(), $customField->getId(), 25],
    ];
    $count = $this->repository->insertMultiple(
      SubscriberCustomFieldEntity::class,
      $columns,
      $data
    );

    expect($count)->equals(2);
    $customFields = $this->subscriberCustomFieldRepository->findAll();
    expect($customFields)->count(2);
    $subscriberCustomField1 = $customFields[0];
    assert($subscriberCustomField1 instanceof SubscriberCustomFieldEntity);
    expect($subscriberCustomField1->getSubscriber())->equals($subscriber1);
    expect($subscriberCustomField1->getCustomField())->equals($customField);
    expect($subscriberCustomField1->getValue())->equals('20');
    $subscriberCustomField2 = $customFields[1];
    assert($subscriberCustomField2 instanceof SubscriberCustomFieldEntity);
    expect($subscriberCustomField2->getSubscriber())->equals($subscriber2);
    expect($subscriberCustomField2->getCustomField())->equals($customField);
    expect($subscriberCustomField2->getValue())->equals('25');
  }

  public function testItUpdateMultipleSubscriberCustomFields(): void {
    $subscriber1 = $this->createSubscriber('user1@test.com', 'One', 'User');
    $subscriber2 = $this->createSubscriber('user2@test.com', 'Two', 'User');
    $customField = $this->createCustomField('age');
    $subscriber1CustomField = $this->createSubscriberCustomField($subscriber1, $customField, '0');
    $subscriber2CustomField = $this->createSubscriberCustomField($subscriber2, $customField, '0');

    $columns = [
      'subscriber_id',
      'custom_field_id',
      'value',
    ];
    $data = [
      [$subscriber1->getId(), $customField->getId(), 20],
      [$subscriber2->getId(), $customField->getId(), 25],
    ];
    $updatedAt = Carbon::createFromTimestamp(time());
    $count = $this->repository->updateMultiple(
      SubscriberCustomFieldEntity::class,
      $columns,
      $data,
      $updatedAt
    );

    expect($count)->equals(2);
    $this->entityManager->clear();
    $this->subscribersRepository->findAll();
    $this->customFieldsRepository->findAll();
    $subscriberCustomFields = $this->subscriberCustomFieldRepository->findAll();
    $subscriberCustomField1 = $subscriberCustomFields[0];
    assert($subscriberCustomField1 instanceof SubscriberCustomFieldEntity);
    $resultSubscriber1 = $subscriberCustomField1->getSubscriber();
    assert($resultSubscriber1 instanceof SubscriberEntity);
    expect($resultSubscriber1->getId())->equals($subscriber1->getId());
    expect($subscriberCustomField1->getCustomField())->equals($customField);
    expect($subscriberCustomField1->getValue())->equals('20');
    $subscriberCustomField2 = $subscriberCustomFields[1];
    assert($subscriberCustomField2 instanceof SubscriberCustomFieldEntity);
    $resultSubscriber2 = $subscriberCustomField2->getSubscriber();
    assert($resultSubscriber2 instanceof SubscriberEntity);
    expect($resultSubscriber2->getId())->equals($subscriber2->getId());
    expect($subscriberCustomField2->getCustomField())->equals($customField);
    expect($subscriberCustomField2->getValue())->equals('25');
  }

  private function createSubscriber(string $email, string $firstName, string $lastName): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setFirstName($firstName);
    $subscriber->setLastName($lastName);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createCustomField(string $name): CustomFieldEntity {
    $customField = new CustomFieldEntity();
    $customField->setName($name);
    $customField->setType(CustomFieldEntity::TYPE_TEXT);
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    return $customField;
  }

  private function createSubscriberCustomField(
    SubscriberEntity $subscriber,
    CustomFieldEntity $customField,
    string $value
  ): SubscriberCustomFieldEntity {
    $subscirberCustomField = new SubscriberCustomFieldEntity($subscriber, $customField, $value);
    $this->entityManager->persist($subscirberCustomField);
    $this->entityManager->flush();
    return $subscirberCustomField;
  }

  private function cleanup() {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberCustomFieldEntity::class);
    $this->truncateEntity(CustomFieldEntity::class);
  }
}
