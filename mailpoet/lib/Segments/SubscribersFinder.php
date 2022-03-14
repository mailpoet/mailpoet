<?php

namespace MailPoet\Segments;

use MailPoet\Entities\SegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

class SubscribersFinder {

  /** @var SegmentSubscribersRepository  */
  private $segmentSubscriberRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct(
    SegmentSubscribersRepository $segmentSubscriberRepository,
    SegmentsRepository $segmentsRepository
  ) {
    $this->segmentSubscriberRepository = $segmentSubscriberRepository;
    $this->segmentsRepository = $segmentsRepository;
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
   * @param ScheduledTask $task
   * @param array<int>    $segmentIds
   *
   * @return float|int
   */
  public function addSubscribersToTaskFromSegments(ScheduledTask $task, array $segmentIds) {
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
   * @param ScheduledTask        $task
   * @param array<int> $segmentIds
   *
   * @return int
   */
  private function addSubscribersToTaskFromStaticSegments(ScheduledTask $task, array $segmentIds) {
    Subscriber::rawExecute(
      'INSERT IGNORE INTO ' . MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE . '
       (task_id, subscriber_id, processed)
       SELECT DISTINCT ? as task_id, subscribers.`id` as subscriber_id, ? as processed
       FROM ' . MP_SUBSCRIBER_SEGMENT_TABLE . ' relation
       JOIN ' . MP_SUBSCRIBERS_TABLE . ' subscribers ON subscribers.id = relation.subscriber_id
       WHERE subscribers.`deleted_at` IS NULL
       AND subscribers.`status` = ?
       AND relation.`status` = ?
       AND relation.`segment_id` IN (' . join(',', array_map('intval', $segmentIds)) . ')',
      [
        $task->id,
        ScheduledTaskSubscriber::STATUS_UNPROCESSED,
        Subscriber::STATUS_SUBSCRIBED,
        Subscriber::STATUS_SUBSCRIBED,
      ]
    );
    return ORM::getLastStatement()->rowCount();
  }

  /**
   * @param ScheduledTask        $task
   * @param array<int> $segmentIds
   *
   * @return int
   */
  private function addSubscribersToTaskFromDynamicSegments(ScheduledTask $task, array $segmentIds) {
    $count = 0;
    foreach ($segmentIds as $segmentId) {
      $count += $this->addSubscribersToTaskFromDynamicSegment($task, (int)$segmentId);
    }
    return $count;
  }

  private function addSubscribersToTaskFromDynamicSegment(ScheduledTask $task, int $segmentId) {
    $count = 0;
    $subscribers = $this->segmentSubscriberRepository->getSubscriberIdsInSegment($segmentId);
    if ($subscribers) {
      $count += $this->addSubscribersToTaskByIds($task, $subscribers);
    }
    return $count;
  }

  private function addSubscribersToTaskByIds(ScheduledTask $task, array $subscriberIds) {
    Subscriber::rawExecute(
      'INSERT IGNORE INTO ' . MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE . '
       (task_id, subscriber_id, processed)
       SELECT DISTINCT ? as task_id, subscribers.`id` as subscriber_id, ? as processed
       FROM ' . MP_SUBSCRIBERS_TABLE . ' subscribers
       WHERE subscribers.`deleted_at` IS NULL
       AND subscribers.`status` = ?
       AND subscribers.`id` IN (' . join(',', array_map('intval', $subscriberIds)) . ')',
      [
        $task->id,
        ScheduledTaskSubscriber::STATUS_UNPROCESSED,
        Subscriber::STATUS_SUBSCRIBED,
      ]
    );
    return ORM::getLastStatement()->rowCount();
  }

  private function unique(array $subscriberIds) {
    $result = [];
    foreach ($subscriberIds as $id) {
      $result[$id] = $id;
    }
    return $result;
  }
}
