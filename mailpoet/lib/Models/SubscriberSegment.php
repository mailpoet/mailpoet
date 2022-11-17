<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Models;

/**
 * @property int $id
 * @property int $subscriberId
 * @property int $segmentId
 * @property string $status
 */

class SubscriberSegment extends Model {
  public static $_table = MP_SUBSCRIBER_SEGMENT_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public function subscriber() {
    return $this->has_one(__NAMESPACE__ . '\Subscriber', 'id', 'subscriber_id');
  }

  public static function unsubscribeFromSegments($subscriber, $segmentIds = []) {
    if (!$subscriber) return false;

    // Reset confirmation emails count, so user can resubscribe
    $subscriber->countConfirmations = 0;
    $subscriber->save();

    $wpSegment = Segment::getWPSegment();

    if (!empty($segmentIds)) {
      // unsubscribe from segments
      foreach ($segmentIds as $segmentId) {

        // do not remove subscriptions to the WP Users segment
        if ($wpSegment !== false && (int)$wpSegment->id === (int)$segmentId) {
          continue;
        }

        if ((int)$segmentId > 0) {
          self::createOrUpdate([
            'subscriber_id' => $subscriber->id,
            'segment_id' => $segmentId,
            'status' => Subscriber::STATUS_UNSUBSCRIBED,
          ]);
        }
      }
    } else {
      // unsubscribe from all segments (except the WP users and WooCommerce customers segments)
      $subscriptions = self::where('subscriber_id', $subscriber->id);

      if ($wpSegment !== false) {
        $subscriptions = $subscriptions->whereNotEqual(
          'segment_id', $wpSegment->id
        );
      }

      $subscriptions->findResultSet()
        ->set('status', Subscriber::STATUS_UNSUBSCRIBED)
        ->save();
    }
    return true;
  }

  public static function resubscribeToAllSegments($subscriber) {
    if ($subscriber === false) return false;
    // (re)subscribe to all segments linked to the subscriber
    return self::where('subscriber_id', $subscriber->id)
      ->findResultSet()
      ->set('status', Subscriber::STATUS_SUBSCRIBED)
      ->save();
  }

  public static function subscribeToSegments($subscriber, $segmentIds = []) {
    if ($subscriber === false) return false;
    if (!empty($segmentIds)) {
      // subscribe to specified segments
      foreach ($segmentIds as $segmentId) {
        if ((int)$segmentId > 0) {
          self::createOrUpdate([
            'subscriber_id' => $subscriber->id,
            'segment_id' => $segmentId,
            'status' => Subscriber::STATUS_SUBSCRIBED,
          ]);
        }
      }
      return true;
    }
  }

  public static function resetSubscriptions($subscriber, $segmentIds = []) {
    self::unsubscribeFromSegments($subscriber);
    return self::subscribeToSegments($subscriber, $segmentIds);
  }

  public static function subscribeManyToSegments(
    $subscriberIds = [],
    $segmentIds = []
  ) {
    if (empty($subscriberIds) || empty($segmentIds)) {
      return false;
    }

    // create many subscriptions to each segment
    $values = [];
    $rowCount = 0;
    foreach ($segmentIds as &$segmentId) {
      foreach ($subscriberIds as &$subscriberId) {
        $values[] = (int)$subscriberId;
        $values[] = (int)$segmentId;
        $rowCount++;
      }
    }

    $query = [
      'INSERT IGNORE INTO `' . self::$_table . '`',
      '(`subscriber_id`, `segment_id`, `created_at`)',
      'VALUES ' . rtrim(str_repeat('(?, ?, NOW()),', $rowCount), ','),
    ];
    self::rawExecute(join(' ', $query), $values);

    return true;
  }

  public static function deleteManySubscriptions($subscriberIds = [], $segmentIds = []) {
    if (empty($subscriberIds)) return false;

    // delete subscribers' relations to segments (except WP and WooCommerce segments)
    $subscriptions = self::whereIn(
      'subscriber_id', $subscriberIds
    );

    $wpSegment = Segment::getWPSegment();
    $wcSegment = Segment::getWooCommerceSegment();
    if ($wpSegment !== false) {
      $subscriptions = $subscriptions->whereNotEqual(
        'segment_id', $wpSegment->id
      );
    }
    if ($wcSegment !== false) {
      $subscriptions = $subscriptions->whereNotEqual(
        'segment_id', $wcSegment->id
      );
    }

    if (!empty($segmentIds)) {
      $subscriptions = $subscriptions->whereIn('segment_id', $segmentIds);
    }

    return $subscriptions->deleteMany();
  }

  public static function deleteSubscriptions($subscriber, $segmentIds = []) {
    if ($subscriber === false) return false;

    $wpSegment = Segment::getWPSegment();
    $wcSegment = Segment::getWooCommerceSegment();

    $subscriptions = self::where('subscriber_id', $subscriber->id)
      ->whereNotIn('segment_id', [$wpSegment->id, $wcSegment->id]);

    if (!empty($segmentIds)) {
      $subscriptions = $subscriptions->whereIn('segment_id', $segmentIds);
    }
    return $subscriptions->deleteMany();
  }

  public static function subscribed($orm) {
    return $orm->where('status', Subscriber::STATUS_SUBSCRIBED);
  }

  public static function createOrUpdate($data = []) {
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
