<?php

require_once(ABSPATH . 'wp-admin/includes/user.php');
require_once(ABSPATH . 'wp-admin/includes/ms.php');

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
*/
// phpcs:ignore PSR1.Classes.ClassDeclaration
class IntegrationTester extends \Codeception\Actor {
  use _generated\IntegrationTesterActions;

  public function createWordPressUser(string $email, string $role) {
    return wp_insert_user([
      'user_login' => explode('@', $email)[0],
      'user_email' => $email,
      'role' => $role,
      'user_pass' => '12123154',
    ]);
  }

  public function deleteWordPressUser(string $email) {
    $user = get_user_by('email', $email);
    if (!$user) {
      return;
    }
    if (is_multisite()) {
      wpmu_delete_user($user->ID);
    } else {
      wp_delete_user($user->ID);
    }
  }
}
