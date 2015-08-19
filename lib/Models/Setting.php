<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Setting extends Model {
  public static $_table = MP_SETTINGS_TABLE;

  function __construct() {
    parent::__construct();

    $this->addValidations('name', array(
      'required' => 'name_is_blank',
      'isString' => 'name_is_not_string',
      'minLength|2' => 'name_is_short'
    ));
    $this->addValidations('value', array(
      'required' => 'value_is_blank',
      'isString' => 'value_is_not_string',
      'minLength|2' => 'value_is_short'
    ));
  }

  public static function createOrUpdate($orm, $settings) {
    return $orm->raw_execute(
      'INSERT IGNORE INTO `' . self::$_table . '` (`name`, `value`)
      VALUES ' . rtrim(str_repeat('(?, ?), ', count($settings)), ', ') . '
      ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
      call_user_func_array(
        'array_merge_recursive', array_map('array_values', $settings)
      ));
  }
}
