<?php
namespace MailPoet\Models;

use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WooCommerce\Helper as WCHelper;

if (!defined('ABSPATH')) exit;

/**
 * @property array $subscribers_count
 * @property string $name
 * @property string $type
 * @property string $description
 */

class Segment extends Model {
  static $_table = MP_SEGMENTS_TABLE;
  const TYPE_WP_USERS = 'wp_users';
  const TYPE_WC_USERS = 'woocommerce_users';
  const TYPE_DEFAULT = 'default';

  function __construct() {
    parent::__construct();

    $this->addValidations('name', [
      'required' => WPFunctions::get()->__('Please specify a name.', 'mailpoet'),
    ]);
  }

  function delete() {
    // delete all relations to subscribers
    SubscriberSegment::where('segment_id', $this->id)->deleteMany();
    return parent::delete();
  }

  function newsletters() {
    return $this->has_many_through(
      __NAMESPACE__ . '\Newsletter',
      __NAMESPACE__ . '\NewsletterSegment',
      'segment_id',
      'newsletter_id'
    );
  }

  function subscribers() {
    return $this->has_many_through(
      __NAMESPACE__ . '\Subscriber',
      __NAMESPACE__ . '\SubscriberSegment',
      'segment_id',
      'subscriber_id'
    );
  }

  function duplicate($data = []) {
    $duplicate = parent::duplicate($data);

    if ($duplicate !== false) {
      foreach ($this->subscribers()->findResultSet() as $relation) {
        $new_relation = SubscriberSegment::create();
        $new_relation->set('subscriber_id', $relation->id);
        $new_relation->set('segment_id', $duplicate->id);
        $new_relation->save();
      }

      return $duplicate;
    }
    return false;
  }

  function addSubscriber($subscriber_id) {
    $relation = SubscriberSegment::create();
    $relation->set('subscriber_id', $subscriber_id);
    $relation->set('segment_id', $this->id);
    return $relation->save();
  }

  function removeSubscriber($subscriber_id) {
    return SubscriberSegment::where('subscriber_id', $subscriber_id)
      ->where('segment_id', $this->id)
      ->delete();
  }

