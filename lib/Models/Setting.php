<?php
namespace MailPoet\Models;

use MailPoet\Cron\CronTrigger;
use MailPoet\Util\Helpers;

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
        'body' => __("Hello,\n\nWelcome to our newsletter!\n\nPlease confirm your subscription to the list(s): [lists_to_confirm] by clicking the link below: \n\n[activation_link]Click here to confirm your subscription.[/activation_link]\n\nThank you,\n\nThe Team", 'mailpoet')
      ),
      'tracking' => array(
        'enabled' => true
      ),
      'analytics' => array(
        'enabled' => false,
      ),
      'in_app_announcements' => [
        'displayed' => []
      ],
      'display_nps_poll' => true,
    );
  }

  public static function getValue($key, $default = null) {
    $defaults = self::getDefaults();
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
  }

  public static function setValue($key, $value) {
    $value = Helpers::recursiveTrim($value);
    if(is_array($value)) {
      $value = serialize($value);
    }

    $setting = Setting::createOrUpdate(array(
      'name' => $key,
      'value' => $value
    ));
    return ($setting->id() > 0 && $setting->getErrors() === false);
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
    $keys = isset($data['name']) ? array('name' => $data['name']) : false;
    return parent::_createOrUpdate($data, $keys);
  }

  public static function deleteValue($value) {
    $value = self::where('name', $value)->findOne();
    return ($value) ? $value->delete() : false;
  }

  public static function saveDefaultSenderIfNeeded($sender_address, $sender_name) {
    if(empty($sender_address) || empty($sender_name) || Setting::getValue('sender')) {
      return;
    }
    Setting::setValue('sender', array(
      'address' => $sender_address,
      'name' => $sender_name
    ));
  }
}
