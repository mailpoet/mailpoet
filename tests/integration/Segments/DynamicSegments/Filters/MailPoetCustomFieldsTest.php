<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;

class MailPoetCustomFieldsTest extends \MailPoetTest {

  /** @var MailPoetCustomFields */
  private $filter;

  /** @var SubscriberEntity[] */
  private $subscribers = [];

  public function _before() {
    $this->cleanData();
    $this->filter = $this->diContainer->get(MailPoetCustomFields::class);
    $this->subscribers = [];
    $this->subscribers[] = $this->createSubscriber('subscriber1@example.com');
    $this->subscribers[] = $this->createSubscriber('subscriber2@example.com');
    $this->subscribers[] = $this->createSubscriber('subscriber3@example.com');
    $this->entityManager->flush();
  }

  public function testItFiltersSubscribersWithTextEquals() {
    $subscriber = $this->subscribers[2];
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_TEXT);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($subscriber, $customField, 'some value'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $segmentFilter = $this->getSegmentFilter(new DynamicSegmentFilterData([
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => MailPoetCustomFields::TYPE,
      'customFieldId' => $customField->getId(),
      'customFieldType' => CustomFieldEntity::TYPE_TEXT,
      'operator' => 'equals',
      'value' => 'some value',
    ]));
    $this->entityManager->flush();

    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();

    expect(count($result))->equals(1);
    $filteredSubscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $filteredSubscriber);
    expect($filteredSubscriber->getEmail())->equals($subscriber->getEmail());
  }

  public function testItFiltersSubscribersWithTextContains() {
    $subscriber = $this->subscribers[1];
    $customField = $this->createCustomField(CustomFieldEntity::TYPE_TEXT);
    $this->entityManager->persist(new SubscriberCustomFieldEntity($subscriber, $customField, 'some value'));
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    $segmentFilter = $this->getSegmentFilter(new DynamicSegmentFilterData([
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'action' => MailPoetCustomFields::TYPE,
      'customFieldId' => $customField->getId(),
      'customFieldType' => CustomFieldEntity::TYPE_TEXT,
      'operator' => 'contains',
      'value' => 'value',
    ]));
    $this->entityManager->flush();

    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();

    expect(count($result))->equals(1);
    $filteredSubscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $filteredSubscriber);
    expect($filteredSubscriber->getEmail())->equals($subscriber->getEmail());
  }

  private function getSegmentFilter($segmentFilterData): DynamicSegmentFilterEntity {
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $segmentFilterData);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $dynamicSegmentFilter;
  }

  private function createSubscriber(string $email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setLastName('Last');
    $subscriber->setFirstName('First');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriber);
    return $subscriber;
  }

  private function createCustomField($type, $params = []): CustomFieldEntity {
    $customField = new CustomFieldEntity();
    $customField->setType($type);
    $customField->setParams($params);
    $customField->setName('custom field' . rand());
    return $customField;
  }

  private function cleanData() {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(CustomFieldEntity::class);
    $this->truncateEntity(SubscriberCustomFieldEntity::class);
    $this->truncateEntity(SegmentEntity::class);
  }

  private function getQueryBuilder() {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id")
      ->from($subscribersTable);
  }
}
