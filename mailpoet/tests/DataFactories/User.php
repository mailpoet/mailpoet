<?php

namespace MailPoet\Test\DataFactories;

use WP_User;

class User {
  public function createUser($name, $role, $email): WP_User {
    if (is_multisite()) {
      $userId = wpmu_create_user($name, "$name-password", $email) ;
    } else {
      $userId = wp_create_user($name, "$name-password", $email);
    }
    assert(is_int($userId));
    $user = get_user_by('ID', $userId);
    assert($user instanceof WP_User);
    foreach ($user->roles as $defaultRole) {
      $user->remove_role($defaultRole);
    }
    $user->add_role($role);
    return $user;
  }
}
