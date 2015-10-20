<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Subscriber extends Model {
  public static $_table = MP_SUBSCRIBERS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('email', array(
      'required' => __('You need to enter your email address.'),
      'isEmail' => __('Your email address is invalid.')
    ));
  }

   function delete() {
    // delete all relations to segments
    SubscriberSegment::where('subscriber_id', $this->id)->deleteMany();

    parent::delete();
  }

  static function search($orm, $search = '') {
    if(strlen(trim($search) === 0)) {
      return $orm;
    }

    return $orm->where_raw(
      '(`email` LIKE ? OR `first_name` LIKE ? OR `last_name` LIKE ?)',
      array('%'.$search.'%', '%'.$search.'%', '%'.$search.'%')
    );
  }

  static function filters() {
    $segments = Segment::orderByAsc('name')->findMany();
    $segment_list = array();

    $segment_list[] = array(
      'label' => __('All lists'),
      'value' => ''
    );

    foreach($segments as $segment) {
      $subscribers_count = $segment->subscribers()->count();
      if($subscribers_count > 0) {
        $segment_list[] = array(
          'label' => sprintf('%s (%d)', $segment->name, $subscribers_count),
          'value' => $segment->id()
        );
      }
    }

    $filters = array(
      array(
        'name' => 'segment',
        'options' => $segment_list
      )
    );

    return $filters;
  }

  static function filterBy($orm, $filters = null) {
    if(empty($filters)) {
      return $orm;
    }

    foreach($filters as $filter) {
      if($filter['name'] === 'segment') {

        $segment = Segment::findOne($filter['value']);
        if($segment !== false) {
          $orm = $orm
          ->select('model.*')
          ->select('subscriber_segment.id', 'subscriber_segment_id')
          ->join(
            MP_SUBSCRIBER_SEGMENT_TABLE,
            'model.id = subscriber_segment.subscriber_id',
            'subscriber_segment'
          )
          ->where('subscriber_segment.segment_id', (int)$filter['value']);
        }
      }
    }
    return $orm;
  }

  static function groups() {
    return array(
      array(
        'name' => 'all',
        'label' => __('All'),
        'count' => Subscriber::count()
      ),
      array(
        'name' => 'subscribed',
        'label' => __('Subscribed'),
        'count' => Subscriber::where('status', 'subscribed')->count()
      ),
      array(
        'name' => 'unconfirmed',
        'label' => __('Unconfirmed'),
        'count' => Subscriber::where('status', 'unconfirmed')->count()
      ),
      array(
        'name' => 'unsubscribed',
        'label' => __('Unsubscribed'),
        'count' => Subscriber::where('status', 'unsubscribed')->count()
      )
    );
  }

  static function groupBy($orm, $group = null) {
    if($group === null or !in_array(
      $group,
      array('subscribed', 'unconfirmed', 'unsubscribed')
    )) {
      return $orm;
    }

    return $orm->where('status', $group);
  }

  static function filterWithCustomFields($orm) {
    $orm = $orm->select(MP_SUBSCRIBERS_TABLE.'.*');
    $customFields = CustomField::findArray();
    foreach ($customFields as $customField) {
      $orm = $orm->select_expr(
        'IFNULL(GROUP_CONCAT(CASE WHEN ' .
        MP_CUSTOM_FIELDS_TABLE . '.id=' . $customField['id'] . ' THEN ' .
        MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.value END), NULL) as "' . $customField['name'].'"');
    }
    $orm = $orm
      ->left_outer_join(
        MP_SUBSCRIBER_CUSTOM_FIELD_TABLE,
        array(MP_SUBSCRIBERS_TABLE.'.id', '=',
          MP_SUBSCRIBER_CUSTOM_FIELD_TABLE.'.subscriber_id'))
      ->left_outer_join(
        MP_CUSTOM_FIELDS_TABLE,
        array(MP_CUSTOM_FIELDS_TABLE.'.id','=',
          MP_SUBSCRIBER_CUSTOM_FIELD_TABLE.'.custom_field_id'))
      ->group_by(MP_SUBSCRIBERS_TABLE.'.id');
    return $orm;
  }

  function segments() {
    return $this->has_many_through(
      __NAMESPACE__.'\Segment',
      __NAMESPACE__.'\SubscriberSegment',
      'subscriber_id',
      'segment_id'
    );
  }

  function customFields() {
    return $this->has_many_through(
      __NAMESPACE__.'\CustomField',
      __NAMESPACE__.'\SubscriberCustomField',
      'subscriber_id',
      'custom_field_id'
    )->select_expr(MP_SUBSCRIBER_CUSTOM_FIELD_TABLE.'.value');
  }

  static function createOrUpdate($data = array()) {
    $subscriber = false;

    if(isset($data['id']) && (int)$data['id'] > 0) {
      $subscriber = self::findOne((int)$data['id']);
    }

    if($subscriber === false) {
      $subscriber = self::create();
      $subscriber->hydrate($data);
    } else {
      unset($data['id']);
      $subscriber->set($data);
    }

    $saved = $subscriber->save();

    if($saved === true) {
      return true;
    } else {
      $errors = $subscriber->getValidationErrors();
      if(!empty($errors)) {
        return $errors;
      }
    }
    return false;
  }

  static function moveToList($listing, $data = array()) {
    $segment_id = (isset($data['segment_id']) ? (int)$data['segment_id'] : 0);
    $segment = Segment::findOne($segment_id);

    if($segment !== false) {
      $subscribers = $listing->getSelection()->findMany();
      foreach($subscribers as $subscriber) {
        // remove subscriber from all segments
        SubscriberSegment::where('subscriber_id', $subscriber->id)->deleteMany();

        // create relation with segment
        $association = SubscriberSegment::create();
        $association->subscriber_id = $subscriber->id;
        $association->segment_id = $segment->id;
        $association->save();
      }
      return true;
    }
    return false;
  }

  static function removeFromList($listing, $data = array()) {
    $segment_id = (isset($data['segment_id']) ? (int)$data['segment_id'] : 0);
    $segment = Segment::findOne($segment_id);

    if($segment !== false) {
      // delete relations with segment
      $subscriber_ids = $listing->getSelectionIds();
      SubscriberSegment::whereIn('subscriber_id', $subscriber_ids)
        ->where('segment_id', $segment->id)
        ->deleteMany();
      return true;
    }
    return false;
  }

  static function addToList($listing, $data = array()) {
    $segment_id = (isset($data['segment_id']) ? (int)$data['segment_id'] : 0);
    $segment = Segment::findOne($segment_id);

    if($segment !== false) {
      $subscribers = $listing->getSelection()->findMany();
      foreach($subscribers as $subscriber) {
        // create relation with segment
        $association = \MailPoet\Models\SubscriberSegment::create();
        $association->subscriber_id = $subscriber->id;
        $association->segment_id = $segment->id;
        $association->save();
      }
      return true;
    }
    return false;
  }

  static function trash($listing) {
    // delete relations with all segments
    $subscriber_ids = $listing->getSelectionIds();
    \MailPoet\Models\SubscriberSegment::whereIn('subscriber_id', $subscriber_ids)->deleteMany();

    return $listing->getSelection()->deleteMany();
  }
}