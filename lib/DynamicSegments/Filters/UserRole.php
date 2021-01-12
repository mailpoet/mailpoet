<?php

namespace MailPoet\DynamicSegments\Filters;

use MailPoetVendor\Idiorm\ORM;

class UserRole implements Filter {

  const SEGMENT_TYPE = 'userRole';

  /** @var string */
  private $role;

  /** @var string|null */
  private $connect;

  /**
   * @param string $role
   * @param string|null $connect
   */
  public function __construct($role, $connect = null) {
    $this->role = $role;
    $this->connect = $connect;
  }

  public function toSql(ORM $orm) {
    global $wpdb;
    $orm->join($wpdb->users, ['wpusers.id', '=', MP_SUBSCRIBERS_TABLE . '.wp_user_id'], 'wpusers')
      ->join($wpdb->usermeta, ['wpusers.ID',  '=', 'wpusermeta.user_id'], 'wpusermeta')
      ->whereEqual('wpusermeta.meta_key', $wpdb->prefix . 'capabilities')
      ->whereLike('wpusermeta.meta_value', '%"' . $this->role . '"%');
    return $orm;
  }

  public function toArray() {
    return [
      'wordpressRole' => $this->role,
      'connect' => $this->connect,
      'segmentType' => UserRole::SEGMENT_TYPE,
    ];
  }

  public function getRole() {
    return $this->role;
  }
}
