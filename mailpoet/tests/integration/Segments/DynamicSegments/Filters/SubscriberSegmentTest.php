<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoetVendor\Carbon\CarbonImmutable;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class SubscriberSegmentTest extends \MailPoetTest {
  /** @var SubscriberSegment */
  private $filter;

  /** @var SegmentEntity */
  private $segment1;
  /** @var SegmentEntity */
  private $segment2;

  public function _before(): void {
    $this->filter = $this->diContainer->get(SubscriberSegment::class);

    $subscriber1 = new SubscriberEntity();
    $subscriber1->setLastSubscribedAt(CarbonImmutable::now());
    $subscriber1->setEmail('a1@example.com');
    $this->entityManager->persist($subscriber1);

    $subscriber2 = new SubscriberEntity();
    $subscriber2->setLastSubscribedAt(CarbonImmutable::now());
    $subscriber2->setEmail('a2@example.com');
    $this->entityManager->persist($subscriber2);

    $subscriber3 = new SubscriberEntity();
    $subscriber3->setLastSubscribedAt(CarbonImmutable::now());
    $subscriber3->setEmail('a3@example.com');
    $this->entityManager->persist($subscriber3);

    $this->segment1 = new SegmentEntity('Segment 1', SegmentEntity::TYPE_DEFAULT, 'Segment 1');
    $this->segment2 = new SegmentEntity('Segment 2', SegmentEntity::TYPE_DEFAULT, 'Segment 2');
    $this->entityManager->persist($this->segment1);
    $this->entityManager->persist($this->segment2);

    $this->entityManager->persist(new SubscriberSegmentEntity($this->segment1, $subscriber1, SubscriberEntity::STATUS_SUBSCRIBED));

    $this->entityManager->persist(new SubscriberSegmentEntity($this->segment2, $subscriber1, SubscriberEntity::STATUS_SUBSCRIBED));
    $this->entityManager->persist(new SubscriberSegmentEntity($this->segment2, $subscriber2, SubscriberEntity::STATUS_SUBSCRIBED));
    $this->entityManager->flush();
  }

  public function testSubscribedAnyOf(): void {
    $segmentFilter = $this->getSegmentFilter(DynamicSegmentFilterData::OPERATOR_ANY, [$this->segment1->getId(), $this->segment2->getId()]);
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();

    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();

    expect(count($result))->equals(2);
    $this->assertIsArray($result[0]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('a1@example.com');
    $this->assertIsArray($result[1]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('a2@example.com');
  }

  public function testSubscribedAllOf(): void {
    $segmentFilter = $this->getSegmentFilter(DynamicSegmentFilterData::OPERATOR_ALL, [$this->segment1->getId(), $this->segment2->getId()]);
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();

    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();

    expect(count($result))->equals(1);
    $this->assertIsArray($result[0]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('a1@example.com');
  }

  public function testSubscribedNoneOf(): void {
    $segmentFilter = $this->getSegmentFilter(DynamicSegmentFilterData::OPERATOR_NONE, [$this->segment1->getId()]);
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();

    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();

    expect(count($result))->equals(2);
    $this->assertIsArray($result[0]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('a2@example.com');
    $this->assertIsArray($result[1]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('a3@example.com');
  }

  private function getSegmentFilter(string $operator, array $segments): DynamicSegmentFilterEntity {
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberSegment::TYPE, [
      'operator' => $operator,
      'segments' => $segments,
    ]);
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $segmentFilterData);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $dynamicSegmentFilter;
  }

  private function getQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("DISTINCT $subscribersTable.id")
      ->from($subscribersTable);
  }
}
