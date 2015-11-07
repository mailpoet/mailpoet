<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class CustomField extends Model {
  public static $_table = MP_CUSTOM_FIELDS_TABLE;

  function __construct() {
    parent::__construct();
    $this->addValidations('name', array(
      'required' => __('You need to specify a name.')
    ));
    $this->addValidations('type', array(
      'required' => __('You need to specify a type.')
    ));
  }

  function asArray() {
    $model = parent::asArray();

    $model['params'] = (
      is_serialized($this->params)
      ? unserialize($this->params)
      : $this->params
    );

    return $model;
  }

  function save() {
    $this->set('params', (
      is_serialized($this->params)
      ? $this->params
      : serialize($this->params)
    ));
    return parent::save();
  }

  function subscribers() {
    return $this->hasManyThrough(
      __NAMESPACE__ . '\Subscriber',
      __NAMESPACE__ . '\SubscriberCustomField',
      'custom_field_id',
      'subscriber_id'
    )->selectExpr(MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.value');
  }

  static function createOrUpdate($data = array()) {
    $custom_field = false;

    if(isset($data['id']) && (int)$data['id'] > 0) {
      $custom_field = self::findOne((int)$data['id']);
    }

    // set name as label by default
    if(empty($data['params']['label'])) {
      $data['params']['label'] = $data['name'];
    }

    if($custom_field === false) {
      $custom_field = self::create();
      $custom_field->hydrate($data);
    } else {
      unset($data['id']);
      $custom_field->set($data);
    }

    try {
      $custom_field->save();
      return $custom_field;
    } catch(Exception $e) {
      return $custom_field->getValidationErrors();
    }
    return false;
  }
}