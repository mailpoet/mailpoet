<?php

namespace MailPoet\Segments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
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
use MailPoetVendor\Doctrine\ORM\Query\Expr\Join;
use MailPoetVendor\Doctrine\ORM\QueryBuilder as ORMQueryBuilder;

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
    $queryBuilder = $this->createCountQueryBuilder();

    if ($segment->isStatic()) {
      $queryBuilder = $this->filterSubscribersInStaticSegment($queryBuilder, $segment, $status);
    } else {
      $queryBuilder = $this->filterSubscribersInDynamicSegment($queryBuilder, $segment, $status);
    }
    $statement = $this->executeQuery($queryBuilder);
    $result = $statement->fetchColumn();
    return (int)$result;
  }

  public function getSubscribersCountBySegmentIds(array $segmentIds, string $status = null): int {
    $segmentRepository = $this->entityManager->getRepository(SegmentEntity::class);
    $segments = $segmentRepository->findBy(['id' => $segmentIds]);
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $queryBuilder = $this->createCountQueryBuilder();

    $subQueries = [];
    foreach ($segments as $segment) {
      $segmentQb = $this->createCountQueryBuilder();
      $segmentQb->select("{$subscribersTable}.id AS inner_id");

      if ($segment->isStatic()) {
        $segmentQb = $this->filterSubscribersInStaticSegment($segmentQb, $segment, $status);
      } else {
        $segmentQb = $this->filterSubscribersInDynamicSegment($segmentQb, $segment, $status);
      }

      // inner parameters have to be merged to outer queryBuilder
      $queryBuilder->setParameters(array_merge(
        $segmentQb->getParameters(),
        $queryBuilder->getParameters()
      ));
      $subQueries[] = $segmentQb->getSQL();
    }

    $queryBuilder->innerJoin(
      $subscribersTable,
      sprintf('(%s)', join(' UNION ', $subQueries)),
      'inner_subscribers',
      "inner_subscribers.inner_id = {$subscribersTable}.id"
    );

    $statement = $this->executeQuery($queryBuilder);
    $result = $statement->fetchColumn();
    return (int)$result;
  }

  /**
   * @param DynamicSegmentFilterData[] $filters
   * @return int
   * @throws InvalidStateException
   */
  public function getDynamicSubscribersCount(array $filters): int {
    $segment = new SegmentEntity('temporary segment', SegmentEntity::TYPE_DYNAMIC, '');
    foreach ($filters as $filter) {
      $segment->addDynamicFilter(new DynamicSegmentFilterEntity($segment, $filter));
    }
    $queryBuilder = $this->createCountQueryBuilder();
    $queryBuilder = $this->filterSubscribersInDynamicSegment($queryBuilder, $segment, null);
    $statement = $this->executeQuery($queryBuilder);
    $result = $statement->fetchColumn();
    return (int)$result;
  }

  private function createCountQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("count(DISTINCT $subscribersTable.id)")
      ->from($subscribersTable);
  }

  public function getSubscribersWithoutSegmentCount(): int {
    $queryBuilder = $this->getSubscribersWithoutSegmentCountQuery();
    return (int)$queryBuilder->getQuery()->getSingleScalarResult();
  }

  public function getSubscribersWithoutSegmentCountQuery(): ORMQueryBuilder {
    $queryBuilder = $this->entityManager->createQueryBuilder();
    $queryBuilder
      ->select('COUNT(DISTINCT s) AS subscribersCount')
      ->from(SubscriberEntity::class, 's');
    $this->addConstraintsForSubscribersWithoutSegment($queryBuilder);
    return $queryBuilder;
  }

  public function addConstraintsForSubscribersWithoutSegment(ORMQueryBuilder $queryBuilder): void {
    $deletedSegmentsQueryBuilder = $this->entityManager->createQueryBuilder();
    $deletedSegmentsQueryBuilder->select('sg.id')
      ->from(SegmentEntity::class, 'sg')
      ->where($deletedSegmentsQueryBuilder->expr()->isNotNull('sg.deletedAt'));

    $queryBuilder
      ->leftJoin('s.subscriberSegments', 'ssg', Join::WITH,
        (string)$queryBuilder->expr()->andX(
          $queryBuilder->expr()->eq('ssg.subscriber', 's.id'),
          $queryBuilder->expr()->eq('ssg.status', ':statusSubscribed'),
          $queryBuilder->expr()->notIn('ssg.segment', $deletedSegmentsQueryBuilder->getDQL())
        ))
      ->andWhere('s.deletedAt IS NULL')
      ->andWhere('ssg.id IS NULL')
      ->setParameter('statusSubscribed', SubscriberEntity::STATUS_SUBSCRIBED);
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
    $parameterName = "segment_{$segment->getId()}"; // When we use this method more times the parameter name has to be unique
    $queryBuilder = $queryBuilder->join(
      $subscribersTable,
      $subscribersSegmentsTable,
      'subsegment',
      "subsegment.subscriber_id = $subscribersTable.id AND subsegment.segment_id = :$parameterName"
    )->andWhere("$subscribersTable.deleted_at IS NULL")
      ->setParameter($parameterName, $segment->getId());
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
    $filters = [];
    $dynamicFilters = $segment->getDynamicFilters();
    foreach ($dynamicFilters as $dynamicFilter) {
      $filters[] = $dynamicFilter->getFilterData();
    }

    // We don't allow dynamic segment without filers since it would return all subscribers
    // For BC compatibility fetching an empty result
    if (count($filters) === 0) {
      return $queryBuilder->andWhere('0 = 1');
    } elseif ($segment instanceof SegmentEntity) {
      $queryBuilder = $this->filterHandler->apply($queryBuilder, $segment);
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
