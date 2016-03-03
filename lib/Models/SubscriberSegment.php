<?php
namespace MailPoet\Models;

use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SubscriberSegment extends Model {
  public static $_table = MP_SUBSCRIBER_SEGMENT_TABLE;

  function __construct() {
    parent::__construct();
  }

  static function setSubscriptions($subscriber, $segment_ids = array()) {
    if($subscriber->id > 0) {
      // unsubscribe from current subscriptions
      SubscriberSegment::where('subscriber_id', $subscriber->id)
        ->whereNotIn('segment_id', $segment_ids)
        ->findResultSet()
        ->set('status', Subscriber::STATUS_UNSUBSCRIBED)
        ->save();

      // subscribe to segments
      foreach($segment_ids as $segment_id) {
        self::createOrUpdate(array(
          'subscriber_id' => $subscriber->id,
          'segment_id' => $segment_id,
          'status' => Subscriber::STATUS_SUBSCRIBED
        ));
      }
    }

    return $subscriber;
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