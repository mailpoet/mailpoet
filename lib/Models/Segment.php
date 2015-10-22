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

  function subscribers() {
    return $this->has_many_through(
      __NAMESPACE__.'\Subscriber',
      __NAMESPACE__.'\SubscriberSegment',
      'segment_id',
      'subscriber_id'
    );
  }

  function newsletters() {
    return $this->has_many_through(
      __NAMESPACE__.'\Newsletter',
      __NAMESPACE__.'\NewsletterSegment',
      'segment_id',
      'newsletter_id'
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

    $saved = $segment->save();

    if($saved === true) {
      return true;
    } else {
      $errors = $segment->getValidationErrors();
      if(!empty($errors)) {
        return $errors;
      }
    }
    return false;
  }

  static function trash($listing, $data = array()) {
    $confirm_delete = filter_var($data['confirm'], FILTER_VALIDATE_BOOLEAN);
    if($confirm_delete) {
      // delete relations with all segments
      $segments = $listing->getSelection()->findResultSet();

      if(!empty($segments)) {
        $segments_count = 0;
        foreach($segments as $segment) {
          if($segment->delete()) {
            $segments_count++;
          }
        }
        return array(
          'segments' => $segments_count
        );
      }
      return false;
    } else {
      // soft delete
      $segments = $listing->getSelection()
        ->findResultSet()
        ->set_expr('deleted_at', 'NOW()')
        ->save();

      return array(
        'segments' => $segments->count()
      );
    }
  }

  static function restore($listing, $data = array()) {
    $segments = $listing->getSelection()
      ->findResultSet()
      ->set_expr('deleted_at', 'NULL')
      ->save();

    return array(
      'segments' => $segments->count()
    );
  }
}
