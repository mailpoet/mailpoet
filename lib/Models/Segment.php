<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Segment extends Model {
  static $_table = MP_SEGMENTS_TABLE;
  const TYPE_WP_USERS = 'wp_users';
  const TYPE_DEFAULT = 'default';

  function __construct() {
    parent::__construct();

    $this->addValidations('name', array(
      'required' => __('Please specify a name.', 'mailpoet')
    ));
  }

  function delete() {
    // delete all relations to subscribers
    SubscriberSegment::where('segment_id', $this->id)->deleteMany();
    return parent::delete();
  }

  function newsletters() {
    return $this->has_many_through(
      __NAMESPACE__.'\Newsletter',
      __NAMESPACE__.'\NewsletterSegment',
      'segment_id',
      'newsletter_id'
    );
  }

  function subscribers() {
    return $this->has_many_through(
      __NAMESPACE__.'\Subscriber',
      __NAMESPACE__.'\SubscriberSegment',
      'segment_id',
      'subscriber_id'
    )->where(MP_SUBSCRIBER_SEGMENT_TABLE.'.status', Subscriber::STATUS_SUBSCRIBED);
  }

  function duplicate($data = array()) {
    $duplicate = parent::duplicate($data);

    if($duplicate !== false) {
      foreach($this->subscribers()->findResultSet() as $relation) {
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
    $this->subscribers_count = SubscriberSegment::table_alias('relation')
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

    if($wp_segment === false) {
      // create the wp users segment
      $wp_segment = Segment::create();
      $wp_segment->hydrate(array(
        'name' => __('WordPress Users', 'mailpoet'),
        'description' =>
          __('This list contains all of your WordPress users.', 'mailpoet'),
        'type' => 'wp_users'
      ));
      $wp_segment->save();
    }

    return $wp_segment;
  }

  static function search($orm, $search = '') {
    return $orm->whereLike('name', '%'.$search.'%');
  }

  static function groups() {
    return array(
      array(
        'name' => 'all',
        'label' => __('All', 'mailpoet'),
        'count' => Segment::getPublished()->count()
      ),
      array(
        'name' => 'trash',
        'label' => __('Trash', 'mailpoet'),
        'count' => Segment::getTrashed()->count()
      )
    );
  }

  static function groupBy($orm, $group = null) {
    if($group === 'trash') {
      $orm->whereNotNull('deleted_at');
    } else {
      $orm->whereNull('deleted_at');
    }
    return $orm;
  }

  static function getSegmentsWithSubscriberCount($type = self::TYPE_DEFAULT) {
    $query = self::selectMany(array(self::$_table.'.id', self::$_table.'.name'))
      ->selectExpr(
        self::$_table.'.*, ' .
        'COUNT(IF('.
          MP_SUBSCRIBER_SEGMENT_TABLE.'.status="'.Subscriber::STATUS_SUBSCRIBED.'"'
          .' AND '.
          MP_SUBSCRIBERS_TABLE.'.deleted_at IS NULL'
          .' AND '.
          MP_SUBSCRIBERS_TABLE.'.status="'.Subscriber::STATUS_SUBSCRIBED.'"'
          .', 1, NULL)) `subscribers`'
      )
      ->leftOuterJoin(
        MP_SUBSCRIBER_SEGMENT_TABLE,
        array(self::$_table.'.id', '=', MP_SUBSCRIBER_SEGMENT_TABLE.'.segment_id'))
      ->leftOuterJoin(
        MP_SUBSCRIBERS_TABLE,
        array(MP_SUBSCRIBER_SEGMENT_TABLE.'.subscriber_id', '=', MP_SUBSCRIBERS_TABLE.'.id'))
      ->groupBy(self::$_table.'.id')
      ->groupBy(self::$_table.'.name')
      ->orderByAsc(self::$_table.'.name')
      ->whereNull(self::$_table.'.deleted_at');

    if(!empty($type)) {
      $query->where(self::$_table.'.type', $type);
    }

    return $query->findArray();
  }

  static function getSegmentsForImport() {
    return self::getSegmentsWithSubscriberCount($type = false);
  }

  static function getSegmentsForExport($withConfirmedSubscribers = false) {
    return self::raw_query(
      '(SELECT segments.id, segments.name, COUNT(relation.subscriber_id) as subscribers ' .
      'FROM ' . MP_SUBSCRIBER_SEGMENT_TABLE . ' relation ' .
      'LEFT JOIN ' . self::$_table . ' segments ON segments.id = relation.segment_id ' .
      'LEFT JOIN ' . MP_SUBSCRIBERS_TABLE . ' subscribers ON subscribers.id = relation.subscriber_id ' .
      (($withConfirmedSubscribers) ?
        'WHERE subscribers.status = "' . Subscriber::STATUS_SUBSCRIBED . '" ' :
        'WHERE relation.segment_id IS NOT NULL ') .
      'AND subscribers.deleted_at IS NULL ' .
      'AND relation.status = "' . Subscriber::STATUS_SUBSCRIBED . '" ' .
      'GROUP BY segments.id) ' .
      'UNION ALL ' .
      '(SELECT 0 as id, "' . __('Not in a List', 'mailpoet') . '" as name, COUNT(*) as subscribers ' .
      'FROM ' . MP_SUBSCRIBERS_TABLE . ' subscribers ' .
      'LEFT JOIN ' . MP_SUBSCRIBER_SEGMENT_TABLE . ' relation on relation.subscriber_id = subscribers.id ' .
      (($withConfirmedSubscribers) ?
        'WHERE relation.subscriber_id is NULL AND subscribers.status = "' . Subscriber::STATUS_SUBSCRIBED . '" ' :
        'WHERE relation.subscriber_id is NULL ') .
      'AND subscribers.deleted_at IS NULL ' .
      'HAVING subscribers) ' .
      'ORDER BY name'
    )->findArray();
  }

  static function createOrUpdate($data = array()) {
    $segment = false;

    if(isset($data['id']) && (int)$data['id'] > 0) {
      $segment = self::findOne((int)$data['id']);
    }

    if($segment === false) {
      $segment = self::create();
      $segment->hydrate($data);
    } else {
      unset($data['id']);
      $segment->set($data);
    }

    $segment->save();
    return $segment;
  }

  static function getPublic() {
    return self::getPublished()->where('type', self::TYPE_DEFAULT)->orderByAsc('name');
  }

  static function bulkTrash($orm) {
    $count = parent::bulkAction($orm, function($ids) {
      Segment::rawExecute(join(' ', array(
        'UPDATE `' . Segment::$_table . '`',
        'SET `deleted_at` = NOW()',
        'WHERE `id` IN ('.rtrim(str_repeat('?,', count($ids)), ',').')',
        'AND `type` = "' . Segment::TYPE_DEFAULT . '"'
      )), $ids);
    });

    return array('count' => $count);
  }

  static function bulkDelete($orm) {
    $count = parent::bulkAction($orm, function($ids) {
      // delete segments (only default)
      Segment::whereIn('id', $ids)
        ->where('type', Segment::TYPE_DEFAULT)
        ->deleteMany();
    });

    return array('count' => $count);
  }
}
