<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Segment extends Model {
  public static $_table = MP_SEGMENTS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', array(
      'required' => 'name_is_blank',
      'isString' => 'name_is_not_string'
    ));
  }

  public static function createOrUpdate($model) {
    $exists = self::where('name', $model['name'])
      ->find_one();

    if($exists === false) {
      $new_model = self::create();
      $new_model->name = $model['name'];
      return $new_model->save();
    }

    $exists->name = $model['name_updated'];
    return $exists->save();
  }

  public function subscribers() {
    return $this->has_many_through(
      __NAMESPACE__.'\Subscriber',
      __NAMESPACE__.'\SubscriberSegment',
      'segment_id',
      'subscriber_id'
    );
  }

  static function search($orm, $search = '') {
    return $orm->where_like('name', '%'.$search.'%');
  }

  static function groups() {
    return array(
      array(
        'name' => 'all',
        'label' => __('All'),
        'count' => Segment::count()
      )
    );
  }

  static function group($orm, $group = null) {
  }
}
