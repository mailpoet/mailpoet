<?php
namespace MailPoet\Models;

use MailPoet\Cron\CronTrigger;

if(!defined('ABSPATH')) exit;

class Setting extends Model {
  public static $_table = MP_SETTINGS_TABLE;

  public static $defaults = null;

  const DEFAULT_SENDING_METHOD_GROUP = 'website';
  const DEFAULT_SENDING_METHOD = 'PHPMail';
  const DEFAULT_SENDING_FREQUENCY_EMAILS = 25;
  const DEFAULT_SENDING_FREQUENCY_INTERVAL = 5; // in minutes

  function __construct() {
    parent::__construct();

    $this->addValidations('name', array(
      'required' => __('Please specify a name.', 'mailpoet')
    ));
  }

  public static function getDefaults() {
    if(self::$defaults === null) {
      self::loadDefaults();
    }
    return self::$defaults;
  }

  public static function loadDefaults() {
    self::$defaults = array(
      'mta_group' => self::DEFAULT_SENDING_METHOD_GROUP,
      'mta' => array(
        'method' => self::DEFAULT_SENDING_METHOD,
        'frequency' => array(
          'emails' => self::DEFAULT_SENDING_FREQUENCY_EMAILS,
          'interval' => self::DEFAULT_SENDING_FREQUENCY_INTERVAL
        )
      ),
      CronTrigger::SETTING_NAME => array(
        'method' => CronTrigger::DEFAULT_METHOD
      ),
      'signup_confirmation' => array(
        'enabled' => true,
        'subject' => sprintf(__('Confirm your subscription to %1$s', 'mailpoet'), get_option('blogname')),
        'body' => __("Hello!\n\nHurray! You've subscribed to our site.\n\nPlease confirm your subscription to the list(s): [lists_to_confirm] by clicking the link below: \n\n[activation_link]Click here to confirm your subscription.[/activation_link]\n\nThank you,\n\nThe Team", 'mailpoet')
      ),
      'tracking' => array(
        'enabled' => true
      ),
      'analytics' => array(
        'enabled' => false,
      )
    );
  }

  public static function getValue($key, $default = null) {
    $keys = explode('.', $key);
    $defaults = self::getDefaults();

    if(count($keys) === 1) {
      $setting = Setting::where('name', $key)->findOne();
      if($setting === false) {
        if($default === null && array_key_exists($key, $defaults)) {
          return $defaults[$key];
        } else {
          return $default;
        }
      } else {
        if(is_serialized($setting->value)) {
          $value = unserialize($setting->value);
        } else {
          $value = $setting->value;
        }
        if(is_array($value) && array_key_exists($key, $defaults)) {
          return array_replace_recursive($defaults[$key], $value);
        } else {
          return $value;
        }
      }
    } else {
      $main_key = array_shift($keys);

      $setting = static::getValue($main_key, $default);

      if($setting !== $default) {
        for($i = 0, $count = count($keys); $i < $count; $i++) {
          if(!is_array($setting)) {
            $setting = array();
          }
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
    $keys = explode('.', $key);

    if(count($keys) === 1) {
      if(is_array($value)) {
        $value = serialize($value);
      }

      $setting = Setting::createOrUpdate(array(
        'name' => $key,
        'value' => $value
      ));
      return ($setting->id() > 0 && $setting->getErrors() === false);
    } else {
      $main_key = array_shift($keys);

      $setting_value = static::getValue($main_key, array());
      $current_value = &$setting_value;
      $last_key = array_pop($keys);

      foreach($keys as $key) {
        $current_value =& $current_value[$key];
      }
      if(is_scalar($current_value)) {
        $current_value = array();
      }
      $current_value[$last_key] = $value;

      return static::setValue($main_key, $setting_value);
    }
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
    return array_replace_recursive(self::getDefaults(), $settings);
  }

  public static function createOrUpdate($data = array()) {
    $setting = false;

    if(isset($data['name'])) {
      $setting = self::where('name', $data['name'])->findOne();
    }

    if($setting === false) {
      $setting = self::create();
      $setting->hydrate($data);
    } else {
      $setting->value = $data['value'];
    }

    return $setting->save();
  }

  public static function deleteValue($value) {
    $value = self::where('name', $value)->findOne();
    return ($value) ? $value->delete() : false;
  }
}
