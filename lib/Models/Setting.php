<?php
namespace MailPoet\Models;

use MailPoet\Cron\CronTrigger;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Setting extends Model {
  public static $_table = MP_SETTINGS_TABLE;

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

  public static function getValue($key) {
    $setting = Setting::where('name', $key)->findOne();
    if($setting === false) {
        return null;
    }
    if(is_serialized($setting->value)) {
      return unserialize($setting->value);
    } else {
      return $setting->value;
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
    return $settings;
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
    $settings = new SettingsController();
    if(empty($sender_address) || empty($sender_name) || $settings->get('sender')) {
      return;
    }
    $settings->set('sender', array(
      'address' => $sender_address,
      'name' => $sender_name
    ));
  }
}
