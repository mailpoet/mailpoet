<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class StatisticsNewsletters extends Model {
  public static $_table = MP_STATISTICS_NEWSLETTERS_TABLE;

  function __construct() {
    parent::__construct();
  }

  static function createMultiple($data) {
    return self::rawExecute(
      'INSERT INTO `' . self::$_table . '` ' .
      '(newsletter_id, subscriber_id, queue_id) ' .
      'VALUES ' . rtrim(
        str_repeat('(?,?,?), ', count($data)/3),
        ', '
      ),
      $data
    );
  }
}