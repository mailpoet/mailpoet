<?php

namespace MailPoet\Subscribers;

use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

class LinkTokens {
  const OBSOLETE_LINK_TOKEN_LENGTH = 6;
  const LINK_TOKEN_LENGTH = 32;

  public function getToken(Subscriber $subscriber) {
    if ($subscriber->link_token === null) {
      $subscriber->link_token = $this->generateToken($subscriber->email);
      // `$subscriber->save()` fails if the subscriber has subscriptions, segments or custom fields
      ORM::rawExecute(sprintf('UPDATE %s SET link_token = ? WHERE email = ?', Subscriber::$_table), [$subscriber->link_token, $subscriber->email]);
    }
    return $subscriber->link_token;
  }

  public function verifyToken(Subscriber $subscriber, $token) {
    $database_token = $this->getToken($subscriber);
    $request_token = substr($token, 0, strlen($database_token));
    return call_user_func(
      'hash_equals',
      $database_token,
      $request_token
    );
  }

  /**
   * Only for backward compatibility for old tokens
   */
  private function generateToken($email = null, $length = self::OBSOLETE_LINK_TOKEN_LENGTH) {
    if ($email !== null) {
      $auth_key = '';
      if (defined('AUTH_KEY')) {
        $auth_key = AUTH_KEY;
      }
      return substr(md5($auth_key . $email), 0, $length);
    }
    return false;
  }

}
