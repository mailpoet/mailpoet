<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Config\Menu;
use MailPoet\Config\Renderer;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberLimitNotificationMailer {

  /** @var Renderer */
  private $renderer;

  /** @var WPFunctions */
  private $wp;

  /** @var SubscriberLimitNotificationNativeMailer */
  private $nativeMailer;

  public function __construct(
    Renderer $renderer,
    WPFunctions $wp,
    SubscriberLimitNotificationNativeMailer $nativeMailer
  ) {
    $this->renderer = $renderer;
    $this->wp = $wp;
    $this->nativeMailer = $nativeMailer;
  }

  public function send(int $threshold, int $count, int $limit): bool {
    $recipient = $this->getRecipient();
    if ($recipient === null) {
      return false;
    }

    $context = [
      'count' => $count,
      'limit' => $limit,
      'threshold' => $threshold,
      'link_upgrade' => $this->wp->adminUrl('admin.php?page=' . Menu::UPGRADE_PAGE_SLUG),
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
}
