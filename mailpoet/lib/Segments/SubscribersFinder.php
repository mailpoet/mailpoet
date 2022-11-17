<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\InvalidStateException;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SubscribersFinder {

  /** @var SegmentSubscribersRepository  */
  private $segmentSubscriberRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    SegmentSubscribersRepository $segmentSubscriberRepository,
    SegmentsRepository $segmentsRepository,
    EntityManager $entityManager
  ) {
    $this->segmentSubscriberRepository = $segmentSubscriberRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->entityManager = $entityManager;
  }

  public function findSubscribersInSegments($subscribersToProcessIds, $newsletterSegmentsIds) {
    $result = [];
    foreach ($newsletterSegmentsIds as $segmentId) {
      $segment = $this->segmentsRepository->findOneById($segmentId);
      if (!$segment instanceof SegmentEntity) {
        continue; // skip deleted segments
      }
      $result = array_merge($result, $this->findSubscribersInSegment($segment, $subscribersToProcessIds));
    }
    return $this->unique($result);
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
   * @return float|int
   */
  public function addSubscribersToTaskFromSegments(ScheduledTaskEntity $task, array $segmentIds) {
    // Prepare subscribers on the DB side for performance reasons
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
      $count += $this->addSubscribersToTaskFromStaticSegments($task, $staticSegmentIds);
    }
    if (!empty($dynamicSegmentIds)) {
      $count += $this->addSubscribersToTaskFromDynamicSegments($task, $dynamicSegmentIds);
    }
    return $count;
  }

  /**
   * @param ScheduledTaskEntity $task
   * @param array<int> $segmentIds
   *
   * @return int
   */
  private function addSubscribersToTaskFromStaticSegments(ScheduledTaskEntity $task, array $segmentIds) {
    $processedStatus = ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED;
    $subscribersStatus = SubscriberEntity::STATUS_SUBSCRIBED;
    $relationStatus = SubscriberEntity::STATUS_SUBSCRIBED;
    $scheduledTaskSubscriberTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $subscriberSegmentTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    $connection = $this->entityManager->getConnection();

    $result = $connection->executeQuery(
      "INSERT IGNORE INTO $scheduledTaskSubscriberTable
       (task_id, subscriber_id, processed)
       SELECT DISTINCT ? as task_id, subscribers.`id` as subscriber_id, ? as processed
       FROM $subscriberSegmentTable relation
       JOIN $subscriberTable subscribers ON subscribers.id = relation.subscriber_id
       WHERE subscribers.`deleted_at` IS NULL
       AND subscribers.`status` = ?
       AND relation.`status` = ?
       AND relation.`segment_id` IN (?)",
      [
        $task->getId(),
        $processedStatus,
        $subscribersStatus,
        $relationStatus,
        $segmentIds,
      ],
      [
        ParameterType::INTEGER,
        ParameterType::INTEGER,
        ParameterType::STRING,
        ParameterType::STRING,
        Connection::PARAM_INT_ARRAY,
      ]
    );

    return (int)$result->rowCount();
  }

  /**
   * @param ScheduledTaskEntity $task
   * @param array<int> $segmentIds
   *
   * @return int
   */
  private function addSubscribersToTaskFromDynamicSegments(ScheduledTaskEntity $task, array $segmentIds) {
    $count = 0;
    foreach ($segmentIds as $segmentId) {
      $count += $this->addSubscribersToTaskFromDynamicSegment($task, (int)$segmentId);
    }
    return $count;
  }

  private function addSubscribersToTaskFromDynamicSegment(ScheduledTaskEntity $task, int $segmentId) {
    $count = 0;
    $subscribers = $this->segmentSubscriberRepository->getSubscriberIdsInSegment($segmentId);
    if ($subscribers) {
      $count += $this->addSubscribersToTaskByIds($task, $subscribers);
    }
    return $count;
  }

  private function addSubscribersToTaskByIds(ScheduledTaskEntity $task, array $subscriberIds) {
    $scheduledTaskSubscriberTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    $connection = $this->entityManager->getConnection();

    $result = $connection->executeQuery(
      "INSERT IGNORE INTO $scheduledTaskSubscriberTable
       (task_id, subscriber_id, processed)
       SELECT DISTINCT ? as task_id, subscribers.`id` as subscriber_id, ? as processed
       FROM $subscriberTable subscribers
       WHERE subscribers.`deleted_at` IS NULL
       AND subscribers.`status` = ?
       AND subscribers.`id` IN (?)",
      [
        $task->getId(),
        ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
        SubscriberEntity::STATUS_SUBSCRIBED,
        $subscriberIds,
      ],
      [
        ParameterType::INTEGER,
        ParameterType::INTEGER,
        ParameterType::STRING,
        Connection::PARAM_INT_ARRAY,
      ]
    );

    return $result->rowCount();
  }

  private function unique(array $subscriberIds) {
    $result = [];
    foreach ($subscriberIds as $id) {
      $result[$id] = $id;
    }
    return $result;
  }
}
