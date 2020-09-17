<?php

namespace MailPoet\Segments;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\NotFoundException;
use MailPoet\Segments\DynamicSegments\FilterHandler;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use function \MailPoetVendor\array_column;

class SegmentSubscribersRepository {
  /** @var EntityManager */
  private $entityManager;

  /** @var FilterHandler */
  private $filterHandler;

  public function __construct(
    EntityManager $entityManager,
    FilterHandler $filterHandler
  ) {
    $this->entityManager = $entityManager;
    $this->filterHandler = $filterHandler;
  }

  public function getSubscriberIdsInSegment(int $segmentId): array {
    $segment = $this->getSegment($segmentId);
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $queryBuilder = $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("DISTINCT $subscribersTable.id")
      ->from($subscribersTable);

    if ($segment->isDynamic()) {
      $queryBuilder = $this->filterSubscribersInDynamicSegment($queryBuilder, $segment);
    } else {
      $queryBuilder = $this->filterSubscribersInStaticSegment($queryBuilder, $segment);
    }
    $statement = $this->executeQuery($queryBuilder);
    $result = $statement->fetchAll();
    return array_column($result, 'id');
  }

  public function getSubscribersCount(int $segmentId): int {
    $segment = $this->getSegment($segmentId);
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $queryBuilder = $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("DISTINCT count($subscribersTable.id)")
      ->from($subscribersTable);

    if ($segment->isDynamic()) {
      $queryBuilder = $this->filterSubscribersInDynamicSegment($queryBuilder, $segment);
    } else {
      $queryBuilder = $this->filterSubscribersInStaticSegment($queryBuilder, $segment);
    }
    $statement = $this->executeQuery($queryBuilder);
    $result = $statement->fetchColumn();
    return (int)$result;
  }

  private function filterSubscribersInStaticSegment(QueryBuilder $queryBuilder, SegmentEntity $segment): QueryBuilder {
    $subscribersSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $queryBuilder->join(
      $subscribersTable,
      $subscribersSegmentsTable,
      'subsegment',
      "subsegment.subscriber_id = $subscribersTable.id AND subsegment.segment_id = :segment"
    )->andWhere("$subscribersTable.deleted_at IS NULL")
      ->andWhere("$subscribersTable.status = :status")
      ->setParameter('segment', $segment->getId())
      ->setParameter('status', SubscriberEntity::STATUS_SUBSCRIBED);
  }

  private function filterSubscribersInDynamicSegment(QueryBuilder $queryBuilder, SegmentEntity $segment): QueryBuilder {
    $filters = $segment->getDynamicFilters();
    // We don't allow dynamic segment without filers since it would return all subscribers
    if (count($filters) === 0) {
      throw new InvalidStateException('Missing filters for dynamic segment.');
    }
    foreach ($filters as $filter) {
      $queryBuilder = $this->filterHandler->apply($queryBuilder, $filter);
    }
    return $queryBuilder;
  }

  private function getSegment(int $id): SegmentEntity {
    $segment = $this->entityManager->find(SegmentEntity::class, $id);
    if (!$segment instanceof SegmentEntity) {
      throw new NotFoundException('Segment not found');
    }
    return $segment;
  }

  private function executeQuery(QueryBuilder $queryBuilder): Statement {
    $statement = $queryBuilder->execute();
    // Execute for select always returns statement but PHP Stan doesn't know that :(
    if (!$statement instanceof Statement) {
      throw new InvalidStateException('Invalid query.');
    }
    return $statement;
  }
}
