<?php

namespace MailPoet\Segments;

use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Util\Helpers;
use MailPoet\WP\Hooks;

class SubscribersFinder {

  function findSubscribersInSegments($subscribers_to_process_ids, $newsletter_segments_ids) {
    $result = array();
    foreach($newsletter_segments_ids as $segment_id) {
      $segment = Segment::find_one($segment_id)->asArray();
      $result = array_merge($result, $this->findSubscribersInSegment($segment, $subscribers_to_process_ids));
    }
    return $this->unique($result);
  }

  private function findSubscribersInSegment($segment, $subscribers_to_process_ids) {
    if($this->isStaticSegment($segment)) {
      $subscribers = Subscriber::findSubscribersInSegments($subscribers_to_process_ids, array($segment['id']))->findMany();
      return Subscriber::extractSubscribersIds($subscribers);
    }
    $finders = Hooks::applyFilters('mailpoet_get_subscribers_in_segment_finders', array());
    foreach($finders as $finder) {
      $subscribers = $finder->findSubscribersInSegment($segment, $subscribers_to_process_ids);
      if($subscribers) {
        return Subscriber::extractSubscribersIds($subscribers);
      }
    }
    return array();
  }

  function getSubscribersByList($segments) {
    $result = array();
    foreach($segments as $segment) {
      $result = array_merge($result, $this->getSubscribers($segment));
    }
    return $this->unique($result);
  }

  private function getSubscribers($segment) {
    if($this->isStaticSegment($segment)) {
      return Subscriber::getSubscribedInSegments(array($segment['id']))->findArray();
    }
    $finders = Hooks::applyFilters('mailpoet_get_subscribers_in_segment_finders', array());
    foreach($finders as $finder) {
      $subscribers = $finder->getSubscriberIdsInSegment($segment);
      if($subscribers) {
        return $subscribers;
      }
    }
    return array();
  }

  private function isStaticSegment($segment) {
    return $segment['type'] === Segment::TYPE_DEFAULT || $segment['type'] === Segment::TYPE_WP_USERS;
  }

  function addSubscribersToTaskFromSegments(ScheduledTask $task, array $segments) {
    // Prepare subscribers on the DB side for performance reasons
    $staticSegments = array();
    $dynamicSegments = array();
    foreach($segments as $segment) {
      if($this->isStaticSegment($segment)) {
        $staticSegments[] = $segment;
      } else {
        $dynamicSegments[] = $segment;
      }
    }
    $count = 0;
    if(!empty($staticSegments)) {
      $count += $this->addSubscribersToTaskFromStaticSegments($task, $staticSegments);
    }
    if(!empty($dynamicSegments)) {
      $count += $this->addSubscribersToTaskFromDynamicSegments($task, $dynamicSegments);
    }
    return $count;
  }

  private function addSubscribersToTaskFromStaticSegments(ScheduledTask $task, array $segments) {
    $segment_ids = Helpers::arrayColumn($segments, 'id');
    Subscriber::rawExecute(
      'INSERT IGNORE INTO ' . MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE . '
       (task_id, subscriber_id, processed)
       SELECT DISTINCT ? as task_id, subscribers.`id` as subscriber_id, ? as processed
       FROM ' . MP_SUBSCRIBER_SEGMENT_TABLE . ' relation
       JOIN ' . MP_SUBSCRIBERS_TABLE . ' subscribers ON subscribers.id = relation.subscriber_id
       WHERE subscribers.`deleted_at` IS NULL
       AND subscribers.`status` = ?
       AND relation.`status` = ?
       AND relation.`segment_id` IN (' . join(',', array_map('intval', $segment_ids)) . ')',
      array(
        $task->id,
        ScheduledTaskSubscriber::STATUS_UNPROCESSED,
        Subscriber::STATUS_SUBSCRIBED,
        Subscriber::STATUS_SUBSCRIBED
      )
    );
    return \ORM::getLastStatement()->rowCount();
  }

  private function addSubscribersToTaskFromDynamicSegments(ScheduledTask $task, array $segments) {
    $count = 0;
    foreach($segments as $segment) {
      $count += $this->addSubscribersToTaskFromDynamicSegment($task, $segment);
    }
    return $count;
  }

  private function addSubscribersToTaskFromDynamicSegment(ScheduledTask $task, $segment) {
    $finders = Hooks::applyFilters('mailpoet_get_subscribers_in_segment_finders', array());
    $count = 0;
    foreach($finders as $finder) {
      $subscribers = $finder->getSubscriberIdsInSegment($segment);
      if($subscribers) {
        $count += $this->addSubscribersToTaskByIds($task, $subscribers);
      }
    }
    return $count;
  }

  private function addSubscribersToTaskByIds(ScheduledTask $task, array $subscribers) {
    $subscribers = Helpers::arrayColumn($subscribers, 'id');
    Subscriber::rawExecute(
      'INSERT IGNORE INTO ' . MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE . '
       (task_id, subscriber_id, processed)
       SELECT DISTINCT ? as task_id, subscribers.`id` as subscriber_id, ? as processed
       FROM ' . MP_SUBSCRIBERS_TABLE . ' subscribers
       WHERE subscribers.`deleted_at` IS NULL
       AND subscribers.`status` = ?
       AND subscribers.`id` IN (' . join(',', array_map('intval', $subscribers)) . ')',
      array(
        $task->id,
        ScheduledTaskSubscriber::STATUS_UNPROCESSED,
        Subscriber::STATUS_SUBSCRIBED
      )
    );
    return \ORM::getLastStatement()->rowCount();
  }

  private function unique($subscribers) {
    $result = array();
    foreach($subscribers as $subscriber) {
      if(is_a($subscriber, 'MailPoet\Models\Model')) {
        $result[$subscriber->id] = $subscriber;
      } elseif(is_scalar($subscriber)) {
        $result[$subscriber] = $subscriber;
      } else {
        $result[$subscriber['id']] = $subscriber;
      }
    }
    return $result;
  }

}