<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;

class LinkTokens {
  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    SubscribersRepository $subscribersRepository
  ) {
    $this->subscribersRepository = $subscribersRepository;
  }

  public function getToken(SubscriberEntity $subscriber): string {
    if ($subscriber->getLinkToken() === null) {
      $subscriber->setLinkToken($this->generateToken());
      $this->subscribersRepository->flush();
    }
    return (string)$subscriber->getLinkToken();
  }

  public function verifyToken(SubscriberEntity $subscriber, string $token) {
    $databaseToken = $this->getToken($subscriber);
    // Fail closed: an empty stored token would otherwise accept any input,
    // because hash_equals('', substr($x, 0, 0)) is always true.
    if ($databaseToken === '') {
      return false;
    }
    $requestToken = substr($token, 0, strlen($databaseToken));
    return hash_equals($databaseToken, $requestToken);
  }

  private function generateToken(): string {
    return bin2hex(random_bytes(SubscriberEntity::LINK_TOKEN_LENGTH / 2));
  }
}
