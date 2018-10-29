<?php
namespace MailPoet\Cron\Workers\Bounce;

if(!defined('ABSPATH')) exit;

class BounceTestMockAPI {
  function checkBounces(array $emails) {
    return array_map(
      function ($email) {
        return array(
          'address' => $email,
          'bounce' => preg_match('/(hard|soft)/', $email, $m) ? $m[1] : null,
        );
      },
      $emails
    );
  }
}
