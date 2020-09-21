<?php

namespace MailPoet\Segments;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\InvalidStateException;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

class SubscribersFinder {

  /** @var SegmentSubscribersRepository  */
  private $segmentSubscriberRepository;

  public function __construct(
    SegmentSubscribersRepository $segmentSubscriberRepository = null
  ) {
    if ($segmentSubscriberRepository === null) {
      $segmentSubscriberRepository = ContainerWrapper::getInstance()->get(SegmentSubscribersRepository::class);
    }
    $this->segmentSubscriberRepository = $segmentSubscriberRepository;
  }

  public function findSubscribersInSegments($subscribersToProcessIds, $newsletterSegmentsIds) {
    $result = [];
    foreach ($newsletterSegmentsIds as $segmentId) {
      $segment = Segment::findOne($segmentId);
      if (!$segment instanceof Segment) {
        continue; // skip deleted segments
      }
      $result = array_merge($result, $this->findSubscribersInSegment($segment, $subscribersToProcessIds));
    }
    return $this->unique($result);
  }

  private function findSubscribersInSegment(Segment $segment, $subscribersToProcessIds): array {
    try {
      return $this->segmentSubscriberRepository->findSubscribersIdsInSegment((int)$segment->id, $subscribersToProcessIds);
    } catch (InvalidStateException $e) {
      return [];
    }
  }

  private function isStaticSegment(Segment $segment) {
    return in_array($segment->type, [Segment::TYPE_DEFAULT, Segment::TYPE_WP_USERS, Segment::TYPE_WC_USERS], true);
  }

  public function addSubscribersToTaskFromSegments(ScheduledTask $task, array $segments) {
    // Prepare subscribers on the DB side for performance reasons
    $staticSegments = [];
    $dynamicSegments = [];
    foreach ($segments as $segment) {
      if ($this->isStaticSegment($segment)) {
        $staticSegments[] = $segment;
      } elseif ($segment->type === SegmentEntity::TYPE_DYNAMIC) {
        $dynamicSegments[] = $segment;
      }
    }
    $count = 0;
    if (!empty($staticSegments)) {
      $count += $this->addSubscribersToTaskFromStaticSegments($task, $staticSegments);
    }
    if (!empty($dynamicSegments)) {
      $count += $this->addSubscribersToTaskFromDynamicSegments($task, $dynamicSegments);
    }
    return $count;
  }

  private function addSubscribersToTaskFromStaticSegments(ScheduledTask $task, array $segments) {
    $segmentIds = array_map(function($segment) {
      return $segment->id;
    }, $segments);
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

  private function addSubscribersToTaskFromDynamicSegments(ScheduledTask $task, array $segments) {
    $count = 0;
    foreach ($segments as $segment) {
      $count += $this->addSubscribersToTaskFromDynamicSegment($task, $segment);
    }
    return $count;
  }

  private function addSubscribersToTaskFromDynamicSegment(ScheduledTask $task, Segment $segment) {
    $count = 0;
    $subscribers = $this->segmentSubscriberRepository->getSubscriberIdsInSegment((int)$segment->id);
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
