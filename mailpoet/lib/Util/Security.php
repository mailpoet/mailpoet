<?php

namespace MailPoet\Util;

use Exception;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\Functions as WPFunctions;

class Security {
  const HASH_LENGTH = 12;
  const UNSUBSCRIBE_TOKEN_LENGTH = 15;
  const UNIQUE_ACTION_PREFIX = 'mailpoetUniqueAction_';

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    SubscribersRepository $subscribersRepository
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->subscribersRepository = $subscribersRepository;
  }

  public static function generateToken($action = 'mailpoet_token') {
    return WPFunctions::get()->wpCreateNonce($action);
  }

  public static function generateTokenForUniqueAction(string $action) {
    return self::generateToken(self::UNIQUE_ACTION_PREFIX . $action);
  }

  public static function getUniqueAction(string $action) {
    return self::UNIQUE_ACTION_PREFIX . $action;
  }

  public static function isActionUnique(string $action): bool {
    return strpos($action, self::UNIQUE_ACTION_PREFIX) === 0;
  }

  public static function checkTokenForUniqueAction(string $token, string $action): bool {
    return self::checkToken($token, self::getUniqueAction($action));
  }

  public static function checkToken(string $token, string $action = 'mailpoet_token'): bool {
    $isNonceValid = WPFunctions::get()->wpVerifyNonce($token, $action);

    if (!$isNonceValid || !self::isActionUnique($action)) {
      return $isNonceValid;
    }

    // Nonces that were generated for a unique action should truly only ever be used once
    $transientName = 'mailpoetUsedNonce_' . $token;
    $hasNonceAlreadyBeenUsed = WPFunctions::get()->getTransient($transientName);

    if ($hasNonceAlreadyBeenUsed) {
      return false;
    }

    // The default nonce life from WordPress uses DAY_IN_SECONDS (86400)
    $expiration = WPFunctions::get()->applyFilters('nonce_life', 86400);
    WPFunctions::get()->setTransient($transientName, true, $expiration);

    return true;
  }

  /**
   * Generate random lowercase alphanumeric string.
   * 1 lowercase alphanumeric character = 6 bits (because log2(36) = 5.17)
   * So 3 bytes = 4 characters
   * @param int $length Minimal length is 5
   * @return string
   */
  public static function generateRandomString($length = 5): string {
    $length = max(5, (int)$length);
    $string = base_convert(
      bin2hex(
        random_bytes( // phpcs:ignore
          (int)ceil(3 * $length / 4)
        )
      ),
      16,
      36
    );
    $result = substr($string, 0, $length);
    if (strlen($result) === $length) return $result;
    // in very rare occasions we generate a shorter string when random_bytes generates something starting with 0 let's try again
    return self::generateRandomString($length);
  }

  /**
   * @param int $length Maximal length is 32
   * @return string
   */
  public static function generateHash($length = null) {
    $length = ($length) ? $length : self::HASH_LENGTH;
    $authKey = self::generateRandomString(64);
    if (defined('AUTH_KEY')) {
      $authKey = AUTH_KEY;
    }
    return substr(
      hash_hmac('sha512', self::generateRandomString(64), $authKey),
      0,
      $length
    );
  }

  static public function generateUnsubscribeToken($model) {
    do {
      $token = self::generateRandomString(self::UNSUBSCRIBE_TOKEN_LENGTH);
      $found = $model::whereEqual('unsubscribe_token', $token)->count();
    } while ($found > 0);
    return $token;
  }

  public function generateUnsubscribeTokenByEntity($entity): string {
    $repository = null;
    if ($entity instanceof NewsletterEntity) {
      $repository = $this->newslettersRepository;
    } elseif ($entity instanceof SubscriberEntity) {
      $repository = $this->subscribersRepository;
    } else {
      throw new Exception('Unsupported Entity type');
    }

    do {
      $token = self::generateRandomString(self::UNSUBSCRIBE_TOKEN_LENGTH);
      $found = count($repository->findBy(['unsubscribeToken' => $token]));
    } while ($found > 0);
    return $token;
  }
}
