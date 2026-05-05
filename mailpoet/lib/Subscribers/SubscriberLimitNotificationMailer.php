<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Config\Renderer;
use MailPoet\Config\ServicesChecker;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberLimitNotificationMailer {

  /** @var Renderer */
  private $renderer;

  /** @var WPFunctions */
  private $wp;

  /** @var SubscriberLimitNotificationNativeMailer */
  private $nativeMailer;

  /** @var ServicesChecker */
  private $servicesChecker;

  public function __construct(
    Renderer $renderer,
    WPFunctions $wp,
    SubscriberLimitNotificationNativeMailer $nativeMailer,
    ServicesChecker $servicesChecker
  ) {
    $this->renderer = $renderer;
    $this->wp = $wp;
    $this->nativeMailer = $nativeMailer;
    $this->servicesChecker = $servicesChecker;
  }

  public function send(int $threshold, int $count, int $limit, bool $hasValidApiKey): bool {
    $recipient = $this->getRecipient();
    if ($recipient === null) {
      return false;
    }

    $context = [
      'count' => $count,
      'limit' => $limit,
      'threshold' => $threshold,
      'hasValidApiKey' => $hasValidApiKey,
      'link_upgrade' => $this->getUpgradeLink($limit, $hasValidApiKey),
    ];

    return $this->nativeMailer->send(
      $recipient,
      // translators: %d is the subscriber limit threshold percentage.
      sprintf(__('Your MailPoet subscriber list is at %d%% of its limit', 'mailpoet'), $threshold),
      $this->renderer->render('emails/subscriberLimitThresholdNotification.html', $context),
      $this->renderer->render('emails/subscriberLimitThresholdNotification.txt', $context)
    );
  }

  private function getRecipient(): ?string {
    $recipient = $this->wp->sanitizeEmail((string)$this->wp->getOption('admin_email'));
    if ($recipient === '' || !$this->wp->isEmail($recipient)) {
      return null;
    }
    return $recipient;
  }

  private function getUpgradeLink(int $limit, bool $hasValidApiKey): string {
    if ($hasValidApiKey) {
      return 'https://account.mailpoet.com/orders/upgrade/' . $this->servicesChecker->generatePartialApiKey();
    }

    return 'https://account.mailpoet.com/?s=' . ($limit + 1);
  }
}
