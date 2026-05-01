<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;

class LinkTokens {
  private const OBSOLETE_LINK_TOKEN_LENGTH = 6;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    SubscribersRepository $subscribersRepository
  ) {
    $this->subscribersRepository = $subscribersRepository;
  }

  public function getToken(SubscriberEntity $subscriber): string {
    if ($subscriber->getLinkToken() === null) {
      $subscriber->setLinkToken($this->generateToken($subscriber->getEmail()));
      $this->subscribersRepository->flush();
    }
    return (string)$subscriber->getLinkToken();
  }

  public function verifyToken(SubscriberEntity $subscriber, string $token) {
    $databaseToken = $this->getToken($subscriber);
    // Fail closed: an empty stored token means the subscriber has no
    // generatable token (e.g. missing email). hash_equals('', substr($x, 0, 0))
    // would otherwise accept any input here.
    if ($databaseToken === '') {
      return false;
    }
    $requestToken = substr($token, 0, strlen($databaseToken));
    return hash_equals($databaseToken, $requestToken);
  }

  /**
   * Only for backward compatibility for old tokens
   */
  private function generateToken(?string $email, int $length = self::OBSOLETE_LINK_TOKEN_LENGTH): ?string {
    if ($email === null || $email === '') {
      return null;
    }
    $authKey = '';
    if (defined('AUTH_KEY')) {
      $authKey = AUTH_KEY;
    }
    $token = substr(md5((string)$authKey . $email), 0, $length);
    return is_string($token) && $token !== '' ? $token : null;
  }
}
