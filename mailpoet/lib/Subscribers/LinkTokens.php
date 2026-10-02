<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;

class LinkTokens {
  /**
   * Upgraded tokens keep the obsolete 6-character token as a prefix followed by this separator,
   * so links sent with the obsolete token keep working until the grace period ends.
   */
  public const UPGRADED_TOKEN_SEPARATOR = '-';
  public const OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING = 'obsolete_link_tokens_accepted_until';
  private const OBSOLETE_TOKENS_GRACE_PERIOD_MONTHS = 12;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    SubscribersRepository $subscribersRepository,
    SettingsController $settings
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->settings = $settings;
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
    if ($this->isUpgradedToken($databaseToken) && $this->acceptsObsoleteTokens()) {
      $databaseToken = substr($databaseToken, 0, SubscriberEntity::OBSOLETE_LINK_TOKEN_LENGTH);
    }
    $requestToken = substr($token, 0, strlen($databaseToken));
    return hash_equals($databaseToken, $requestToken);
  }

  public function startObsoleteTokensGracePeriod(): void {
    if ($this->settings->hasSavedValue(self::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING)) {
      return;
    }
    $acceptedUntil = Carbon::now()->addMonths(self::OBSOLETE_TOKENS_GRACE_PERIOD_MONTHS)->getTimestamp();
    $this->settings->set(self::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING, $acceptedUntil);
  }

  private function generateToken(): string {
    return bin2hex(random_bytes(SubscriberEntity::LINK_TOKEN_LENGTH / 2));
  }

  private function isUpgradedToken(string $token): bool {
    return substr($token, SubscriberEntity::OBSOLETE_LINK_TOKEN_LENGTH, 1) === self::UPGRADED_TOKEN_SEPARATOR;
  }

  private function acceptsObsoleteTokens(): bool {
    $acceptedUntil = (int)$this->settings->get(self::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING, 0);
    return Carbon::now()->getTimestamp() < $acceptedUntil;
  }
}
