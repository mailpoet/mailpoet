<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Models;

use MailPoet\Util\Helpers;

/**
 * @property int $subscriberId
 * @property int $customFieldId
 * @property string $value
 */
class SubscriberCustomField extends Model {
  public static $_table = MP_SUBSCRIBER_CUSTOM_FIELD_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration

  public static function createOrUpdate($data = []) {
    $customField = CustomField::findOne($data['custom_field_id']);
    if ($customField instanceof CustomField) {
      $customField = $customField->asArray();
    } else {
      return false;
    }

    if ($customField['type'] === 'date') {
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

  public static function createMultiple($values) {
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

  public static function updateMultiple($values) {
    $subscriberIds = array_unique(array_column($values, 1));
    $query = sprintf(
      "UPDATE `%s` SET value = (CASE %s ELSE value END) WHERE subscriber_id IN (%s)",
      self::$_table,
      str_repeat('WHEN custom_field_id = ? AND subscriber_id = ? THEN ? ', count($values)),
      implode(',', $subscriberIds)
    );
    self::rawExecute(
      $query,
      Helpers::flattenArray($values)
    );
  }

  public static function deleteSubscriberRelations($subscriber) {
    if ($subscriber === false) return false;
    $relations = self::where('subscriber_id', $subscriber->id);
    return $relations->deleteMany();
  }

  public static function deleteManySubscriberRelations(array $subscriberIds) {
    if (empty($subscriberIds)) return false;
    $relations = self::whereIn('subscriber_id', $subscriberIds);
    return $relations->deleteMany();
  }
}
