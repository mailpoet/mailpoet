<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Form extends Model {
  public static $_table = MP_FORMS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', array(
      'required' => __('Please specify a name.')
    ));
  }

  function asArray() {
    $model = parent::asArray();

    $model['body'] = (is_serialized($this->body))
      ? unserialize($this->body)
      : $this->body;
    $model['settings'] = (is_serialized($this->settings))
      ? unserialize($this->settings)
      : $this->settings;

    return $model;
  }

  function save() {
    $this->set('body', (is_serialized($this->body))
      ? $this->body
      : serialize($this->body)
    );
    $this->set('settings', (is_serialized($this->settings))
      ? $this->settings
      : serialize($this->settings)
    );
    return parent::save();
  }

  static function search($orm, $search = '') {
    return $orm->whereLike('name', '%'.$search.'%');
  }

  static function groups() {
    return array(
      array(
        'name' => 'all',
        'label' => __('All'),
        'count' => Form::getPublished()->count()
      ),
      array(
        'name' => 'trash',
        'label' => __('Trash'),
        'count' => Form::getTrashed()->count()
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

    return $form->save();
  }
}
