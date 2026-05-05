<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Config\Renderer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberLimitNotificationMailer {

  /** @var Renderer */
  private $renderer;

  /** @var WPFunctions */
  private $wp;

  /** @var MailerFactory */
  private $mailerFactory;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var ServicesChecker */
  private $servicesChecker;

  public function __construct(
    Renderer $renderer,
    WPFunctions $wp,
    MailerFactory $mailerFactory,
    MetaInfo $mailerMetaInfo,
    ServicesChecker $servicesChecker
  ) {
    $this->renderer = $renderer;
    $this->wp = $wp;
    $this->mailerFactory = $mailerFactory;
    $this->mailerMetaInfo = $mailerMetaInfo;
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

    $newsletter = [
      // translators: %d is the subscriber limit threshold percentage.
      'subject' => sprintf(__('Your MailPoet subscriber list is at %d%% of its limit', 'mailpoet'), $threshold),
      'body' => [
        'html' => $this->renderer->render('emails/subscriberLimitThresholdNotification.html', $context),
        'text' => $this->renderer->render('emails/subscriberLimitThresholdNotification.txt', $context),
      ],
    ];

    try {
      $result = $this->mailerFactory->getDefaultMailer()->send($newsletter, $recipient, [
        'meta' => $this->mailerMetaInfo->getSubscriberLimitNotificationMetaInfo(),
      ]);
    } catch (\Exception $e) {
      return false;
    }

    return (bool)($result['response'] ?? false);
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
      $partialApiKey = $this->servicesChecker->generatePartialApiKey();
      if ($partialApiKey !== '') {
        return 'https://account.mailpoet.com/orders/upgrade/' . $partialApiKey;
      }
    }

    return 'https://account.mailpoet.com/?s=' . ($limit + 1);
  }
}
