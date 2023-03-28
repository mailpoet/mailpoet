<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class SubscriberScoreTest extends \MailPoetTest {

  /** @var SubscriberScore */
  private $filter;

  public function _before(): void {
    $this->filter = $this->diContainer->get(SubscriberScore::class);

    $subscriber = new SubscriberEntity();
    $subscriber->setEngagementScore(0);
    $subscriber->setEmail('e1@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setEngagementScore(25);
    $subscriber->setEmail('e12@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setEngagementScore(50);
    $subscriber->setEmail('e123@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setEngagementScore(75);
    $subscriber->setEmail('e1234@example.com');
    $this->entityManager->persist($subscriber);

    $subscriber = new SubscriberEntity();
    $subscriber->setEngagementScore(100);
    $subscriber->setEmail('e12345@example.com');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();

    $subscriber = new SubscriberEntity();
    // Engagement score not set, should be NULL
    $subscriber->setEmail('e123456@example.com');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
  }

  public function testGetHigherThan(): void {
    $segmentFilter = $this->getSegmentFilter(SubscriberScore::HIGHER_THAN, '80');
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $this->assertIsArray($result[0]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e12345@example.com');
  }

  public function testGetLowerThan(): void {
    $segmentFilter = $this->getSegmentFilter(SubscriberScore::LOWER_THAN, '30');
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(2);

    $this->assertIsArray($result[0]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e1@example.com');

    $this->assertIsArray($result[1]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e12@example.com');
  }

  public function testGetEquals(): void {
    $segmentFilter = $this->getSegmentFilter(SubscriberScore::EQUALS, '50');
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $this->assertIsArray($result[0]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e123@example.com');
  }

  public function testGetNotEquals(): void {
    $segmentFilter = $this->getSegmentFilter(SubscriberScore::NOT_EQUALS, '50');
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(4);

    $this->assertIsArray($result[0]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e1@example.com');

    $this->assertIsArray($result[1]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e12@example.com');

    $this->assertIsArray($result[2]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[2]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e1234@example.com');

    $this->assertIsArray($result[3]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[3]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e12345@example.com');
  }

  public function testGetUnknown(): void {
    $segmentFilter = $this->getSegmentFilter(SubscriberScore::UNKNOWN, '');
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);

    $this->assertIsArray($result[0]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e123456@example.com');
  }

  public function testGetNotUnknown(): void {
    $segmentFilter = $this->getSegmentFilter(SubscriberScore::NOT_UNKNOWN, '');
    $statement = $this->filter->apply($this->getQueryBuilder(), $segmentFilter)
      ->orderBy('email')
      ->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(5);

    $this->assertIsArray($result[0]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e1@example.com');

    $this->assertIsArray($result[1]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e12@example.com');

    $this->assertIsArray($result[2]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[2]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e123@example.com');

    $this->assertIsArray($result[3]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[3]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getEmail())->equals('e1234@example.com');

    $this->assertIsArray($result[4]);
    $subscriber = $this->entityManager->find(SubscriberEntity::class, $result[4]['id']);
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
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberScore::TYPE, [
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
}
