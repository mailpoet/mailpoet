<?php

namespace MailPoet\Subscribers;

use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

class LinkTokens {
  const OBSOLETE_LINK_TOKEN_LENGTH = 6;
  const LINK_TOKEN_LENGTH = 32;

  public function getToken(Subscriber $subscriber) {
    if ($subscriber->linkToken === null) {
      $subscriber->linkToken = $this->generateToken($subscriber->email);
      // `$subscriber->save()` fails if the subscriber has subscriptions, segments or custom fields
      ORM::rawExecute(sprintf('UPDATE %s SET link_token = ? WHERE email = ?', Subscriber::$_table), [$subscriber->linkToken, $subscriber->email]);
    }
    return $subscriber->linkToken;
  }

  public function verifyToken(Subscriber $subscriber, $token) {
    $databaseToken = $this->getToken($subscriber);
    $requestToken = substr($token, 0, strlen($databaseToken));
    return call_user_func(
      'hash_equals',
      $databaseToken,
      $requestToken
    );
  }

  /**
   * Only for backward compatibility for old tokens
   */
  private function generateToken($email = null, $length = self::OBSOLETE_LINK_TOKEN_LENGTH) {
    if ($email !== null) {
      $authKey = '';
      if (defined('AUTH_KEY')) {
        $authKey = AUTH_KEY;
      }
      return substr(md5($authKey . $email), 0, $length);
    }
    return false;
  }

}
