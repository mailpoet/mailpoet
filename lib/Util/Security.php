<?php

namespace MailPoet\Util;

use Exception;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

class Security {
  const HASH_LENGTH = 12;
  const UNSUBSCRIBE_TOKEN_LENGTH = 15;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function __construct(NewslettersRepository $newslettersRepository) {
    $this->newslettersRepository = $newslettersRepository;
  }

  public static function generateToken($action = 'mailpoet_token') {
    return WPFunctions::get()->wpCreateNonce($action);
  }

  /**
   * Generate random lowercase alphanumeric string.
   * 1 lowercase alphanumeric character = 6 bits (because log2(36) = 5.17)
   * So 3 bytes = 4 characters
   * @param int $length Minimal lenght is 5
   * @return string
   */
  public static function generateRandomString($length = 5) {
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
    return substr($string, 0, $length);
  }

  /**
   * @param int $length Maximal length is 32
   * @return string
   */
  public static function generateHash($length = null) {
    $length = ($length) ? $length : self::HASH_LENGTH;
    $authKey = '';
    if (defined('AUTH_KEY')) {
      $authKey = AUTH_KEY;
    }
    return substr(
      md5($authKey . self::generateRandomString(64)),
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
