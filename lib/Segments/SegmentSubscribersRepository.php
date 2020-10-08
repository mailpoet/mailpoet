<?php

namespace MailPoet\Segments;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\NotFoundException;
use MailPoet\Segments\DynamicSegments\FilterHandler;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

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

  public function findSubscribersIdsInSegment(int $segmentId, array $candidateIds = null): array {
    return $this->loadSubscriberIdsInSegment($segmentId, $candidateIds);
  }

  public function getSubscriberIdsInSegment(int $segmentId): array {
    return $this->loadSubscriberIdsInSegment($segmentId);
  }

  public function getSubscribersCount(int $segmentId, string $status = null): int {
    $segment = $this->getSegment($segmentId);
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $queryBuilder = $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("count(DISTINCT $subscribersTable.id)")
      ->from($subscribersTable);

    if ($segment->isStatic()) {
      $queryBuilder = $this->filterSubscribersInStaticSegment($queryBuilder, $segment, $status);
    } else {
      $queryBuilder = $this->filterSubscribersInDynamicSegment($queryBuilder, $segment, $status);
    }
    $statement = $this->executeQuery($queryBuilder);
    $result = $statement->fetchColumn();
    return (int)$result;
  }

  private function loadSubscriberIdsInSegment(int $segmentId, array $candidateIds = null): array {
    $segment = $this->getSegment($segmentId);
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $queryBuilder = $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("DISTINCT $subscribersTable.id")
      ->from($subscribersTable);

    if ($segment->isStatic()) {
      $queryBuilder = $this->filterSubscribersInStaticSegment($queryBuilder, $segment, SubscriberEntity::STATUS_SUBSCRIBED);
    } else {
      $queryBuilder = $this->filterSubscribersInDynamicSegment($queryBuilder, $segment, SubscriberEntity::STATUS_SUBSCRIBED);
    }

    if ($candidateIds) {
      $queryBuilder->andWhere("$subscribersTable.id IN (:candidateIds)")
        ->setParameter('candidateIds', $candidateIds, Connection::PARAM_STR_ARRAY);
    }

    $statement = $this->executeQuery($queryBuilder);
    $result = $statement->fetchAll();
    return array_column($result, 'id');
  }

  private function filterSubscribersInStaticSegment(
    QueryBuilder $queryBuilder,
    SegmentEntity $segment,
    string $status = null
  ): QueryBuilder {
    $subscribersSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $queryBuilder = $queryBuilder->join(
      $subscribersTable,
      $subscribersSegmentsTable,
      'subsegment',
      "subsegment.subscriber_id = $subscribersTable.id AND subsegment.segment_id = :segment"
    )->andWhere("$subscribersTable.deleted_at IS NULL")
      ->setParameter('segment', $segment->getId());
    if ($status) {
      $queryBuilder = $queryBuilder->andWhere("$subscribersTable.status = :status")
        ->andWhere("subsegment.status = :status")
        ->setParameter('status', $status);
    }
    return $queryBuilder;
  }

  private function filterSubscribersInDynamicSegment(
    QueryBuilder $queryBuilder,
    SegmentEntity $segment,
    string $status = null
  ): QueryBuilder {
    $filters = $segment->getDynamicFilters();
    // We don't allow dynamic segment without filers since it would return all subscribers
    // For BC compatibility fetching an empty result
    if (count($filters) === 0) {
      return $queryBuilder->andWhere('0 = 1');
    }
    foreach ($filters as $filter) {
      $queryBuilder = $this->filterHandler->apply($queryBuilder, $filter);
    }
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $queryBuilder = $queryBuilder->andWhere("$subscribersTable.deleted_at IS NULL");
    if ($status) {
      $queryBuilder = $queryBuilder->andWhere("$subscribersTable.status = :status")
        ->setParameter('status', $status);
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
