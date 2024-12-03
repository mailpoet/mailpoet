<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20241128_114257_App_Test extends \MailPoetTest {
  public function testItMigratesActivableEmailsWithStatusSending(): void {
    $migration = new Migration_20241128_114257_App($this->diContainer);
    $newlyScheduledAt = (new Carbon())->subDays(1);
    $oldScheduledAt = (new Carbon())->subDays(31);
    $subscriber = (new Subscriber())->create();

    $pausedStandardNewsletter = (new Newsletter())->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSendingQueue([
        'status' => ScheduledTaskEntity::STATUS_PAUSED,
        'count_processed' => 0,
        'count_to_process' => 1,
        'scheduled_at' => $newlyScheduledAt,
      ])
      ->create();

    $runningStandardNewsletter = (new Newsletter())->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSendingQueue([
        'status' => null,
        'count_processed' => 0,
        'scheduled_at' => $newlyScheduledAt,
      ])
      ->create();

    $sentStandardNewsletter = (new Newsletter())->withType(NewsletterEntity::TYPE_STANDARD)
      ->withStatus(NewsletterEntity::STATUS_SENT)
      ->withSendingQueue([
        'status' => ScheduledTaskEntity::STATUS_COMPLETED,
        'count_processed' => 1,
        'count_to_process' => 0,
        'scheduled_at' => $newlyScheduledAt,
      ])
      ->create();

    $completedAutomationEmail = (new Newsletter())->withType(NewsletterEntity::TYPE_AUTOMATION)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withSendingQueue([
        'status' => ScheduledTaskEntity::STATUS_COMPLETED,
        'count_processed' => 1,
        'count_to_process' => 0,
        'scheduled_at' => $newlyScheduledAt,
      ])
      ->create();

    $pausedAutomationEmail = (new Newsletter())->withType(NewsletterEntity::TYPE_AUTOMATION)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSendingQueue([
        'status' => ScheduledTaskEntity::STATUS_PAUSED,
        'count_processed' => 0,
        'count_to_process' => 1,
        'scheduled_at' => $newlyScheduledAt,])
      ->create();

    $pausedLegacyWelcomeEmail = (new Newsletter())->withType(NewsletterEntity::TYPE_WELCOME)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSendingQueue([
        'status' => ScheduledTaskEntity::STATUS_PAUSED,
        'count_processed' => 0,
        'count_to_process' => 1,
        'scheduled_at' => $newlyScheduledAt,
      ])
      ->create();

    $oldPausedAutomationEmail = (new Newsletter())->withType(NewsletterEntity::TYPE_AUTOMATION)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSendingQueue([
        'status' => ScheduledTaskEntity::STATUS_PAUSED,
        'count_processed' => 0,
        'count_to_process' => 1,
        'scheduled_at' => $oldScheduledAt,
      ])
      ->create();
    $task = $oldPausedAutomationEmail->getLatestQueue()->getTask();
    $taskSubscriber = (new ScheduledTaskSubscriber())->createUnprocessed($task, $subscriber);
    $task->getSubscribers()->add($taskSubscriber);


    $oldPausedWelcomeEmail = (new Newsletter())->withType(NewsletterEntity::TYPE_AUTOMATION)
      ->withStatus(NewsletterEntity::STATUS_SENDING)
      ->withSendingQueue([
        'status' => ScheduledTaskEntity::STATUS_PAUSED,
        'count_processed' => 0,
        'count_to_process' => 1,
        'scheduled_at' => $oldScheduledAt,
        ])
      ->create();
    $task = $oldPausedWelcomeEmail->getLatestQueue()->getTask();
    $taskSubscriber = (new ScheduledTaskSubscriber())->createUnprocessed($task, $subscriber);
    $task->getSubscribers()->add($taskSubscriber);
    $this->entityManager->flush();

    $migration->run();

    $this->entityManager->refresh($pausedStandardNewsletter);
    $this->entityManager->refresh($runningStandardNewsletter);
    $this->entityManager->refresh($sentStandardNewsletter);
    $this->entityManager->refresh($completedAutomationEmail);
    $this->entityManager->refresh($pausedAutomationEmail);
    $this->entityManager->refresh($pausedLegacyWelcomeEmail);
    $this->entityManager->refresh($oldPausedAutomationEmail);
    $this->entityManager->refresh($oldPausedWelcomeEmail);

    // Newsletter statuses wchich we don't touch
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

    // Newsletter we switch to active
    $this->assertEquals(NewsletterEntity::STATUS_ACTIVE, $pausedAutomationEmail->getStatus());
    $queue = $pausedAutomationEmail->getLatestQueue();
    $this->entityManager->refresh($queue);
    $this->entityManager->refresh($queue->getTask());
    $this->assertEquals(ScheduledTaskEntity::STATUS_SCHEDULED, $queue->getTask()->getStatus());

    $this->assertEquals(NewsletterEntity::STATUS_ACTIVE, $pausedLegacyWelcomeEmail->getStatus());
    $queue = $pausedLegacyWelcomeEmail->getLatestQueue();
    $this->assertEquals(ScheduledTaskEntity::STATUS_SCHEDULED, $queue->getTask()->getStatus());

    // Old paused tasks are switched to completed and scheduled task subscribers marked as failed
    $this->assertEquals(NewsletterEntity::STATUS_ACTIVE, $oldPausedAutomationEmail->getStatus());
    $queue = $oldPausedAutomationEmail->getLatestQueue();
    $this->assertEquals(ScheduledTaskEntity::STATUS_COMPLETED, $queue->getTask()->getStatus());
    $taskSubscriber = $queue->getTask()->getSubscribers()->first();
    $this->entityManager->refresh($taskSubscriber);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $taskSubscriber);
    $this->assertEquals($taskSubscriber->getFailed(), 1);
    $this->assertEquals($taskSubscriber->getProcessed(), 1);
    $this->assertEquals($taskSubscriber->getError(), 'Sending timed out for being paused too long.');

    $this->assertEquals(NewsletterEntity::STATUS_ACTIVE, $oldPausedWelcomeEmail->getStatus());
    $queue = $oldPausedWelcomeEmail->getLatestQueue();
    $this->assertEquals(ScheduledTaskEntity::STATUS_COMPLETED, $queue->getTask()->getStatus());
    $taskSubscriber = $queue->getTask()->getSubscribers()->first();
    $this->entityManager->refresh($taskSubscriber);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $taskSubscriber);
    $this->assertEquals($taskSubscriber->getFailed(), 1);
    $this->assertEquals($taskSubscriber->getFailed(), 1);
    $this->assertEquals($taskSubscriber->getProcessed(), 1);
    $this->assertEquals($taskSubscriber->getError(), 'Sending timed out for being paused too long.');
  }
}
