<?php
namespace MailPoet\Models;

use MailPoet\Util\Helpers;
use function MailPoet\Util\array_column;

if (!defined('ABSPATH')) exit;

/**
 * @property int $subscriber_id
 * @property int $custom_field_id
 * @property string $value
 */
class SubscriberCustomField extends Model {
  public static $_table = MP_SUBSCRIBER_CUSTOM_FIELD_TABLE;

  static function createOrUpdate($data = []) {
    $custom_field = CustomField::findOne($data['custom_field_id']);
    if ($custom_field instanceof CustomField) {
      $custom_field = $custom_field->asArray();
    } else {
      return false;
    }

    if ($custom_field['type'] === 'date') {
      if (is_array($data['value'])) {
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

    return parent::_createOrUpdate($data, [
      'custom_field_id' => $data['custom_field_id'],
      'subscriber_id' => $data['subscriber_id'],
    ]);
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
    $subscriber_ids = array_unique(array_column($values, 1));
    $query = sprintf(
      "UPDATE `%s` SET value = (CASE %s ELSE value END) WHERE subscriber_id IN (%s)",
      self::$_table,
      str_repeat('WHEN custom_field_id = ? AND subscriber_id = ? THEN ? ', count($values)),
      implode(',', $subscriber_ids)
    );
    self::rawExecute(
      $query,
      Helpers::flattenArray($values)
    );
  }

  static function deleteSubscriberRelations($subscriber) {
    if ($subscriber === false) return false;
    $relations = self::where('subscriber_id', $subscriber->id);
    return $relations->deleteMany();
  }

  static function deleteManySubscriberRelations(array $subscriber_ids) {
    if (empty($subscriber_ids)) return false;
    $relations = self::whereIn('subscriber_id', $subscriber_ids);
    return $relations->deleteMany();
  }
}
