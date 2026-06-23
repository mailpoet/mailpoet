<?php declare(strict_types = 1);

namespace unit\EmailEditor\Integrations\MailPoet\Coupons;

use MailPoet\EmailEditor\Integrations\MailPoet\Coupons\EmailContextBuilder;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;

class EmailContextBuilderTest extends \MailPoetUnitTest {
  public function testItMarksSingleRecipientAutomationAsRealSendWithoutValidRecipientEmail(): void {
    $builder = $this->makeBuilder();

    $context = $builder->build(
      $this->makeNewsletter(NewsletterEntity::TYPE_AUTOMATION),
      $this->makeSendingQueue(1, 'not-an-email'),
      false
    );

    verify($context['is_real_send'])->true();
    verify($context['is_preview'])->false();
    verify($context['is_single_recipient'])->true();
    verify($context['subscriber_count'])->equals(1);
    verify(isset($context['recipient_email']))->false();
  }

  public function testItAddsRecipientEmailForSingleRecipientAutomationWithValidRecipientEmail(): void {
    $builder = $this->makeBuilder();

    $context = $builder->build(
      $this->makeNewsletter(NewsletterEntity::TYPE_AUTOMATION),
      $this->makeSendingQueue(1, 'subscriber@example.com'),
      false
    );

    verify($context['recipient_email'])->equals('subscriber@example.com');
    verify($context['is_real_send'])->true();
    verify($context['is_single_recipient'])->true();
  }

  public function testItAddsUserIdForSingleRecipientAutomationLinkedToWpUser(): void {
    $builder = $this->makeBuilder();

    $context = $builder->build(
      $this->makeNewsletter(NewsletterEntity::TYPE_AUTOMATION),
      $this->makeSendingQueue(1, 'subscriber@example.com', 123),
      false
    );

    verify($context['user_id'])->equals(123);
  }

  public function testItDoesNotMarkAsSingleRecipientWhenTaskHasMultipleRecipients(): void {
    // One recipient already sent (log) and one still pending (queue) => 2 recipients total,
    // so this is not a 1:1 send and must not expose a single recipient email.
    $builder = $this->makeBuilder();

    $context = $builder->build(
      $this->makeNewsletter(NewsletterEntity::TYPE_AUTOMATION),
      $this->makeSendingQueue(2, 'subscriber@example.com'),
      false
    );

    verify($context['is_single_recipient'])->false();
    verify($context['subscriber_count'])->equals(2);
    verify(isset($context['recipient_email']))->false();
  }

  private function makeBuilder(): EmailContextBuilder {
    return new EmailContextBuilder($this->makeWpFunctions());
  }

  private function makeNewsletter(string $type): NewsletterEntity {
    return $this->make(NewsletterEntity::class, [
      'getId' => 1,
      'getType' => $type,
    ]);
  }

  private function makeSendingQueue(int $totalSubscribers = 1, ?string $recipientEmail = null, ?int $wpUserId = null): SendingQueueEntity {
    $subscriber = $this->make(SubscriberEntity::class, [
      'getEmail' => $recipientEmail,
      'getWpUserId' => $wpUserId,
    ]);
    $task = $this->make(ScheduledTaskEntity::class, [
      'getId' => 5,
      'getTotalSubscribersCount' => $totalSubscribers,
      'getQueuedCount' => $totalSubscribers,
      'getFirstQueuedSubscriber' => $recipientEmail !== null ? $subscriber : null,
    ]);

    return $this->make(SendingQueueEntity::class, [
      'getId' => 2,
      'getTask' => $task,
    ]);
  }

  private function makeWpFunctions(): WPFunctions {
    return $this->make(WPFunctions::class, [
      'isEmail' => function($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
      },
    ]);
  }
}
