<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers\Bounce;

class BounceTestMockAPI {
  /** @var array<int, array<int, string>> */
  public $checkBouncesCalls = [];

  public function checkBounces(array $emails) {
    $this->checkBouncesCalls[] = $emails;
    return array_map(
      function ($email) {
        return [
          'address' => $email,
          'bounce' => preg_match('/(hard|soft)/', $email, $m) ? $m[1] : null,
        ];
      },
      $emails
    );
  }
}
