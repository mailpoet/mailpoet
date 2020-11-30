<?php

namespace MailPoet\Models;

use MailPoet\Util\DateConverter;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @property string $name
 * @property string $type
 * @property string|array|null $params
 */

class CustomField extends Model {
  public static $_table = MP_CUSTOM_FIELDS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration
  const TYPE_DATE = 'date';
  const TYPE_TEXT = 'text';
  const TYPE_TEXTAREA = 'textarea';
  const TYPE_RADIO = 'radio';
  const TYPE_CHECKBOX = 'checkbox';
  const TYPE_SELECT = 'select';

  public function __construct() {
    parent::__construct();
    $this->addValidations('name', [
      'required' => WPFunctions::get()->__('Please specify a name.', 'mailpoet'),
    ]);
    $this->addValidations('type', [
      'required' => WPFunctions::get()->__('Please specify a type.', 'mailpoet'),
    ]);
  }

  public function asArray() {
    $model = parent::asArray();

    if (isset($model['params'])) {
      $model['params'] = (is_array($this->params))
        ? $this->params
        : ($this->params !== null ? unserialize($this->params) : false);
    }
    return $model;
  }

  public function save() {
    if (is_null($this->params)) {
      $this->params = [];
    }
    $this->set('params', (
      is_array($this->params)
      ? serialize($this->params)
      : $this->params
    ));
    return parent::save();
  }

  public function formatValue($value = null) {
    // format custom field data depending on type
    if (is_array($value) && $this->type === self::TYPE_DATE) {
      $customFieldData = $this->asArray();
      $dateFormat = $customFieldData['params']['date_format'];
      $dateType = (isset($customFieldData['params']['date_type'])
        ? $customFieldData['params']['date_type']
        : 'year_month_day'
      );
      $dateParts = explode('_', $dateType);
      switch ($dateType) {
        case 'year_month_day':
          $value = str_replace(
            ['DD', 'MM', 'YYYY'],
            [$value['day'], $value['month'], $value['year']],
            $dateFormat
          );
          break;

        case 'year_month':
          $value = str_replace(
            ['MM', 'YYYY'],
            [$value['month'], $value['year']],
            $dateFormat
          );
          break;

        case 'month':
          if ((int)$value['month'] === 0) {
            $value = '';
          } else {
            $value = sprintf(
              '%s',
              $value['month']
            );
          }
          break;

        case 'day':
          if ((int)$value['day'] === 0) {
            $value = '';
          } else {
            $value = sprintf(
              '%s',
              $value['day']
            );
          }
          break;

        case 'year':
          if ((int)$value['year'] === 0) {
            $value = '';
          } else {
            $value = sprintf(
              '%04d',
              $value['year']
            );
          }
          break;
      }

      if (!empty($value) && is_string($value)) {
        $value = (new DateConverter())->convertDateToDatetime($value, $dateFormat);
      }
    }

    return $value;
  }

  public function subscribers() {
    return $this->hasManyThrough(
      __NAMESPACE__ . '\Subscriber',
      __NAMESPACE__ . '\SubscriberCustomField',
      'custom_field_id',
      'subscriber_id'
    )->selectExpr(MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.value');
  }

  public static function createOrUpdate($data = []) {
    // set name as label by default
    if (empty($data['params']['label']) && isset($data['name'])) {
      $data['params']['label'] = $data['name'];
    }
    return parent::_createOrUpdate($data);
  }
}
