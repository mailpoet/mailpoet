<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\CarbonImmutable;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class SubscriberSubscribedDateTest extends \MailPoetTest {

  /** @var SubscriberSubscribedDate */
  private $filter;

  public function _before(): void {
    $this->filter = $this->diContainer->get(SubscriberSubscribedDate::class);
    $this->cleanUp();

    $subscriber = new SubscriberEntity();
    $subscriber->setLastSubscribedAt(CarbonImmutable::now());
    $subscriber->setEmail('e1@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setLastSubscribedAt(CarbonImmutable::now()->subDays(1));
    $subscriber->setEmail('e12@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setLastSubscribedAt(CarbonImmutable::now()->subDays(2));
    $subscriber->setEmail('e123@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setLastSubscribedAt(CarbonImmutable::now()->subDays(3));
    $subscriber->setEmail('e1234@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setLastSubscribedAt(CarbonImmutable::now()->subDays(4));
    $subscriber->setEmail('e12345@example.com');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
  }

  public function testGetBefore(): void {
    $segmentFilter = $this->getSegmentFilter(SubscriberSubscribedDate::BEFORE, CarbonImmutable::now()->subDays(3)->format('Y-m-d'));
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e12345@example.com');
  }

  public function testGetAfter(): void {
    $segmentFilter = $this->getSegmentFilter(SubscriberSubscribedDate::AFTER, CarbonImmutable::now()->subDays(2)->format('Y-m-d'));
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(2);

    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e1@example.com');

    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e12@example.com');
  }

  public function testGetOn(): void {
    $segmentFilter = $this->getSegmentFilter(SubscriberSubscribedDate::ON, CarbonImmutable::now()->subDays(2)->format('Y-m-d'));
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();

    $this->assertCount(1, $result);

    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->assertSame('e123@example.com', $subscriber->getEmail());
  }

  public function testGetNotOn(): void {
    $segmentFilter = $this->getSegmentFilter(SubscriberSubscribedDate::NOT_ON, CarbonImmutable::now()->subDays(2)->format('Y-m-d'));
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();

    $this->assertCount(4, $result);

    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $this->assertSame('e1@example.com', $subscriber1->getEmail());

    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $this->assertSame('e12@example.com', $subscriber2->getEmail());

    $subscriber3 = $this->entityManager->find(SubscriberEntity::class, $result[2]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);
    $this->assertSame('e1234@example.com', $subscriber3->getEmail());

    $subscriber4 = $this->entityManager->find(SubscriberEntity::class, $result[3]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber4);
    $this->assertSame('e12345@example.com', $subscriber4->getEmail());
  }

  public function testGetInTheLast(): void {
    $segmentFilter = $this->getSegmentFilter(SubscriberSubscribedDate::IN_THE_LAST, '2');
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(2);

    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e1@example.com');

    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e12@example.com');
  }

  public function testGetNotInTheLast(): void {
    $segmentFilter = $this->getSegmentFilter(SubscriberSubscribedDate::NOT_IN_THE_LAST, '3');
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(2);

    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e1234@example.com');

    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e12345@example.com');
  }

  private function getQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id")
      ->from($subscribersTable);
  }

  private function getSegmentFilter(string $operator, string $value): DynamicSegmentFilterEntity {
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberSubscribedDate::TYPE, [
      'operator' => $operator,
      'value' => $value,
    ]);
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $segmentFilterData);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $dynamicSegmentFilter;
  }

  private function cleanUp(): void {
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(DynamicSegmentFilterEntity::class);
  }
}
