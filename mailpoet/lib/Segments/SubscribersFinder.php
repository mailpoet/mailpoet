<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SubscribersFinder {

  /** @var SegmentSubscribersRepository  */
  private $segmentSubscriberRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var EntityManager */
  private $entityManager;

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  public function __construct(
    SegmentSubscribersRepository $segmentSubscriberRepository,
    SegmentsRepository $segmentsRepository,
    EntityManager $entityManager,
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository
  ) {
    $this->segmentSubscriberRepository = $segmentSubscriberRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->entityManager = $entityManager;
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
  }

  /**
   * @return array
   * @throws InvalidStateException
   */
  public function findSubscribersInSegments($subscribersToProcessIds, $newsletterSegmentsIds, ?int $filterSegmentId = null) {
    $result = [];
    foreach ($newsletterSegmentsIds as $segmentId) {
      $segment = $this->segmentsRepository->findOneById($segmentId);
      if (!$segment instanceof SegmentEntity) {
        continue; // skip deleted segments
      }
      $result = array_merge($result, $this->findSubscribersInSegment($segment, $subscribersToProcessIds));
    }

    if (is_int($filterSegmentId)) {
      $filterSegment = $this->segmentsRepository->verifyDynamicSegmentExists($filterSegmentId);
      $idsInFilterSegment = $this->findSubscribersInSegment($filterSegment, $subscribersToProcessIds);
      $result = array_intersect($result, $idsInFilterSegment);
    }

    return $this->unique($result);
  }

  /**
   * @param int[] $newsletterSegmentsIds
   * @return int[]
   * @throws InvalidStateException
   */
  public function getSubscriberIdsFromSegments(array $newsletterSegmentsIds, ?int $filterSegmentId = null): array {
    $result = [];
    foreach ($newsletterSegmentsIds as $segmentId) {
      $segment = $this->segmentsRepository->findOneById($segmentId);
      if (!$segment instanceof SegmentEntity) {
        continue;
      }
      try {
        $result = array_merge($result, $this->segmentSubscriberRepository->getSubscriberIdsInSegment((int)$segment->getId()));
      } catch (InvalidStateException $exception) {
        continue;
      }
    }

    if (is_int($filterSegmentId)) {
      $filterSegment = $this->segmentsRepository->verifyDynamicSegmentExists($filterSegmentId);
      $filterSubscriberIds = $this->segmentSubscriberRepository->getSubscriberIdsInSegment((int)$filterSegment->getId());
      $result = array_intersect($result, $filterSubscriberIds);
    }

    return array_values($this->unique($result));
  }

  private function findSubscribersInSegment(SegmentEntity $segment, $subscribersToProcessIds): array {
    try {
      return $this->segmentSubscriberRepository->findSubscribersIdsInSegment((int)$segment->getId(), $subscribersToProcessIds);
    } catch (InvalidStateException $e) {
      return [];
    }
  }

  /**
   * @param ScheduledTaskEntity $task
   * @param array<int>    $segmentIds
   *
   * @return void
   */
  public function addSubscribersToTaskFromSegments(ScheduledTaskEntity $task, array $segmentIds, ?int $filterSegmentId = null): void {
    // Prepare subscribers on the DB side for performance reasons
    if (is_int($filterSegmentId)) {
      try {
        $this->segmentsRepository->verifyDynamicSegmentExists($filterSegmentId);
      } catch (InvalidStateException $exception) {
        return;
      }
    }
    $staticSegmentIds = [];
    $dynamicSegmentIds = [];
    foreach ($segmentIds as $segment) {
      $segment = $this->segmentsRepository->findOneById($segment);
      if ($segment instanceof SegmentEntity) {
        if ($segment->isStatic()) {
          $staticSegmentIds[] = (int)$segment->getId();
        } elseif ($segment->getType() === SegmentEntity::TYPE_DYNAMIC) {
          $dynamicSegmentIds[] = (int)$segment->getId();
        }
      }
    }
    $count = 0;
    if (!empty($staticSegmentIds)) {
      $count += $this->addSubscribersToTaskFromStaticSegments($task, $staticSegmentIds, $filterSegmentId);
    }
    if (!empty($dynamicSegmentIds)) {
      $count += $this->addSubscribersToTaskFromDynamicSegments($task, $dynamicSegmentIds, $filterSegmentId);
    }
    if ($count > 0) {
      $this->entityManager->refresh($task);
    }
  }

  /**
   * @param ScheduledTaskEntity $task
   * @param array<int> $segmentIds
   *
   * @return int
   */
  private function addSubscribersToTaskFromStaticSegments(ScheduledTaskEntity $task, array $segmentIds, ?int $filterSegmentId = null) {
    $scheduledTaskSubscriberTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $subscriberSegmentTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    $connection = $this->entityManager->getConnection();
    $selectQueryBuilder = $connection->createQueryBuilder();
    $selectQueryBuilder
      // No DISTINCT needed: the INSERT IGNORE relies on the (task_id, subscriber_id)
      // primary key of scheduled_task_subscribers to drop duplicates, and DISTINCT
      // forces an expensive temporary table on large segments.
      ->select(':task_id as task_id', 'subscribers.id as subscriber_id', ':processed as processed')
      ->from($subscriberSegmentTable, 'relation')
      ->join('relation', $subscriberTable, 'subscribers', 'subscribers.id = relation.subscriber_id')
      ->where('subscribers.deleted_at IS NULL')
      ->andWhere('subscribers.status = :subscribers_status')
      ->andWhere('relation.status = :relation_status')
      ->andWhere($selectQueryBuilder->expr()->in('relation.segment_id', ':segment_ids'))
      ->setParameter('task_id', $task->getId(), ParameterType::INTEGER)
      ->setParameter('processed', ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED, ParameterType::INTEGER)
      ->setParameter('subscribers_status', SubscriberEntity::STATUS_SUBSCRIBED, ParameterType::STRING)
      ->setParameter('relation_status', SubscriberEntity::STATUS_SUBSCRIBED, ParameterType::STRING)
      ->setParameter('segment_ids', $segmentIds, ArrayParameterType::INTEGER);

    if ($filterSegmentId) {
      $filterSegmentSubscriberIds = $this->segmentSubscriberRepository->findSubscribersIdsInSegment($filterSegmentId);
      $selectQueryBuilder
        ->andWhere($selectQueryBuilder->expr()->in('subscribers.id', ':filterSegmentSubscriberIds'))
        ->setParameter('filterSegmentSubscriberIds', $filterSegmentSubscriberIds, ArrayParameterType::INTEGER);
    }

    // queryBuilder doesn't support INSERT IGNORE directly
    $sql = "INSERT IGNORE INTO $scheduledTaskSubscriberTable (task_id, subscriber_id, processed) " . $selectQueryBuilder->getSQL();
    $result = $connection->executeQuery($sql, $selectQueryBuilder->getParameters(), $selectQueryBuilder->getParameterTypes());

    return (int)$result->rowCount();
  }

  /**
   * @param ScheduledTaskEntity $task
   * @param array<int> $segmentIds
   *
   * @return int
   */
  private function addSubscribersToTaskFromDynamicSegments(ScheduledTaskEntity $task, array $segmentIds, ?int $filterSegmentId = null) {
    $count = 0;
    foreach ($segmentIds as $segmentId) {
      $count += $this->addSubscribersToTaskFromDynamicSegment($task, (int)$segmentId, $filterSegmentId);
    }
    return $count;
  }

  private function addSubscribersToTaskFromDynamicSegment(ScheduledTaskEntity $task, int $segmentId, ?int $filterSegmentId) {
    $count = 0;
    $subscriberIds = $this->segmentSubscriberRepository->getSubscriberIdsInSegment($segmentId);

    if ($filterSegmentId) {
      $filterSegmentSubscriberIds = $this->segmentSubscriberRepository->getSubscriberIdsInSegment($filterSegmentId);
      $subscriberIds = array_intersect($subscriberIds, $filterSegmentSubscriberIds);
    }

    if ($subscriberIds) {
      $count += $this->scheduledTaskSubscribersRepository->addSubscribersByIds($task, $subscriberIds);
    }
    return $count;
  }

  private function unique(array $subscriberIds) {
    $result = [];
    foreach ($subscriberIds as $id) {
      $result[$id] = $id;
    }
    return $result;
  }
}
