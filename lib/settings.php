<?php
namespace MailPoet;

final class Settings {
  // settings cache
  private static $_settings = null;
  // settings key
  const OPTION_KEY = 'mailpoet_settings';

  // load settings if not cached already
  public static function load() {
    if(static::$_settings === null) {
      // load settings
      $settings = get_option(static::OPTION_KEY, array());

      // check if settings are empty
      if(empty($settings)) {
        // load defaults
        static::$_settings = static::getDefaults();
      } else {
        static::$_settings = $settings;
      }
    }
  }

  // save settings
  public static function save($settings = null) {
    $is_new = empty(static::$_settings);

    if($settings !== null) {
      static::$_settings = array_merge(static::$_settings, $settings);
    }

    // save settings (autoload)
    if($is_new) {
      add_option(static::OPTION_KEY, static::$_settings, '', 'yes');
    } else {
      update_option(static::OPTION_KEY, static::$_settings);
    }
  }

  // get all values
  public static function getAll() {
    static::load();
    return static::$_settings;
  }
  // get value
  public static function get($key, $default = null) {
    // check if specified key exists
    if(array_key_exists($key, static::$_settings)) {
      // return stored value
      return static::$_settings[$key];
    } else {
      // return default value
      return $default;
    }
  }

  // set value
  public static function set($key, $value = null) {
    if($value === null) {
      // unset value
      unset(static::$_settings[$key]);
    } else {
      // set value
      static::$_settings[$key] = $value;
    }
  }

  public static function clearAll() {
    // delete WP option
    delete_option(static::OPTION_KEY);
    // reset settings
    static::$_settings = null;
  }

  // default values
  public static function getDefaults() {
    $defaults = array(
      'signup_confirmation' => true,
      'signup_confirmation_page' => '', // select mailpoet page by default
      'subscription_edit_page' => '', // select mailpoet page by default
      'mta_method' => 'website',
      'mta_local_method' => 'mail',
      'mta_frequency_emails' => 25,
      'mta_frequency_interval' => 15,
      'mta_smtp_authenticate' => true, // enable SMTP authentication by default
      'bounce_frequency_interval' => 60,
      'analytics' => false,
      'newsletter_charset' => 'UTF-8',
      'debug' => false,
    );
    return $defaults;
  }
}