<?php
namespace MailPoet\Models;

use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class SubscriberCustomField extends Model {
  public static $_table = MP_SUBSCRIBER_CUSTOM_FIELD_TABLE;

  static function createOrUpdate($data = array()) {
    $custom_field = CustomField::findOne($data['custom_field_id']);
    if($custom_field === false) {
      return false;
    } else {
      $custom_field = $custom_field->asArray();
    }

    if($custom_field['type'] === 'date') {
      if(is_array($data['value'])) {
        $day = (
          isset($data['value']['day'])
          ? (int)$data['value']['day']
          : 1
        );
        $month = (
          isset($data['value']['month'])
          ? (int)$data['value']['month']
          : 1
        );
        $year = (
          isset($data['value']['year'])
          ? (int)$data['value']['year']
          : 1970
        );
        $data['value'] = mktime(0, 0, 0, $month, $day, $year);
      }
    }

    $relation = self::where('custom_field_id', $data['custom_field_id'])
      ->where('subscriber_id', $data['subscriber_id'])
      ->findOne();

    if($relation === false) {
      $relation = self::create();
      $relation->hydrate($data);
    } else {
      $relation->set($data);
    }

    return $relation->save();
  }

  static function createMultiple($values) {
    return self::rawExecute(
      'INSERT IGNORE INTO `' . self::$_table . '` ' .
      '(custom_field_id, subscriber_id, value) ' .
      'VALUES ' . rtrim(
        str_repeat(
          '(?, ?, ?)' . ', ',
          count($values)
        ), ', '
      ),
      Helpers::flattenArray($values)
    );
  }

  static function updateMultiple($values) {
    self::rawExecute(
      'UPDATE `' . self::$_table . '` ' .
      'SET value = ' .
      '(CASE ' .
      str_repeat(
        'WHEN custom_field_id = ? AND subscriber_id = ? THEN ? ',
        count($values)
      ) .
      'ELSE value END) ',
      Helpers::flattenArray($values)
    );
  }

  static function deleteSubscriberRelations($subscriber) {
    if($subscriber === false) return false;
    $relations = self::where('subscriber_id', $subscriber->id);
    return $relations->deleteMany();
  }

  static function deleteManySubscriberRelations(array $subscriber_ids) {
    if(empty($subscriber_ids)) return false;
    $relations = self::whereIn('subscriber_id', $subscriber_ids);
    return $relations->deleteMany();
  }
}