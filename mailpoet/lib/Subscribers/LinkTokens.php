<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class LinkTokens {
  /**
   * Upgraded tokens keep the obsolete 6-character token as a prefix followed by this separator,
   * so links sent with the obsolete token keep working until the grace period ends.
   */
  public const UPGRADED_TOKEN_SEPARATOR = '-';
  public const OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING = 'obsolete_link_tokens_accepted_until';
  private const OBSOLETE_TOKENS_GRACE_PERIOD_MONTHS = 1;
  /**
   * Once a subscriber reaches this many tokens that don't match the obsolete part, only their whole
   * upgraded token is accepted. Mismatches aren't counted while the limit is reached, so the limit
   * holds for MISMATCH_WINDOW seconds after the last counted mismatch.
   */
  public const MISMATCH_LIMIT = 10;
  private const MISMATCH_WINDOW = DAY_IN_SECONDS;
  private const MISMATCHES_TRANSIENT_PREFIX = 'MAILPOET_link_token_mismatches_';

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var array<string, true> Mismatches counted in this request, so a token checked twice counts once */
  private $countedMismatches = [];

  public function __construct(
    SubscribersRepository $subscribersRepository,
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->settings = $settings;
    $this->wp = $wp;
  }

  public function getToken(SubscriberEntity $subscriber): string {
    if ($subscriber->getLinkToken() === null) {
      $subscriber->setLinkToken($this->generateToken());
      $this->subscribersRepository->flush();
    }
    return (string)$subscriber->getLinkToken();
  }

  /**
   * @param mixed $token Tokens come from decoded request data, so they can be of any type.
   */
  public function verifyToken(SubscriberEntity $subscriber, $token) {
    if (!is_string($token)) {
      return false;
    }
    $databaseToken = $this->getToken($subscriber);
    // Fail closed: an empty stored token would otherwise accept any input,
    // because hash_equals('', substr($x, 0, 0)) is always true.
    if ($databaseToken === '') {
      return false;
    }
    $requestToken = substr($token, 0, strlen($databaseToken));
    if (hash_equals($databaseToken, $requestToken)) {
      return true;
    }
    if (!$this->isUpgradedToken($databaseToken) || !$this->acceptsObsoleteTokens() || $this->hasTooManyMismatches($subscriber)) {
      return false;
    }
    $obsoleteToken = substr($databaseToken, 0, SubscriberEntity::OBSOLETE_LINK_TOKEN_LENGTH);
    if (hash_equals($obsoleteToken, substr($token, 0, SubscriberEntity::OBSOLETE_LINK_TOKEN_LENGTH))) {
      return true;
    }
    $this->countMismatch($subscriber, $token);
    return false;
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

  private function hasTooManyMismatches(SubscriberEntity $subscriber): bool {
    return $this->getMismatchCount($subscriber) >= self::MISMATCH_LIMIT;
  }

  private function countMismatch(SubscriberEntity $subscriber, string $token): void {
    $transient = $this->getMismatchesTransient($subscriber);
    $key = $transient . '|' . $token;
    if ($transient === null || isset($this->countedMismatches[$key])) {
      return;
    }
    $this->countedMismatches[$key] = true;
    $this->wp->setTransient($transient, $this->getMismatchCount($subscriber) + 1, self::MISMATCH_WINDOW);
  }

  private function getMismatchCount(SubscriberEntity $subscriber): int {
    $transient = $this->getMismatchesTransient($subscriber);
    $count = $transient === null ? 0 : $this->wp->getTransient($transient);
    return is_numeric($count) ? (int)$count : 0;
  }

  private function getMismatchesTransient(SubscriberEntity $subscriber): ?string {
    $subscriberId = $subscriber->getId();
    return $subscriberId ? self::MISMATCHES_TRANSIENT_PREFIX . $subscriberId : null;
  }

  private function acceptsObsoleteTokens(): bool {
    $acceptedUntil = (int)$this->settings->get(self::OBSOLETE_TOKENS_ACCEPTED_UNTIL_SETTING, 0);
    return Carbon::now()->getTimestamp() < $acceptedUntil;
  }
}
