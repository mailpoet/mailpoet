<?php
namespace MailPoet\Models;

use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SubscriberSegment extends Model {
  public static $_table = MP_SUBSCRIBER_SEGMENT_TABLE;

  function __construct() {
    parent::__construct();
  }

  static function filterWithCustomFields($orm) {
    $orm = $orm->select(MP_SUBSCRIBERS_TABLE.'.*');
    $customFields = CustomField::findArray();
    foreach ($customFields as $customField) {
      $orm = $orm->select_expr(
        'CASE WHEN ' .
        MP_CUSTOM_FIELDS_TABLE . '.id=' . $customField['id'] . ' THEN ' .
        MP_SUBSCRIBER_CUSTOM_FIELD_TABLE . '.value END as "' . $customField['name'].'"');
    }
    $orm = $orm
      ->left_outer_join(
        MP_SUBSCRIBER_CUSTOM_FIELD_TABLE,
        array(MP_SUBSCRIBERS_TABLE.'.id', '=',
          MP_SUBSCRIBER_CUSTOM_FIELD_TABLE.'.subscriber_id'))
      ->left_outer_join(
        MP_CUSTOM_FIELDS_TABLE,
        array(MP_CUSTOM_FIELDS_TABLE.'.id','=',
          MP_SUBSCRIBER_CUSTOM_FIELD_TABLE.'.custom_field_id'));
    return $orm;
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