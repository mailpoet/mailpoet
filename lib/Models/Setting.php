<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class Setting extends Model {
  public static $_table = MP_SETTINGS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', array(
      'required' => 'name_is_blank',
      'isString' => 'name_is_not_string'
    ));
  }

  public static function getValue($key, $default = null) {
    $keys = explode('.', $key);

    if(count($keys) === 1) {
      $setting = Setting::where('name', $key)->findOne();
      if($setting === false) {
        return $default;
      } else {
        if(is_serialized($setting->value)) {
          return unserialize($setting->value);
        } else {
          return $setting->value;
        }
      }
    } else {
      $main_key = array_shift($keys);

      $setting = static::getValue($main_key, $default);

      if($setting !== $default) {
        for($i = 0, $count = count($keys); $i < $count; $i++) {
          if(array_key_exists($keys[$i], $setting)) {
            $setting = $setting[$keys[$i]];
          } else {
            return $default;
          }
        }
      }
      return $setting;
    }
  }

  public static function setValue($key, $value) {
    if(is_array($value)) {
      $value = serialize($value);
    }

    return Setting::createOrUpdate(array(
      'name' => $key,
      'value' => $value
    ));
  }

  public static function getAll() {
    $settingsCollection = self::findMany();
    $settings = array();
    if(!empty($settingsCollection)) {
      foreach($settingsCollection as $setting) {
        $value = (is_serialized($setting->value)
          ? unserialize($setting->value)
          : $setting->value
        );
        $settings[$setting->name] = $value;
      }
    }
    return $settings;
  }

  public static function createOrUpdate($model) {
    $exists = self::where('name', $model['name'])
      ->find_one();

    if($exists === false) {
      $new_model = self::create();
      $new_model->hydrate($model);
      return $new_model->save();
    }

    $exists->value = $model['value'];
    return $exists->save();
  }
}
