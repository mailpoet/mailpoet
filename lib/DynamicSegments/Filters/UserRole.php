<?php

namespace MailPoet\Premium\DynamicSegments\Filters;

class UserRole implements Filter {

  const SEGMENT_TYPE = 'userRole';

  /** @var string */
  private $role;

  /** @var string */
  private $connect;

  /**
   * @param string $role
   * @param string $connect
   */
  public function __construct($role, $connect = null) {
    $this->role = $role;
    $this->connect = $connect;
  }

  function toSql(\ORM $orm) {
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

  function getRole() {
    return $this->role;
  }
}