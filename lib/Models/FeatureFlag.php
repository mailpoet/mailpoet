<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

/**
 * @property string $name
 * @property bool $value
 */
class FeatureFlag extends Model {
  public static $_table = MP_FEATURE_FLAGS_TABLE;

  static function createOrUpdate($data = []) {
    $keys = false;
    if (isset($data['name'])) {
      $keys = [
        'name' => $data['name'],
      ];
    }
    return parent::_createOrUpdate($data, $keys);
  }
}
