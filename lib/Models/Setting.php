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

  public static function hasSignupConfirmation() {
    $signup_confirmation = Setting::getValue('signup_confirmation', array());
    $has_signup_confirmation = true;
    if(array_key_exists('enabled', $signup_confirmation)) {
      $has_signup_confirmation = filter_var(
        $signup_confirmation['enabled'],
        FILTER_VALIDATE_BOOLEAN
      );
    }
    return $has_signup_confirmation;
  }
}
