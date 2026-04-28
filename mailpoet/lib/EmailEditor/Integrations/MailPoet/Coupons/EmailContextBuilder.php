<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Coupons;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\WP\Functions as WPFunctions;

class EmailContextBuilder {
  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function build(NewsletterEntity $newsletter, ?SendingQueueEntity $sendingQueue, bool $preview): array {
    $context = [
      'integration' => 'mailpoet',
      'newsletter_id' => (int)$newsletter->getId(),
      'queue_id' => $sendingQueue ? (int)$sendingQueue->getId() : 0,
      'email_type' => $newsletter->getType(),
      'is_real_send' => false,
      'is_preview' => $preview,
      'is_single_recipient' => false,
      'subscriber_count' => 0,
      'mailpoet_is_automation' => false,
    ];

    if ($preview || !$sendingQueue || !$this->isAutomationType($newsletter)) {
      if (!$preview && $sendingQueue && $newsletter->getType() === NewsletterEntity::TYPE_STANDARD) {
        $context['is_real_send'] = true;
        $context['subscriber_count'] = $this->getQueueSubscriberCount($sendingQueue);
      }
      return $context;
    }

    $task = $sendingQueue->getTask();
    $subscribers = $task ? $task->getSubscribers() : null;
    $subscriberCount = $subscribers ? count($subscribers) : 0;
    $context['subscriber_count'] = $subscriberCount;
    $context['mailpoet_is_automation'] = true;

    if ($subscriberCount !== 1) {
      return $context;
    }

    // Only one-recipient automation sends can safely expose a unique recipient
    // email to WooCommerce. Bulk renders must not use the first subscriber as a
    // stand-in for everyone who will receive the email.
    $firstSubscriber = $subscribers ? $subscribers->first() : null;
    $subscriber = $firstSubscriber ? $firstSubscriber->getSubscriber() : null;
    $recipientEmail = $subscriber ? $subscriber->getEmail() : null;
    if (!is_string($recipientEmail) || !$this->wp->isEmail($recipientEmail)) {
      return $context;
    }

    $context['recipient_email'] = $recipientEmail;
    $context['is_real_send'] = true;
    $context['is_preview'] = false;
    $context['is_single_recipient'] = true;
    $context['subscriber_count'] = 1;
    return $context;
  }

  private function getQueueSubscriberCount(SendingQueueEntity $sendingQueue): int {
    $task = $sendingQueue->getTask();
    $subscribers = $task ? $task->getSubscribers() : null;
    return $subscribers ? count($subscribers) : 0;
  }

  private function isAutomationType(NewsletterEntity $newsletter): bool {
    return in_array($newsletter->getType(), [
      NewsletterEntity::TYPE_AUTOMATION,
      NewsletterEntity::TYPE_AUTOMATION_NOTIFICATION,
      NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL,
    ], true);
  }
}
