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

  // generate random users
  public function generateSubscribers($count, $data = []) {
    for ($i = 0; $i < $count; $i++) {
      $this->generateSubscriber($data);
    }
  }

  public function generateSubscriber($data = []) {
    $subscriberData = [
      'email' => sprintf('user%s@mailpoet.com', bin2hex(random_bytes(7))), // phpcs:ignore PHPCompatibility
      'first_name' => $this->generateName(),
      'last_name' => $this->generateName(),
    ];

    $subscriber = \MailPoet\Models\Subscriber::create();
    $subscriber->hydrate(array_merge($subscriberData, $data));
    $subscriber->save();
  }

  protected function generateName() {
    $name = '';
    $length = mt_rand(6, 12);

    $vowels = 'aeiouy';
    $consonants = 'bcdfgjklmnpqrstvwxz';
    $specials = ' \'';
    $alphabet = $consonants . $vowels;
    $charset = $specials . $alphabet;

    // pick first letter in alphabet
    $name .= $alphabet[mt_rand(0, strlen($alphabet) - 1)];

    for ($i = 0; $i < $length; $i++) {
      $name .= $charset[mt_rand(0, strlen($charset) - 1)];
    }

    // pick last letter in alphabet
    $name .= $alphabet[mt_rand(0, strlen($alphabet) - 1)];

    return ucfirst($name);
  }
}
