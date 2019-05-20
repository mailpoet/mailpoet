<?php
namespace MailPoet\Models;


if (!defined('ABSPATH')) exit;

/**
 * @property int $id
 * @property int $subscriber_id
 * @property int $segment_id
 * @property string $status
 */

class SubscriberSegment extends Model {
  public static $_table = MP_SUBSCRIBER_SEGMENT_TABLE;

  function subscriber() {
    return $this->has_one(__NAMESPACE__ . '\Subscriber', 'id', 'subscriber_id');
  }

  static function unsubscribeFromSegments($subscriber, $segment_ids = []) {
    if ($subscriber === false) return false;

    // Reset confirmation emails count, so user can resubscribe
    $subscriber->count_confirmations = 0;
    $subscriber->save();

    $wp_segment = Segment::getWPSegment();

    if (!empty($segment_ids)) {
      // unsubscribe from segments
      foreach ($segment_ids as $segment_id) {

        // do not remove subscriptions to the WP Users segment
        if ($wp_segment !== false && (int)$wp_segment->id === (int)$segment_id) {
          continue;
        }

        if ((int)$segment_id > 0) {
          self::createOrUpdate([
            'subscriber_id' => $subscriber->id,
            'segment_id' => $segment_id,
            'status' => Subscriber::STATUS_UNSUBSCRIBED,
          ]);
        }
      }
    } else {
      // unsubscribe from all segments (except the WP users and WooCommerce customers segments)
      $subscriptions = self::where('subscriber_id', $subscriber->id);

      if ($wp_segment !== false) {
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
    if ($subscriber === false) return false;
    // (re)subscribe to all segments linked to the subscriber
    return self::where('subscriber_id', $subscriber->id)
      ->findResultSet()
      ->set('status', Subscriber::STATUS_SUBSCRIBED)
      ->save();
  }

  static function subscribeToSegments($subscriber, $segment_ids = []) {
    if ($subscriber === false) return false;
    if (!empty($segment_ids)) {
      // subscribe to specified segments
      foreach ($segment_ids as $segment_id) {
        if ((int)$segment_id > 0) {
          self::createOrUpdate([
            'subscriber_id' => $subscriber->id,
            'segment_id' => $segment_id,
            'status' => Subscriber::STATUS_SUBSCRIBED,
          ]);
        }
      }
      return true;
    }
  }

  static function resetSubscriptions($subscriber, $segment_ids = []) {
    self::unsubscribeFromSegments($subscriber);
    return self::subscribeToSegments($subscriber, $segment_ids);
  }

  static function subscribeManyToSegments(
    $subscriber_ids = [],
    $segment_ids = []
  ) {
    if (empty($subscriber_ids) || empty($segment_ids)) {
      return false;
    }

    // create many subscriptions to each segment
    $values = [];
    $row_count = 0;
    foreach ($segment_ids as &$segment_id) {
      foreach ($subscriber_ids as &$subscriber_id) {
        $values[] = (int)$subscriber_id;
        $values[] = (int)$segment_id;
        $row_count++;
      }
    }

    $query = [
      'INSERT IGNORE INTO `' . self::$_table . '`',
      '(`subscriber_id`, `segment_id`, `created_at`)',
      'VALUES ' . rtrim(str_repeat('(?, ?, NOW()),', $row_count), ','),
    ];
    self::rawExecute(join(' ', $query), $values);

    return true;
  }

  static function deleteManySubscriptions($subscriber_ids = [], $segment_ids = []) {
    if (empty($subscriber_ids)) return false;

    // delete subscribers' relations to segments (except WP and WooCommerce segments)
    $subscriptions = self::whereIn(
      'subscriber_id', $subscriber_ids
    );

    $wp_segment = Segment::getWPSegment();
    $wc_segment = Segment::getWooCommerceSegment();
    if ($wp_segment !== false) {
      $subscriptions = $subscriptions->whereNotEqual(
        'segment_id', $wp_segment->id
      );
    }
    if ($wc_segment !== false) {
      $subscriptions = $subscriptions->whereNotEqual(
        'segment_id', $wc_segment->id
      );
    }

    if (!empty($segment_ids)) {
      $subscriptions = $subscriptions->whereIn('segment_id', $segment_ids);
    }

    return $subscriptions->deleteMany();
  }

  static function deleteSubscriptions($subscriber, $segment_ids = []) {
    if ($subscriber === false) return false;

    $wp_segment = Segment::getWPSegment();
    $wc_segment = Segment::getWooCommerceSegment();

    $subscriptions = self::where('subscriber_id', $subscriber->id)
      ->whereNotIn('segment_id', [$wp_segment->id, $wc_segment->id]);

    if (!empty($segment_ids)) {
      $subscriptions = $subscriptions->whereIn('segment_id', $segment_ids);
    }
    return $subscriptions->deleteMany();
  }

  static function subscribed($orm) {
    return $orm->where('status', Subscriber::STATUS_SUBSCRIBED);
  }

  static function createOrUpdate($data = []) {
    $keys = false;
    if (isset($data['subscriber_id']) && isset($data['segment_id'])) {
      $keys = [
        'subscriber_id' => (int)$data['subscriber_id'],
        'segment_id' => (int)$data['segment_id'],
      ];
    }
    return parent::_createOrUpdate($data, $keys);
  }
}
