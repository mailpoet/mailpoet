<?php
namespace MailPoet\Settings;

use MailPoet\Cron\CronTrigger;
use MailPoet\Models\Setting;
use MailPoet\Util\Helpers;

class SettingsController {

  const DEFAULT_SENDING_METHOD_GROUP = 'website';
  const DEFAULT_SENDING_METHOD = 'PHPMail';
  const DEFAULT_SENDING_FREQUENCY_EMAILS = 25;
  const DEFAULT_SENDING_FREQUENCY_INTERVAL = 5; // in minutes

  private static $loaded = false;

  private static $settings = [];

  private $defaults = null;

  function get($key, $default = null) {
    $this->ensureLoaded();
    $key_parts = explode('.', $key);
    $setting = self::$settings;
    if($default === null) {
      $default = $this->getDefaultValue($key_parts);
    }
    foreach($key_parts as $key_part) {
      if(array_key_exists($key_part, $setting)) {
        $setting = $setting[$key_part];
      } else {
        return $default;
      }
    }
    if(is_array($setting) && is_array($default)) {
      return array_replace_recursive($default, $setting);
    }
    return $setting;
  }

  function getAllDefaults() {
    if($this->defaults === null) {
      $this->defaults = [
        'mta_group' => self::DEFAULT_SENDING_METHOD_GROUP,
        'mta' => array(
          'method' => self::DEFAULT_SENDING_METHOD,
          'frequency' => array(
            'emails' => self::DEFAULT_SENDING_FREQUENCY_EMAILS,
            'interval' => self::DEFAULT_SENDING_FREQUENCY_INTERVAL
          )
        ),
        CronTrigger::SETTING_NAME => [
          'method' => CronTrigger::DEFAULT_METHOD
        ],
        'signup_confirmation' => [
          'enabled' => true,
          'subject' => sprintf(__('Confirm your subscription to %1$s', 'mailpoet'), get_option('blogname')),
          'body' => __("Hello,\n\nWelcome to our newsletter!\n\nPlease confirm your subscription to the list(s): [lists_to_confirm] by clicking the link below: \n\n[activation_link]Click here to confirm your subscription.[/activation_link]\n\nThank you,\n\nThe Team", 'mailpoet')
        ],
        'tracking' => [
          'enabled' => true
        ],
        'analytics' => [
          'enabled' => false,
        ],
        'in_app_announcements' => [
          'displayed' => []
        ],
        'display_nps_poll' => true,
      ];
    }
    return $this->defaults;
  }

  /**
   * Fetches the value from DB and update in cache
   * This is required for sync settings between parallel processes e.g. cron
   */
  function fetch($key, $default = null) {
    $keys = explode('.', $key);
    $main_key = $keys[0];
    self::$settings[$main_key] = $this->fetchValue($main_key);
    return $this->get($key, $default);
  }

  function getAll() {
    $this->ensureLoaded();
    return array_replace_recursive($this->getAllDefaults(), self::$settings);
  }

  function set($key, $value) {
    $this->ensureLoaded();
    $key_parts = explode('.', $key);
    $main_key = $key_parts[0];
    $last_key = array_pop($key_parts);
    $setting =& self::$settings;
    foreach($key_parts as $key_part) {
      $setting =& $setting[$key_part];
      if(!is_array($setting)) {
        $setting = [];
      }
    }
    $setting[$last_key] = $value;
    $this->saveValue($main_key, self::$settings[$main_key]);
  }

  function delete($key) {
    Setting::deleteValue($key);
    unset(self::$settings[$key]);
  }

  private function ensureLoaded() {
    if(self::$loaded) {
      return;
    }
    self::$settings = Setting::getAll() ?: [];
    self::$loaded = true;
  }

  private function getDefaultValue($keys) {
    $default = $this->getAllDefaults();
    foreach($keys as $key) {
      if(array_key_exists($key, $default)) {
        $default = $default[$key];
      } else {
        return null;
      }
    }
    return $default;
  }

  private function fetchValue($key) {
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

  private function saveValue($key, $value) {
    $value = Helpers::recursiveTrim($value);
    if(is_array($value)) {
      $value = serialize($value);
    }

    $setting = Setting::createOrUpdate([
      'name' => $key,
      'value' => $value,
    ]);
    return ($setting->id() > 0 && $setting->getErrors() === false);
  }

  /**
   * Temporary function for tests use only.
   * It is needed until this is only instantiated in one place (DI Container)
   * Once this is achieved we can make properties not static and remove this method
   */
  static function resetCache() {
    self::$settings = [];
    self::$loaded = false;
  }
}
