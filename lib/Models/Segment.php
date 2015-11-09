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
    parent::delete();
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

  static function search($orm, $search = '') {
    return $orm->where_like('name', '%'.$search.'%');
  }

  static function groups() {
    return array(
      array(
        'name' => 'all',
        'label' => __('All'),
        'count' => Segment::whereNull('deleted_at')->count()
      ),
      array(
        'name' => 'trash',
        'label' => __('Trash'),
        'count' => Segment::whereNotNull('deleted_at')->count()
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

  static function filterWithSubscriberCount($orm) {
    $orm = $orm
      ->selectMany(array(self::$_table.'.id', self::$_table.'.name'))
      ->select_expr('COUNT('.MP_SUBSCRIBER_SEGMENT_TABLE.'.subscriber_id)', 'subscribers')
      ->left_outer_join(
        MP_SUBSCRIBER_SEGMENT_TABLE,
        array(self::$_table.'.id', '=', MP_SUBSCRIBER_SEGMENT_TABLE.'.segment_id'))
      ->group_by(self::$_table.'.id')
      ->group_by(self::$_table.'.name');
    return $orm;
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
}