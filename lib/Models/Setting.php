<?php
namespace MailPoet\Models;

use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * @property string $name
 * @property string|null $value
 */
class Setting extends Model {
  public static $_table = MP_SETTINGS_TABLE;

  const DEFAULT_SENDING_METHOD_GROUP = 'website';
  const DEFAULT_SENDING_METHOD = 'PHPMail';
  const DEFAULT_SENDING_FREQUENCY_EMAILS = 25;
  const DEFAULT_SENDING_FREQUENCY_INTERVAL = 5; // in minutes

  function __construct() {
    parent::__construct();

    $this->addValidations('name', [
      'required' => WPFunctions::get()->__('Please specify a name.', 'mailpoet'),
    ]);
  }

  /**
   * This method is here only for BC fix of 3rd party plugin hacky integration
   * @deprecated
   */
  public static function getValue($key, $default = null) {
    trigger_error('Calling Settings::getValue() is deprecated and will be removed. Use \MailPoet\Settings\SettingsController instead.', E_USER_DEPRECATED);
    $settings = new SettingsController();
    return $settings->get($key, $default);
  }

  public static function getAll() {
    $settingsCollection = self::findMany();
    $settings = [];
    if (!empty($settingsCollection)) {
      foreach ($settingsCollection as $setting) {
        $value = (is_serialized($setting->value)
          ? unserialize($setting->value)
          : $setting->value
        );
        $settings[$setting->name] = $value;
      }
    }
    return $settings;
  }

  public static function createOrUpdate($data = []) {
    $keys = isset($data['name']) ? ['name' => $data['name']] : false;
    return parent::_createOrUpdate($data, $keys);
  }

  public static function deleteValue($value) {
    $value = self::where('name', $value)->findOne();
    return ($value) ? $value->delete() : false;
  }

  public static function saveDefaultSenderIfNeeded($sender_address, $sender_name) {
    $settings = new SettingsController();
    if (empty($sender_address) || empty($sender_name) || $settings->get('sender')) {
      return;
    }
    $settings->set('sender', [
      'address' => $sender_address,
      'name' => $sender_name,
    ]);
  }
}
