<?php

namespace MailPoet\Models;

use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @property string $name
 * @property string|null $value
 */
class Setting extends Model {
  public static $_table = MP_SETTINGS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', [
      'required' => WPFunctions::get()->__('Please specify a name.', 'mailpoet'),
    ]);
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
    $settings = SettingsController::getInstance();
    if (empty($sender_address) || empty($sender_name) || $settings->get('sender')) {
      return;
    }
    $settings->set('sender', [
      'address' => $sender_address,
      'name' => $sender_name,
    ]);
  }
}
