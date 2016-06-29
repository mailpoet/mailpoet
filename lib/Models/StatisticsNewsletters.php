<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class StatisticsNewsletters extends Model {
  public static $_table = MP_STATISTICS_NEWSLETTERS_TABLE;

  static function createMultiple(array $data) {
    $values = array();
    foreach($data as $value) {
      if(!empty($value['newsletter_id']) &&
         !empty($value['subscriber_id']) &&
         !empty($value['queue_id'])
      ) {
        $values[] = $value['newsletter_id'];
        $values[] = $value['subscriber_id'];
        $values[] = $value['queue_id'];
      }
    }
    if (!count($values)) return false;
    return self::rawExecute(
      'INSERT INTO `' . self::$_table . '` ' .
      '(newsletter_id, subscriber_id, queue_id) ' .
      'VALUES ' . rtrim(
        str_repeat('(?,?,?), ', count($values) / 3),
        ', '
      ),
      $values
    );
  }
}
