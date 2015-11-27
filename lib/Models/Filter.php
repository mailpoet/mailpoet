<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Filter extends Model {
  static $_table = MP_FILTERS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', array(
      'required' => __('You need to specify a name.')
    ));
  }

  function delete() {
    // delete all relations to subscribers
    SegmentFilter::where('filter_id', $this->id)->deleteMany();
    return parent::delete();
  }

  function segments() {
    return $this->has_many_through(
      __NAMESPACE__.'\Segment',
      __NAMESPACE__.'\SegmentFilter',
      'filter_id',
      'segment_id'
    );
  }

  static function createOrUpdate($data = array()) {
    $filter = false;

    if(isset($data['id']) && (int)$data['id'] > 0) {
      $filter = self::findOne((int)$data['id']);
    }

    if($filter === false) {
      $filter = self::create();
      $filter->hydrate($data);
    } else {
      unset($data['id']);
      $filter->set($data);
    }

    $filter->save();
    return $filter;
  }
}