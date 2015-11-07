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

  static function updateMultiple($subscribers) {
    self::createMultiple($subscribers);
    self::rawExecute(
      'UPDATE `' . self::$_table . '` ' .
      'SET value = ' .
      '(CASE ' .
      str_repeat(
        'WHEN custom_field_id = ? AND subscriber_id = ? THEN ? ',
        count($subscribers)
      ) .
      'END) ' .
      'WHERE subscriber_id IN (' .
      implode(', ', Helpers::arrayColumn($subscribers, 1)) .
      ') AND custom_field_id IN (' .
      implode(', ', array_unique(Helpers::arrayColumn($subscribers, 0)))
      . ') ',
      Helpers::flattenArray($subscribers)
    );
  }
}