  function withSubscribersCount() {
    $this->subscribers_count = SubscriberSegment::tableAlias('relation')
      ->where('relation.segment_id', $this->id)
      ->join(
        MP_SUBSCRIBERS_TABLE,
        'subscribers.id = relation.subscriber_id',
        'subscribers'
      )
      ->select_expr(
        'SUM(CASE WHEN subscribers.status = "' . Subscriber::STATUS_SUBSCRIBED . '"
        AND relation.status = "' . Subscriber::STATUS_SUBSCRIBED . '" THEN 1 ELSE 0 END)',
        Subscriber::STATUS_SUBSCRIBED
      )
      ->select_expr(
        'SUM(CASE WHEN subscribers.status = "' . Subscriber::STATUS_UNSUBSCRIBED . '"
        OR relation.status = "' . Subscriber::STATUS_UNSUBSCRIBED . '" THEN 1 ELSE 0 END)',
        Subscriber::STATUS_UNSUBSCRIBED
      )
      ->select_expr(
        'SUM(CASE WHEN subscribers.status = "' . Subscriber::STATUS_INACTIVE . '"
        AND relation.status != "' . Subscriber::STATUS_UNSUBSCRIBED . '" THEN 1 ELSE 0 END)',
        Subscriber::STATUS_INACTIVE
      )
      ->select_expr(
        'SUM(CASE WHEN subscribers.status = "' . Subscriber::STATUS_UNCONFIRMED . '"
        AND relation.status != "' . Subscriber::STATUS_UNSUBSCRIBED . '" THEN 1 ELSE 0 END)',
        Subscriber::STATUS_UNCONFIRMED
      )
      ->select_expr(
        'SUM(CASE WHEN subscribers.status = "' . Subscriber::STATUS_BOUNCED . '"
        AND relation.status != "' . Subscriber::STATUS_UNSUBSCRIBED . '" THEN 1 ELSE 0 END)',
        Subscriber::STATUS_BOUNCED
      )
      ->whereNull('subscribers.deleted_at')
      ->findOne()
      ->asArray();

    return $this;
  }

  static function getWPSegment() {
    $wp_segment = self::where('type', self::TYPE_WP_USERS)->findOne();

    if ($wp_segment === false) {
      // create the wp users segment
      $wp_segment = Segment::create();
      $wp_segment->hydrate([
        'name' => WPFunctions::get()->__('WordPress Users', 'mailpoet'),
        'description' =>
          WPFunctions::get()->__('This list contains all of your WordPress users.', 'mailpoet'),
        'type' => self::TYPE_WP_USERS,
      ]);
      $wp_segment->save();
    }

    return $wp_segment;
  }

  static function getWooCommerceSegment() {
    $wc_segment = self::where('type', self::TYPE_WC_USERS)->findOne();

    if ($wc_segment === false) {
      // create the WooCommerce customers segment
      $wc_segment = Segment::create();
      $wc_segment->hydrate([
        'name' => WPFunctions::get()->__('WooCommerce Customers', 'mailpoet'),
        'description' =>
          WPFunctions::get()->__('This list contains all of your WooCommerce customers.', 'mailpoet'),
        'type' => self::TYPE_WC_USERS,
      ]);
      $wc_segment->save();
    }

    return $wc_segment;
  }

  static function shouldShowWooCommerceSegment() {
    $woocommerce_helper = new WCHelper();
    $is_woocommerce_active = $woocommerce_helper->isWooCommerceActive();
    $woocommerce_user_exists = Segment::tableAlias('segment')
      ->where('segment.type', Segment::TYPE_WC_USERS)
      ->join(
        MP_SUBSCRIBER_SEGMENT_TABLE,
        'segment_subscribers.segment_id = segment.id',
        'segment_subscribers'
      )
      ->limit(1)
      ->findOne();

    if (!$is_woocommerce_active && !$woocommerce_user_exists) {
      return false;
    }
    return true;
  }

  static function getSegmentTypes() {
    $types = [Segment::TYPE_DEFAULT, Segment::TYPE_WP_USERS];
    if (Segment::shouldShowWooCommerceSegment()) {
      $types[] = Segment::TYPE_WC_USERS;
    }
    return $types;
  }

  static function search($orm, $search = '') {
    return $orm->whereLike('name', '%' . $search . '%');
  }

  static function groups() {
    return [
      [
        'name' => 'all',
        'label' => WPFunctions::get()->__('All', 'mailpoet'),
        'count' => Segment::getPublished()->count(),
      ],
      [
        'name' => 'trash',
        'label' => WPFunctions::get()->__('Trash', 'mailpoet'),
        'count' => Segment::getTrashed()->count(),
      ],
    ];
  }

  static function groupBy($orm, $group = null) {
    if ($group === 'trash') {
      $orm->whereNotNull('deleted_at');
    } else {
      $orm->whereNull('deleted_at');
    }
    return $orm;
  }

  static function getSegmentsWithSubscriberCount($type = self::TYPE_DEFAULT) {
    $query = self::selectMany([self::$_table . '.id', self::$_table . '.name'])
      ->whereIn('type', Segment::getSegmentTypes())
      ->selectExpr(
        self::$_table . '.*, ' .
        'COUNT(IF(' .
          MP_SUBSCRIBER_SEGMENT_TABLE . '.status="' . Subscriber::STATUS_SUBSCRIBED . '"'
          . ' AND ' .
          MP_SUBSCRIBERS_TABLE . '.deleted_at IS NULL'
          . ' AND ' .
          MP_SUBSCRIBERS_TABLE . '.status="' . Subscriber::STATUS_SUBSCRIBED . '"'
          . ', 1, NULL)) `subscribers`'
      )
      ->leftOuterJoin(
        MP_SUBSCRIBER_SEGMENT_TABLE,
        [self::$_table . '.id', '=', MP_SUBSCRIBER_SEGMENT_TABLE . '.segment_id'])
      ->leftOuterJoin(
        MP_SUBSCRIBERS_TABLE,
        [MP_SUBSCRIBER_SEGMENT_TABLE . '.subscriber_id', '=', MP_SUBSCRIBERS_TABLE . '.id'])
      ->groupBy(self::$_table . '.id')
      ->groupBy(self::$_table . '.name')
      ->orderByAsc(self::$_table . '.name')
      ->whereNull(self::$_table . '.deleted_at');

    if (!empty($type)) {
      $query->where(self::$_table . '.type', $type);
    }

    return $query->findArray();
  }

  static function getSegmentsForImport() {
    $segments = self::getSegmentsWithSubscriberCount($type = false);
    return array_values(array_filter($segments, function($segment) {
      return $segment['type'] !== Segment::TYPE_WC_USERS;
    }));
  }

  static function getSegmentsForExport() {
    return self::rawQuery(
      '(SELECT segments.id, segments.name, COUNT(relation.subscriber_id) as subscribers ' .
      'FROM ' . MP_SUBSCRIBER_SEGMENT_TABLE . ' relation ' .
      'LEFT JOIN ' . self::$_table . ' segments ON segments.id = relation.segment_id ' .
      'INNER JOIN ' . MP_SUBSCRIBERS_TABLE . ' subscribers ON subscribers.id = relation.subscriber_id ' .
      'WHERE relation.segment_id IS NOT NULL ' .
      'AND subscribers.deleted_at IS NULL ' .
      'GROUP BY segments.id) ' .
      'UNION ALL ' .
      '(SELECT 0 as id, "' . WPFunctions::get()->__('Not in a List', 'mailpoet') . '" as name, COUNT(*) as subscribers ' .
      'FROM ' . MP_SUBSCRIBERS_TABLE . ' subscribers ' .
      'LEFT JOIN ' . MP_SUBSCRIBER_SEGMENT_TABLE . ' relation on relation.subscriber_id = subscribers.id ' .
      'WHERE relation.subscriber_id is NULL ' .
      'AND subscribers.deleted_at IS NULL ' .
      'HAVING subscribers) ' .
      'ORDER BY name'
    )->findArray();
  }

  static function listingQuery(array $data = []) {
    $query = self::select('*');
    $query->whereIn('type', Segment::getSegmentTypes());
    if (isset($data['group'])) {
      $query->filter('groupBy', $data['group']);
    }
    return $query;
  }

  static function getPublic() {
    return self::getPublished()->where('type', self::TYPE_DEFAULT)->orderByAsc('name');
  }

  static function bulkTrash($orm) {
    $count = parent::bulkAction($orm, function($ids) {
      Segment::rawExecute(join(' ', [
        'UPDATE `' . Segment::$_table . '`',
        'SET `deleted_at` = NOW()',
        'WHERE `id` IN (' . rtrim(str_repeat('?,', count($ids)), ',') . ')',
        'AND `type` = "' . Segment::TYPE_DEFAULT . '"',
      ]), $ids);
    });

    return ['count' => $count];
  }

  static function bulkDelete($orm) {
    $count = parent::bulkAction($orm, function($ids) {
      // delete segments (only default)
      $segments = Segment::whereIn('id', $ids)
        ->where('type', Segment::TYPE_DEFAULT)
        ->findMany();
      $ids = array_map(function($segment) {
        return $segment->id;
      }, $segments);
      SubscriberSegment::whereIn('segment_id', $ids)
        ->deleteMany();
      Segment::whereIn('id', $ids)->deleteMany();
    });

    return ['count' => $count];
  }

  static function getAnalytics() {
    $analytics = Segment::selectExpr('type, count(*) as count')
                        ->whereNull('deleted_at')
                        ->groupBy('type')
                        ->findArray();
    $result = [];
    foreach ($analytics as $segment) {
      $result[$segment['type']] = $segment['count'];
    }
    return $result;
  }
}
