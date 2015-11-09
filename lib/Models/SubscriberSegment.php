<?php
namespace MailPoet\Models;

use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SubscriberSegment extends Model {
  public static $_table = MP_SUBSCRIBER_SEGMENT_TABLE;

  function __construct() {
    parent::__construct();
  }

  static function createMultiple($segmnets, $subscribers) {
    $values = Helpers::flattenArray(
      array_map(function ($segment) use ($subscribers) {
        return array_map(function ($subscriber) use ($segment) {
          return array(
            $segment,
            $subscriber
          );
        }, $subscribers);
      }, $segmnets)
    );
    return self::rawExecute(
      'INSERT IGNORE INTO `' . self::$_table . '` ' .
      '(segment_id, subscriber_id) ' .
      'VALUES ' . rtrim(
        str_repeat(
          '(?, ?), ', count($subscribers) * count($segmnets)), ', '
      ),
      $values
    );
  }
}