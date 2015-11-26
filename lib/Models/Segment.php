<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Segment extends Model {
  static $_table = MP_SEGMENTS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', array(
      'required' => __('You need to specify a name.')
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
    );
  }

  function segmentFilters() {
    return $this->has_many_through(
      __NAMESPACE__.'\Filter',
      __NAMESPACE__.'\SegmentFilter',
      'segment_id',
      'filter_id'
    );
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

  static function getWPUsers() {
    $segment = self::where('type', 'wp_users')->findOne();

    if($segment === false) {
      // create the wp users list
      $segment = self::create();
      $segment->hydrate(array(
        'name' => __('WordPress Users'),
        'type' => 'wp_users'
      ));
      $segment->save();
      return self::findOne($segment->id());
    }

    return $segment;
  }

  static function search($orm, $search = '') {
    return $orm->whereLike('name', '%'.$search.'%');
  }

  static function groups() {
    return array(
      array(
        'name' => 'all',
        'label' => __('All'),
        'count' => Segment::getPublished()->count()
      ),
      array(
        'name' => 'trash',
        'label' => __('Trash'),
        'count' => Segment::getTrashed()->count()
      )
    );
  }

  static function groupBy($orm, $group = null) {
    if($group === 'trash') {
      return $orm->whereNotNull('deleted_at');
    } else {
      $orm = $orm->whereNull('deleted_at');
    }
  }

  static function getSegmentsForImport() {
    return self::selectMany(array(self::$_table.'.id', self::$_table.'.name'))
      ->select_expr(
        'COUNT('.MP_SUBSCRIBER_SEGMENT_TABLE.'.subscriber_id)', 'subscribers'
      )
      ->left_outer_join(
        MP_SUBSCRIBER_SEGMENT_TABLE,
        array(self::$_table.'.id', '=', MP_SUBSCRIBER_SEGMENT_TABLE.'.segment_id'))
      ->group_by(self::$_table.'.id')
      ->group_by(self::$_table.'.name')
      ->where(self::$_table.'.type', 'default')
      ->findArray();
  }

  static function getSegmentsForExport($withConfirmedSubscribers = false) {
    return self::raw_query(
      '(SELECT segments.id, segments.name, COUNT(relation.subscriber_id) as subscribers ' .
      'FROM ' . MP_SUBSCRIBER_SEGMENT_TABLE . ' relation ' .
      'LEFT JOIN ' . self::$_table . ' segments ON segments.id = relation.segment_id ' .
      'LEFT JOIN ' . MP_SUBSCRIBERS_TABLE . ' subscribers ON subscribers.id = relation.subscriber_id ' .
      (($withConfirmedSubscribers) ?
        'WHERE subscribers.status = 1 ' :
        'WHERE relation.segment_id IS NOT NULL ') .
      'GROUP BY segments.id) ' .
      'UNION ALL ' .
      '(SELECT 0 as id, "' . __('Not In List') . '" as name, COUNT(*) as subscribers ' .
      'FROM ' . MP_SUBSCRIBERS_TABLE . ' subscribers ' .
      'LEFT JOIN ' . MP_SUBSCRIBER_SEGMENT_TABLE . ' relation on relation.subscriber_id = subscribers.id ' .
      (($withConfirmedSubscribers) ?
        'WHERE relation.subscriber_id is NULL AND subscribers.status = 1 ' :
        'WHERE relation.subscriber_id is NULL ') .
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
    return self::getPublished()->where('type', 'default');
  }
}