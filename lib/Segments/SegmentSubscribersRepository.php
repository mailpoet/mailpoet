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

  public function getSubscribersStatisticsCount(SegmentEntity $segment) {
    $subscriberSegmentTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $queryBuilder = $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->from($subscriberSegmentTable, 'subscriber_segment')
      ->where('subscriber_segment.segment_id = :segment_id')
      ->setParameter('segment_id', $segment->getId())
      ->andWhere('subscribers.deleted_at is null')
      ->join('subscriber_segment', $subscribersTable, 'subscribers', 'subscribers.id = subscriber_segment.subscriber_id')
      ->addSelect('SUM(
          CASE WHEN subscribers.status = :status_subscribed AND subscriber_segment.status = :status_subscribed
            THEN 1 ELSE 0 END
      ) as :status_subscribed')
      ->addSelect('SUM(
        CASE WHEN subscribers.status = :status_unsubscribed OR subscriber_segment.status = :status_unsubscribed
          THEN 1 ELSE 0 END
      ) as :status_unsubscribed')
      ->addSelect('SUM(
        CASE WHEN subscribers.status = :status_inactive AND subscriber_segment.status != :status_unsubscribed
          THEN 1 ELSE 0 END
      ) as :status_inactive')
      ->addSelect('SUM(
        CASE WHEN subscribers.status = :status_unconfirmed  AND subscriber_segment.status != :status_unsubscribed
          THEN 1 ELSE 0 END
      ) as :status_unconfirmed')
      ->addSelect('SUM(
        CASE WHEN subscribers.status = :status_bounced AND subscriber_segment.status != :status_unsubscribed
          THEN 1 ELSE 0 END
      ) as :status_bounced')

      ->setParameter('status_subscribed', SubscriberEntity::STATUS_SUBSCRIBED)
      ->setParameter('status_unsubscribed', SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->setParameter('status_inactive', SubscriberEntity::STATUS_INACTIVE)
      ->setParameter('status_unconfirmed', SubscriberEntity::STATUS_UNCONFIRMED)
      ->setParameter('status_bounced', SubscriberEntity::STATUS_BOUNCED);

    $statement = $this->executeQuery($queryBuilder);
    $result = $statement->fetch();
    return $result;
  }
}
