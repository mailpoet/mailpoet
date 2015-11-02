<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Form extends Model {
  static $_table = MP_FORMS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', array(
      'required' => __('You need to specify a name.')
    ));
  }

  function segments() {
    return $this->has_many_through(
      __NAMESPACE__.'\Segment',
      __NAMESPACE__.'\FormSegment',
      'form_id',
      'segment_id'
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
        'count' => Form::whereNull('deleted_at')->count()
      ),
      array(
        'name' => 'trash',
        'label' => __('Trash'),
        'count' => Form::whereNotNull('deleted_at')->count()
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
    $form = false;

    if(isset($data['id']) && (int)$data['id'] > 0) {
      $form = self::findOne((int)$data['id']);
    }

    if(!empty($data['data'])) {
      $data['data'] = json_encode($data['data']);
    }

    if($form === false) {
      $form = self::create();
      $form->hydrate($data);
    } else {
      unset($data['id']);
      $form->set($data);
    }

    try {
      $form->save();
      return $form;
    } catch(Exception $e) {
      return $form->getValidationErrors();
    }
    return false;
  }

  function duplicate($data = array()) {
    $duplicate = parent::duplicate($data);

    if($duplicate !== false) {
      foreach($this->segments()->findResultSet() as $relation) {
        $new_relation = FormSegment::create();
        $new_relation->set('segment_id', $relation->id);
        $new_relation->set('form_id', $duplicate->id);
        $new_relation->save();
      }

      return $duplicate;
    }
    return false;
  }
}
