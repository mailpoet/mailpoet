<?php
namespace MailPoet\Models;

use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SubscriberCustomField extends Model {
  public static $_table = MP_SUBSCRIBER_CUSTOM_FIELD_TABLE;

  function __construct() {
    parent::__construct();
  }

  static function createMultiple($values) {
    $values = array_map('array_values', $values);
    return self::rawExecute(
      'INSERT IGNORE INTO `' . self::$_table . '` ' .
      '(custom_field_id, subscriber_id, value) ' .
      'VALUES ' . rtrim(
        str_repeat(
          '(?, ?, ?)' . ', '
          , count($values)
        ), ', '
      ),
      Helpers::flattenArray($values)
    );
  }

  static function updateMultiple($values) {
    self::createMultiple($values);
    $values = array_map('array_values', $values);
    self::rawExecute(
      'UPDATE `' . self::$_table . '` ' .
      'SET value = ' .
      '(CASE ' .
      str_repeat(
        'WHEN custom_field_id = ? AND subscriber_id = ? THEN ? ',
        count($values)
      ) .
      'END) ' .
      'WHERE subscriber_id IN (' .
      implode(', ', Helpers::arrayColumn($values, 1)) .
      ') AND custom_field_id IN (' .
      implode(', ', array_unique(Helpers::arrayColumn($values, 0)))
      . ') ',
      Helpers::flattenArray($values)
    );
  }
}