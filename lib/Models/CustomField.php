<?php
namespace MailPoet\Models;

use MailPoet\Form\Block\Date;

if(!defined('ABSPATH')) exit;

class CustomField extends Model {
  public static $_table = MP_CUSTOM_FIELDS_TABLE;
  const TYPE_DATE = 'date';
  const TYPE_TEXT = 'text';
  const TYPE_TEXTAREA = 'textarea';
  const TYPE_RADIO = 'radio';
  const TYPE_CHECKBOX = 'checkbox';
  const TYPE_SELECT = 'select';

  function __construct() {
    parent::__construct();
    $this->addValidations('name', array(
      'required' => __('Please specify a name.', 'mailpoet')
    ));
    $this->addValidations('type', array(
      'required' => __('Please specify a type.', 'mailpoet')
    ));
  }

  function asArray() {
    $model = parent::asArray();

    if(isset($model['params'])) {
      $model['params'] = (is_array($this->params))
        ? $this->params
        : unserialize($this->params);
    }
    return $model;
  }

  function save() {
    if(is_null($this->params)) {
      $this->params = array();
    }
    $this->set('params', (
      is_array($this->params)
      ? serialize($this->params)
      : $this->params
    ));
    return parent::save();
  }

  function formatValue($value = null) {
    // format custom field data depending on type
    if(is_array($value) && $this->type === self::TYPE_DATE) {
      $custom_field_data = $this->asArray();
      $date_format = $custom_field_data['params']['date_format'];
      $date_type = (isset($custom_field_data['params']['date_type'])
        ? $custom_field_data['params']['date_type']
        : 'year_month_day'
      );
      $date_parts = explode('_', $date_type);
      switch($date_type) {
        case 'year_month_day':
          $value = sprintf(
            '%s/%s/%s',
            $value['month'],
            $value['day'],
            $value['year']
          );
          break;

        case 'year_month':
          $value = sprintf(
            '%s/%s',
            $value['month'],
            $value['year']
          );
          break;

        case 'month':
          if((int)$value['month'] === 0) {
            $value = '';
          } else {
            $value = sprintf(
              '%s',
              $value['month']
            );
          }
          break;

        case 'day':
          if((int)$value['day'] === 0) {
            $value = '';
          } else {
            $value = sprintf(
              '%s',
              $value['day']
            );
          }
          break;

        case 'year':
          if((int)$value['year'] === 0) {
            $value = '';
          } else {
            $value = sprintf(
              '%04d',
              $value['year']
            );
          }
          break;
      }

      if(!empty($value)) {
        $value = Date::convertDateToDatetime($value, $date_format);
      }
    }

    return $value;
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
    if(empty($data['params']['label']) && isset($data['name'])) {
      $data['params']['label'] = $data['name'];
    }

    if($custom_field === false) {
      $custom_field = self::create();
      $custom_field->hydrate($data);
    } else {
      unset($data['id']);
      $custom_field->set($data);
    }

    return $custom_field->save();
  }
}
