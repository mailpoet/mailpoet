<?php
namespace MailPoet\Models;

use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SubscriberSegment extends Model {
  public static $_table = MP_SUBSCRIBER_SEGMENT_TABLE;

  function __construct() {
    parent::__construct();
  }

  function subscriber() {
    return $this->has_one(__NAMESPACE__.'\Subscriber', 'id', 'subscriber_id');
  }

  static function unsubscribeFromSegments($subscriber, $segment_ids = array()) {
    if($subscriber !== false && $subscriber->id > 0) {

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
    return false;
  }

  static function subscribeToSegments($subscriber, $segment_ids = array()) {
    if($subscriber->id > 0) {
      if(!empty($segment_ids)) {
        // subscribe to segments
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
      } else {
        // subscribe to all segments
        return self::where('subscriber_id', $subscriber->id)
          ->findResultSet()
          ->set('status', Subscriber::STATUS_SUBSCRIBED)
          ->save();
      }
    }
    return false;
  }

  static function resetSubscriptions($subscriber, $segment_ids = array()) {
    self::unsubscribeFromSegments($subscriber);
    return self::subscribeToSegments($subscriber, $segment_ids);
  }

  static function deleteManySubscriptions($subscriber_ids = array()) {
    if(!empty($subscriber_ids)) {
      // delete subscribers' relations to segments (except WP Users' segment)
      $subscriptions = SubscriberSegment::whereIn(
        'subscriber_id', $subscriber_ids
      );

      $wp_segment = Segment::getWPSegment();
      if($wp_segment !== false) {
        $subscriptions = $subscriptions->whereNotEqual(
          'segment_id', $wp_segment->id
        );
      }
      return $subscriptions->deleteMany();
    }
    return false;
  }

  static function deleteSubscriptions($subscriber) {
    if($subscriber !== false && $subscriber->id > 0) {
      // delete all relationships to segments
      return self::where('subscriber_id', $subscriber->id)->deleteMany();
    }
    return false;
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

  // TO BE REVIEWED
  static function createMultiple($segmnets, $subscribers) {
    $values = Helpers::flattenArray(
      array_map(function ($segment) use ($subscribers) {
        return array_map(function ($subscriber) use ($segment) {
          return array(
            $segment,
            $subscriber
          );
        }, $subscribers);
      }, $segmnets)
    );
    return self::rawExecute(
      'INSERT IGNORE INTO `' . self::$_table . '` ' .
      '(segment_id, subscriber_id) ' .
      'VALUES ' . rtrim(
        str_repeat(
          '(?, ?), ', count($subscribers) * count($segmnets)), ', '
      ),
      $values
    );
  }
}