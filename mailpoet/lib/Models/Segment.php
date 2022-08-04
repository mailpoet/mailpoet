<?php

namespace MailPoet\Models;

use MailPoet\Entities\SegmentEntity;
use MailPoet\WooCommerce\Helper as WCHelper;

/**
 * @property array $subscribersCount
 * @property array $automatedEmailsSubjects
 * @property string $name
 * @property string $type
 * @property string $description
 * @property string $countConfirmations
 */

class Segment extends Model {
  public static $_table = MP_SEGMENTS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration
  const TYPE_WP_USERS = SegmentEntity::TYPE_WP_USERS;
  const TYPE_WC_USERS = SegmentEntity::TYPE_WC_USERS;
  const TYPE_WC_MEMBERSHIPS = SegmentEntity::TYPE_WC_MEMBERSHIPS;
  const TYPE_DEFAULT = SegmentEntity::TYPE_DEFAULT;

  public function __construct() {
    parent::__construct();

    $this->addValidations('name', [
      'required' => __('Please specify a name.', 'mailpoet'),
    ]);
  }

  public function delete() {
    // delete all relations to subscribers
    SubscriberSegment::where('segment_id', $this->id)->deleteMany();
    return parent::delete();
  }

  public function newsletters() {
    return $this->has_many_through(
      __NAMESPACE__ . '\Newsletter',
      __NAMESPACE__ . '\NewsletterSegment',
      'segment_id',
      'newsletter_id'
    );
  }

  public function subscribers() {
    return $this->has_many_through(
      __NAMESPACE__ . '\Subscriber',
      __NAMESPACE__ . '\SubscriberSegment',
      'segment_id',
      'subscriber_id'
    );
  }

  public function duplicate($data = []) {
    $duplicate = parent::duplicate($data);

    if ($duplicate !== false) {
      foreach ($this->subscribers()->findResultSet() as $relation) {
        $newRelation = SubscriberSegment::create();
        $newRelation->set('subscriber_id', $relation->id);
        $newRelation->set('segment_id', $duplicate->id);
        $newRelation->save();
      }

      return $duplicate;
    }
    return false;
  }

  public function addSubscriber($subscriberId) {
    $relation = SubscriberSegment::create();
    $relation->set('subscriber_id', $subscriberId);
    $relation->set('segment_id', $this->id);
    return $relation->save();
  }

  public function removeSubscriber($subscriberId) {
    return SubscriberSegment::where('subscriber_id', $subscriberId)
      ->where('segment_id', $this->id)
      ->delete();
  }

  /**
   * @deprecated Use the version in \MailPoet\Segments\SegmentSubscribersRepository::getSubscribersStatisticsCount
   * @return $this
   */
  public function withSubscribersCount() {
    trigger_error('Calling Segment::withSubscribersCount() is deprecated and will be removed. Use MailPoet\Segments\SegmentSubscribersRepository::getSubscribersStatisticsCount. ', E_USER_DEPRECATED);
    $query = SubscriberSegment::tableAlias('relation')
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
      ->findOne();

    if ($query instanceof SubscriberSegment) {
      $this->subscribersCount = $query->asArray();
    }

    return $this;
  }

  public static function getWPSegment() {
    $wpSegment = self::where('type', self::TYPE_WP_USERS)->findOne();

    if ($wpSegment === false) {
      // create the wp users segment
      $wpSegment = Segment::create();
      $wpSegment->hydrate([
        'name' => __('WordPress Users', 'mailpoet'),
        'description' =>
          __('This list contains all of your WordPress users.', 'mailpoet'),
        'type' => self::TYPE_WP_USERS,
      ]);
      $wpSegment->save();
    }

    return $wpSegment;
  }

  public static function getWooCommerceSegment() {
    $wcSegment = self::where('type', self::TYPE_WC_USERS)->findOne();

    if ($wcSegment === false) {
      // create the WooCommerce customers segment
      $wcSegment = Segment::create();
      $wcSegment->hydrate([
        'name' => __('WooCommerce Customers', 'mailpoet'),
        'description' =>
          __('This list contains all of your WooCommerce customers.', 'mailpoet'),
        'type' => self::TYPE_WC_USERS,
      ]);
      $wcSegment->save();
    }

    return $wcSegment;
  }

  /**
   * @deprecated Use the non static implementation in \MailPoet\Segments\WooCommerce::shouldShowWooCommerceSegment instead
   */
  public static function shouldShowWooCommerceSegment() {
    $woocommerceHelper = new WCHelper();
    $isWoocommerceActive = $woocommerceHelper->isWooCommerceActive();
    $woocommerceUserExists = Segment::tableAlias('segment')
      ->where('segment.type', Segment::TYPE_WC_USERS)
      ->join(
        MP_SUBSCRIBER_SEGMENT_TABLE,
        'segment_subscribers.segment_id = segment.id',
        'segment_subscribers'
      )
      ->limit(1)
      ->findOne();

    if (!$isWoocommerceActive && !$woocommerceUserExists) {
      return false;
    }
    return true;
  }

