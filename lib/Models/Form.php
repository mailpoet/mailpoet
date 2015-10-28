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

    if($form === false) {
      $form = self::create();
      $form->hydrate($data);
    } else {
      unset($data['id']);
      $form->set($data);
    }

    $saved = $form->save();

    if($saved === true) {
      return true;
    } else {
      $errors = $form->getValidationErrors();
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
      $forms = $listing->getSelection()->findResultSet();
      if(!empty($forms)) {
        $forms_count = 0;
        foreach($forms as $form) {
          if($form->delete()) {
            $forms_count++;
          }
        }
        return array(
          'segments' => $forms_count
        );
      }
      return false;
    } else {
      // soft delete
      $forms = $listing->getSelection()
        ->findResultSet()
        ->set_expr('deleted_at', 'NOW()')
        ->save();

      return array(
        'segments' => $forms->count()
      );
    }
  }

  static function restore($listing, $data = array()) {
    $forms = $listing->getSelection()
      ->findResultSet()
      ->set_expr('deleted_at', 'NULL')
      ->save();

    return array(
      'segments' => $forms->count()
    );
  }
}
