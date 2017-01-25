<?php
namespace MailPoet\Models;


if(!defined('ABSPATH')) exit;

class SubscriberSegment extends Model {
  public static $_table = MP_SUBSCRIBER_SEGMENT_TABLE;

  function subscriber() {
    return $this->has_one(__NAMESPACE__.'\Subscriber', 'id', 'subscriber_id');
  }

  static function unsubscribeFromSegments($subscriber, $segment_ids = array()) {
    if($subscriber === false) return false;

    $wp_segment = Segment::getWPSegment();

    if(!empty($segment_ids)) {
      // unsubscribe from segments
      foreach($segment_ids as $segment_id) {

        // do not remove subscriptions to the WP Users segment
        if(
          $wp_segment !== false
          && ($wp_segment->id === (int)$segment_id)
        ) {
          continue;
        }

        if((int)$segment_id > 0) {
          self::createOrUpdate(array(
            'subscriber_id' => $subscriber->id,
            'segment_id' => $segment_id,
            'status' => Subscriber::STATUS_UNSUBSCRIBED
          ));
        }
      }
    } else {
      // unsubscribe from all segments (except the WP users segment)
      $subscriptions = self::where('subscriber_id', $subscriber->id);

      if($wp_segment !== false) {
        $subscriptions = $subscriptions->whereNotEqual(
          'segment_id', $wp_segment->id
        );
      }

      $subscriptions->findResultSet()
        ->set('status', Subscriber::STATUS_UNSUBSCRIBED)
        ->save();
    }
    return true;
  }

  static function resubscribeToAllSegments($subscriber) {
    if($subscriber === false) return false;
    // (re)subscribe to all segments linked to the subscriber
    return self::where('subscriber_id', $subscriber->id)
      ->findResultSet()
      ->set('status', Subscriber::STATUS_SUBSCRIBED)
      ->save();
  }

  static function subscribeToSegments($subscriber, $segment_ids = array()) {
    if($subscriber === false) return false;
    if(!empty($segment_ids)) {
      // subscribe to specified segments
      foreach($segment_ids as $segment_id) {
        if((int)$segment_id > 0) {
          self::createOrUpdate(array(
            'subscriber_id' => $subscriber->id,
            'segment_id' => $segment_id,
            'status' => Subscriber::STATUS_SUBSCRIBED
          ));
        }
      }
      return true;
    }
  }

  static function resetSubscriptions($subscriber, $segment_ids = array()) {
    self::unsubscribeFromSegments($subscriber);
    return self::subscribeToSegments($subscriber, $segment_ids);
  }

  static function subscribeManyToSegments(
    $subscriber_ids = array(),
    $segment_ids = array()
  ) {
    if(empty($subscriber_ids) || empty($segment_ids)) {
      return false;
    }

    // create many subscriptions to each segment
    $values = array();
    $row_count = 0;
    foreach($segment_ids as &$segment_id) {
      foreach($subscriber_ids as &$subscriber_id) {
        $values[] = (int)$subscriber_id;
        $values[] = (int)$segment_id;
        $row_count++;
      }
    }

    $query = array(
      'INSERT IGNORE INTO `'.self::$_table.'`',
      '(`subscriber_id`, `segment_id`)',
      'VALUES '.rtrim(str_repeat('(?, ?),', $row_count), ',')
    );
    self::rawExecute(join(' ', $query), $values);

    return true;
  }

  static function deleteManySubscriptions($subscriber_ids = array(), $segment_ids = array()) {
    if(empty($subscriber_ids)) return false;

    // delete subscribers' relations to segments (except WP segment)
    $subscriptions = self::whereIn(
      'subscriber_id', $subscriber_ids
    );

    $wp_segment = Segment::getWPSegment();
    if($wp_segment !== false) {
      $subscriptions = $subscriptions->whereNotEqual(
        'segment_id', $wp_segment->id
      );
    }

    if(!empty($segment_ids)) {
      $subscriptions = $subscriptions->whereIn('segment_id', $segment_ids);
    }

    return $subscriptions->deleteMany();
  }

  static function deleteSubscriptions($subscriber, $segment_ids = array()) {
    if($subscriber === false) return false;

    $wp_segment = Segment::getWPSegment();

    $subscriptions = self::where('subscriber_id', $subscriber->id)
      ->whereNotEqual('segment_id', $wp_segment->id);

    if(!empty($segment_ids)) {
      $subscriptions = $subscriptions->whereIn('segment_id', $segment_ids);
    }
    return $subscriptions->deleteMany();
  }

  static function subscribed($orm) {
    return $orm->where('status', Subscriber::STATUS_SUBSCRIBED);
  }

  static function createOrUpdate($data = array()) {
    $subscription = false;

    if(isset($data['id']) && (int)$data['id'] > 0) {
      $subscription = self::findOne((int)$data['id']);
    }

    if(isset($data['subscriber_id']) && isset($data['segment_id'])) {
      $subscription = self::where('subscriber_id', (int)$data['subscriber_id'])
        ->where('segment_id', (int)$data['segment_id'])
        ->findOne();
    }

    if($subscription === false) {
      $subscription = self::create();
      $subscription->hydrate($data);
    } else {
      unset($data['id']);
      $subscription->set($data);
    }

    return $subscription->save();
  }
}
