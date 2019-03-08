<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

/**
 * @property int $user_id
 * @property string $name
 * @property string $value
 */
class UserFlag extends Model {
  public static $_table = MP_USER_FLAGS_TABLE;

  public static function createOrUpdate($data = []) {
    $keys = false;
    if (!empty($data['user_id']) && !empty($data['name'])) {
      $keys = [
        'user_id' => $data['user_id'],
        'name' => $data['name'],
      ];
    }
    return parent::_createOrUpdate($data, $keys);
  }
}
