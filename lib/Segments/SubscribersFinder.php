<?php

namespace MailPoet\Segments;

use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
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
    if($segment['type'] === Segment::TYPE_DEFAULT || $segment['type'] === Segment::TYPE_WP_USERS) {
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
    if($segment['type'] === Segment::TYPE_DEFAULT || $segment['type'] === Segment::TYPE_WP_USERS) {
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