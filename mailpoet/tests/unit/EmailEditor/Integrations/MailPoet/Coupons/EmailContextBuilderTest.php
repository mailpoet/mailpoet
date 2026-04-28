<?php declare(strict_types = 1);

namespace unit\EmailEditor\Integrations\MailPoet\Coupons;

use MailPoet\EmailEditor\Integrations\MailPoet\Coupons\EmailContextBuilder;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\Common\Collections\ArrayCollection;

class EmailContextBuilderTest extends \MailPoetUnitTest {
  public function testItMarksSingleRecipientAutomationAsRealSendWithoutValidRecipientEmail(): void {
    $builder = new EmailContextBuilder($this->makeWpFunctions());

    $context = $builder->build(
      $this->makeNewsletter(NewsletterEntity::TYPE_AUTOMATION),
      $this->makeSendingQueue('not-an-email'),
      false
    );

    verify($context['is_real_send'])->true();
    verify($context['is_preview'])->false();
    verify($context['is_single_recipient'])->true();
    verify($context['subscriber_count'])->equals(1);
    verify(isset($context['recipient_email']))->false();
  }

  public function testItAddsRecipientEmailForSingleRecipientAutomationWithValidRecipientEmail(): void {
    $builder = new EmailContextBuilder($this->makeWpFunctions());

    $context = $builder->build(
      $this->makeNewsletter(NewsletterEntity::TYPE_AUTOMATION),
      $this->makeSendingQueue('subscriber@example.com'),
      false
    );

    verify($context['recipient_email'])->equals('subscriber@example.com');
    verify($context['is_real_send'])->true();
    verify($context['is_single_recipient'])->true();
  }

  private function makeNewsletter(string $type): NewsletterEntity {
    return $this->make(NewsletterEntity::class, [
      'getId' => 1,
      'getType' => $type,
    ]);
  }

  private function makeSendingQueue(string $subscriberEmail): SendingQueueEntity {
    $subscriber = $this->make(SubscriberEntity::class, [
      'getEmail' => $subscriberEmail,
    ]);
    $taskSubscriber = $this->make(ScheduledTaskSubscriberEntity::class, [
      'getSubscriber' => $subscriber,
    ]);
    $task = $this->make(ScheduledTaskEntity::class, [
      'getSubscribers' => new ArrayCollection([$taskSubscriber]),
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