  public static function getSegmentTypes() {
    $types = [Segment::TYPE_DEFAULT, Segment::TYPE_WP_USERS];
    if (Segment::shouldShowWooCommerceSegment()) {
      $types[] = Segment::TYPE_WC_USERS;
    }
    return $types;
  }

  public static function groupBy($orm, $group = null) {
    if ($group === 'trash') {
      $orm->whereNotNull('deleted_at');
    } else {
      $orm->whereNull('deleted_at');
    }
    return $orm;
  }

  /**
   * @deprecated Will be removed after 2021/07/30. Use MailPoet\Segments\SegmentsSimpleListRepository
   */
  public static function getSegmentsWithSubscriberCount($type = self::TYPE_DEFAULT) {
    trigger_error('Calling Segment::getSegmentsWithSubscriberCount() is deprecated and will be removed. Use MailPoet\Segments\SegmentsSimpleListRepository. ', E_USER_DEPRECATED);
    $query = self::selectMany([self::$_table . '.id', self::$_table . '.name'])
      ->whereIn('type', Segment::getSegmentTypes())
      ->selectExpr(
        self::$_table . '.type, ' .
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
      ->groupBy(self::$_table . '.type')
      ->orderByAsc(self::$_table . '.name')
      ->whereNull(self::$_table . '.deleted_at');

    if (!empty($type)) {
      $query->where(self::$_table . '.type', $type);
    }

    return $query->findArray();
  }

  /**
   * @deprecated Will be removed after 2021/07/30. Use MailPoet\Segments\SegmentsSimpleListRepository
   */
  public static function getSegmentsForImport() {
    trigger_error('Calling Segment::getSegmentsForImport() is deprecated and will be removed. Use MailPoet\Segments\SegmentsSimpleListRepository. ', E_USER_DEPRECATED);
    $segments = self::getSegmentsWithSubscriberCount($type = false);
    return array_values(array_filter($segments, function($segment) {
      return $segment['type'] !== Segment::TYPE_WC_USERS;
    }));
  }

  /**
   * @deprecated Will be removed after 2021/07/30. Use MailPoet\Segments\SegmentsSimpleListRepository
   */
  public static function getSegmentsForExport() {
    trigger_error('Calling Segment::getSegmentsForExport() is deprecated and will be removed. Use MailPoet\Segments\SegmentsSimpleListRepository. ', E_USER_DEPRECATED);
    return self::rawQuery(
      '(SELECT segments.id, segments.name, COUNT(relation.subscriber_id) as subscribers ' .
      'FROM ' . MP_SUBSCRIBER_SEGMENT_TABLE . ' relation ' .
      'LEFT JOIN ' . self::$_table . ' segments ON segments.id = relation.segment_id ' .
      'INNER JOIN ' . MP_SUBSCRIBERS_TABLE . ' subscribers ON subscribers.id = relation.subscriber_id ' .
      'WHERE relation.segment_id IS NOT NULL ' .
      'AND subscribers.deleted_at IS NULL ' .
      'GROUP BY segments.id) ' .
      'UNION ALL ' .
      '(SELECT 0 as id, "' . __('Subscribers without a list', 'mailpoet') . '" as name, COUNT(*) as subscribers ' .
      'FROM ' . MP_SUBSCRIBERS_TABLE . ' subscribers ' .
      'LEFT JOIN ' . MP_SUBSCRIBER_SEGMENT_TABLE . ' relation on relation.subscriber_id = subscribers.id ' .
      'WHERE relation.subscriber_id is NULL ' .
      'AND subscribers.deleted_at IS NULL ' .
      'HAVING subscribers) ' .
      'ORDER BY name'
    )->findArray();
  }

  public static function getPublic() {
    return self::getPublished()->where('type', self::TYPE_DEFAULT)->orderByAsc('name');
  }

  public static function bulkTrash($orm) {
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

  public static function bulkDelete($orm) {
    $count = parent::bulkAction($orm, function($ids) {
      // delete segments (only default)
      $segments = Segment::whereIn('id', $ids)
        ->where('type', Segment::TYPE_DEFAULT)
        ->findMany();
      $ids = array_map(function($segment) {
        return $segment->id;
      }, $segments);
      if (!$ids) {
        return;
      }
      SubscriberSegment::whereIn('segment_id', $ids)
        ->deleteMany();
      Segment::whereIn('id', $ids)->deleteMany();
    });

    return ['count' => $count];
  }
}
