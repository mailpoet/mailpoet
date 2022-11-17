<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use PHPUnit\Framework\Assert;
use WP_User;

class User {
  public function createUser($name, $role, $email): WP_User {
    if (is_multisite()) {
      $userId = wpmu_create_user($name, "$name-password", $email) ;
    } else {
      $userId = wp_create_user($name, "$name-password", $email);
    }
    Assert::assertIsNumeric($userId);
    $user = get_user_by('ID', $userId);
    Assert::assertInstanceOf(WP_User::class, $user);
    foreach ($user->roles as $defaultRole) {
      $user->remove_role($defaultRole);
    }
    $user->add_role($role);
    return $user;
  }
}
