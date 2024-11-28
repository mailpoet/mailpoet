<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Test\DataFactories\Newsletter;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20241128_114257_App_Test extends \MailPoetTest {
  public function testItMigratesActivableEmailsWithStatusSending(): void {
    $migration = new Migration_20241128_114257_App($this->diContainer);

    $pausedStandardNewsletter = (new Newsletter())->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_PAUSED, 'count_processed' => 0, 'count_to_process' => 1])
      ->create();

    $runningStandardNewsletter = (new Newsletter())->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSendingQueue(['status' => null, 'count_processed' => 0])
      ->create();

    $sentStandardNewsletter = (new Newsletter())->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_SENT)
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_COMPLETED, 'count_processed' => 1, 'count_to_process' => 0])
      ->create();

    $completedAutomationEmail = (new Newsletter())->withType(NewsletterEntity::TYPE_AUTOMATION)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_COMPLETED, 'count_processed' => 1, 'count_to_process' => 0])
      ->create();

    $pausedAutomationEmail = (new Newsletter())->withType(NewsletterEntity::TYPE_AUTOMATION)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_PAUSED, 'count_processed' => 0, 'count_to_process' => 1])
      ->create();

    $pausedLegacyWelcomeEmail = (new Newsletter())->withType(NewsletterEntity::TYPE_WELCOME)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSendingQueue(['status' => ScheduledTaskEntity::STATUS_PAUSED, 'count_processed' => 0, 'count_to_process' => 1])
      ->create();

    $migration->run();

    $this->entityManager->refresh($pausedStandardNewsletter);
    $this->entityManager->refresh($runningStandardNewsletter);
    $this->entityManager->refresh($sentStandardNewsletter);
    $this->entityManager->refresh($completedAutomationEmail);
    $this->entityManager->refresh($pausedAutomationEmail);
    $this->entityManager->refresh($pausedLegacyWelcomeEmail);

    $this->assertEquals(NewsletterEntity::STATUS_SENDING, $pausedStandardNewsletter->getStatus());
    $queue = $pausedStandardNewsletter->getLatestQueue();
    $this->assertEquals(ScheduledTaskEntity::STATUS_PAUSED, $queue->getTask()->getStatus());

    $this->assertEquals(NewsletterEntity::STATUS_SENDING, $runningStandardNewsletter->getStatus());
    $queue = $runningStandardNewsletter->getLatestQueue();
    $this->assertEquals(null, $queue->getTask()->getStatus());

    $this->assertEquals(NewsletterEntity::STATUS_SENT, $sentStandardNewsletter->getStatus());
    $queue = $sentStandardNewsletter->getLatestQueue();
    $this->assertEquals(ScheduledTaskEntity::STATUS_COMPLETED, $queue->getTask()->getStatus());

    $this->assertEquals(NewsletterEntity::STATUS_ACTIVE, $completedAutomationEmail->getStatus());
    $queue = $completedAutomationEmail->getLatestQueue();
    $this->assertEquals(ScheduledTaskEntity::STATUS_COMPLETED, $queue->getTask()->getStatus());

    $this->assertEquals(NewsletterEntity::STATUS_ACTIVE, $pausedAutomationEmail->getStatus());
    $queue = $pausedAutomationEmail->getLatestQueue();
    $this->entityManager->refresh($queue);
    $this->entityManager->refresh($queue->getTask());
    $this->assertEquals(ScheduledTaskEntity::STATUS_SCHEDULED, $queue->getTask()->getStatus());

    $this->assertEquals(NewsletterEntity::STATUS_ACTIVE, $pausedLegacyWelcomeEmail->getStatus());
    $queue = $pausedLegacyWelcomeEmail->getLatestQueue();
    $this->assertEquals(ScheduledTaskEntity::STATUS_SCHEDULED, $queue->getTask()->getStatus());
  }
}
