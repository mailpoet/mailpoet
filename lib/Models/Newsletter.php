<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Newsletter extends Model {
  public static $_table = MP_NEWSLETTERS_TABLE;

  function __construct() {
    parent::__construct();
  }

  function segments() {
    return $this->has_many_through(
      __NAMESPACE__.'\Segment',
      __NAMESPACE__.'\NewsletterSegment',
      'newsletter_id',
      'segment_id'
    );
  }

  function options() {
    return $this->has_many_through(
      __NAMESPACE__.'\NewsletterOptionField',
      __NAMESPACE__.'\NewsletterOption',
      'newsletter_id',
      'option_field_id'
    )->select_expr(MP_NEWSLETTER_OPTIONS_TABLE.'.value');
  }

  static function search($orm, $search = '') {
    return $orm->where_like('subject', '%' . $search . '%');
  }

  static function filters() {
    $segments = Segment::orderByAsc('name')->findMany();
    $segment_list = array();
    $segment_list[] = array(
      'label' => __('All lists'),
      'value' => ''
    );

    foreach($segments as $segment) {
      $newsletters_count = $segment->newsletters()->count();
      if($newsletters_count > 0) {
        $segment_list[] = array(
          'label' => sprintf('%s (%d)', $segment->name, $newsletters_count),
          'value' => $segment->id()
        );
      }
    }

    $filters = array(
      'segment' => $segment_list
    );

    return $filters;
  }

  static function filterBy($orm, $filters = null) {
   if(empty($filters)) {
      return $orm;
    }
    foreach($filters as $key => $value) {
      if($key === 'segment') {
        $segment = Segment::findOne($value);
        if($segment !== false) {
          $orm = $segment->newsletters();
        }
      }
    }
    return $orm;
  }

  static function filterWithOptions($orm) {
    $orm = $orm->select(MP_NEWSLETTERS_TABLE.'.*');
    $optionFields = NewsletterOptionField::findArray();
    foreach ($optionFields as $optionField) {
      $orm = $orm->select_expr(
        'IFNULL(GROUP_CONCAT(CASE WHEN ' .
        MP_NEWSLETTER_OPTION_FIELDS_TABLE . '.id=' . $optionField['id'] . ' THEN ' .
        MP_NEWSLETTER_OPTION_TABLE . '.value END), NULL) as "' . $optionField['name'].'"');
    }
    $orm = $orm
      ->left_outer_join(
        MP_NEWSLETTER_OPTION_TABLE,
        array(MP_NEWSLETTERS_TABLE.'.id', '=',
          MP_NEWSLETTER_OPTION_TABLE.'.newsletter_id'))
      ->left_outer_join(
        MP_NEWSLETTER_OPTION_FIELDS_TABLE,
        array(MP_NEWSLETTER_OPTION_FIELDS_TABLE.'.id','=',
          MP_NEWSLETTER_OPTION_TABLE.'.option_field_id'))
      ->group_by(MP_NEWSLETTERS_TABLE.'.id');
    return $orm;
  }

  static function groups() {
    return array(
      array(
        'name' => 'all',
        'label' => __('All'),
        'count' => Newsletter::whereNull('deleted_at')->count()
      ),
      array(
        'name' => 'trash',
        'label' => __('Trash'),
        'count' => Newsletter::whereNotNull('deleted_at')->count()
      )
    );
  }

  static function groupBy($orm, $group = null) {
    if($group === 'trash') {
      return $orm->whereNotNull('deleted_at');
    }
    return $orm->whereNull('deleted_at');
  }

  static function createOrUpdate($data = array()) {
    $newsletter = false;

    if(isset($data['id']) && (int) $data['id'] > 0) {
      $newsletter = self::findOne((int) $data['id']);
    }

    if($newsletter === false) {
      $newsletter = self::create();
      $newsletter->hydrate($data);
    } else {
      unset($data['id']);
      $newsletter->set($data);
    }

    $saved = $newsletter->save();

    if($saved === true) {
      return $newsletter->id();
    } else {
      $errors = $newsletter->getValidationErrors();
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
      $newsletters = $listing->getSelection()->findResultSet();

      if(!empty($newsletters)) {
        $newsletters_count = 0;
        foreach($newsletters as $newsletter) {
          if($newsletter->delete()) {
            $newsletters_count++;
          }
        }
        return array(
          'newsletters' => $newsletters_count
        );
      }
      return false;
    } else {
      // soft delete
      $newsletters = $listing->getSelection()
        ->findResultSet()
        ->set_expr('deleted_at', 'NOW()')
        ->save();

      return array(
        'newsletters' => $newsletters->count()
      );
    }
  }

  static function restore($listing, $data = array()) {
    $newsletters = $listing->getSelection()
      ->findResultSet()
      ->set_expr('deleted_at', 'NULL')
      ->save();

    return array(
      'newsletters' => $newsletters->count()
    );
  }
}
