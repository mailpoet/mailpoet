<?php
namespace MailPoet\Settings;

use Composer\Package\Package;
use MailPoet\Models\Setting;

class SettingsController {
  private static $loaded = false;

  private static $settings = [];

  function get($key, $default = null) {
    $this->ensureLoaded();
    $keys = explode('.', $key);
    $setting = self::$settings;
    if($default === null) {
      $default = $this->getDefault($keys);
    }
    foreach($keys as $key) {
      if(array_key_exists($key, $setting)) {
        $setting = $setting[$key];
      } else {
        return $default;
      }
    }
    if(is_array($setting) && is_array($default)) {
      return array_replace_recursive($default, $setting);
    }
    return $setting;
  }

  function getDefaults() {
    return Setting::getDefaults();
  }

  /**
   * Fetches the value from DB and update in cache
   * This is required for sync settings between parallel processes e.g. cron
   */
  function fetch($key, $default = null) {
    $keys = explode('.', $key);
    $main_key = $keys[0];
    self::$settings[$main_key] = Setting::getValue($main_key);
    return $this->get($key, $default);
  }

  function getAll() {
    $this->ensureLoaded();
    return self::$settings;
  }

  function set($key, $value) {
    $this->ensureLoaded();
    $keys = explode('.', $key);
    $main_key = $keys[0];
    $last_key = array_pop($keys);
    $settings = self::$settings;
    $setting =& $settings;
    foreach($keys as $key) {
      $setting =& $setting[$key];
      if(!is_array($setting)) {
        $setting = [];
      }
    }
    $setting[$last_key] = $value;
    Setting::setValue($main_key, $settings[$main_key]);
    self::$settings = $settings;
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

  private function getDefault($keys) {
    $default = $this->getDefaults();
    foreach($keys as $key) {
      if(array_key_exists($key, $default)) {
        $default = $default[$key];
      } else {
        return null;
      }
    }
    return $default;
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
