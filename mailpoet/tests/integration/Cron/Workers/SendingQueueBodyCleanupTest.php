<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\SendingQueueBodyCleanup;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;

class SendingQueueBodyCleanupTest extends \MailPoetTest {
  /** @var SendingQueueBodyCleanup */
  private $worker;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(SendingQueueBodyCleanup::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->set('sending_queue_body_retention_days', 30);
  }

  public function testItProcessesOldCompletedQueues() {
    $queue = $this->createOldCompletedQueue();
    $task = new ScheduledTaskEntity();

    $this->worker->processTaskStrategy($task, microtime(true));

    $this->entityManager->refresh($queue);
    verify($queue->getNewsletterRenderedBody())->null();
  }

  public function testItSkipsWhenRetentionIsSetToNever() {
    $this->settings->set('sending_queue_body_retention_days', '');

    $queue = $this->createOldCompletedQueue();
    $task = new ScheduledTaskEntity();

    $this->worker->processTaskStrategy($task, microtime(true));

    $this->entityManager->refresh($queue);
    verify($queue->getNewsletterRenderedBody())->notNull();
  }

  public function testItRespectsExecutionTimeLimit() {
    for ($i = 0; $i < 10; $i++) {
      $this->createOldCompletedQueue();
    }

    $startTime = microtime(true);
    $task = new ScheduledTaskEntity();
    $this->worker->processTaskStrategy($task, microtime(true));
    $executionTime = microtime(true) - $startTime;

    verify($executionTime)->lessThan(SendingQueueBodyCleanup::MAX_EXECUTION_TIME + 1);
  }

  public function testItSchedulesNextRun() {
    $nextRunDate = $this->worker->getNextRunDate();
    verify($nextRunDate)->notNull();
    verify($nextRunDate->getTimestamp())->greaterThan(Carbon::now()->getTimestamp());

    $tomorrow = Carbon::now()->addDay();
    verify($nextRunDate->getTimestamp())->lessThan($tomorrow->getTimestamp());
  }

  private function createOldCompletedQueue(): SendingQueueEntity {
    $scheduledTask = new ScheduledTaskEntity();
    $scheduledTask->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $scheduledTask->setType('sending');
    $scheduledTask->setProcessedAt(new \DateTime('-40 days'));
    $this->entityManager->persist($scheduledTask);

    $newsletter = new NewsletterEntity();
    $newsletter->setType('standard');
    $newsletter->setSubject('Test');
    $this->entityManager->persist($newsletter);

    $queue = new SendingQueueEntity();
    $queue->setTask($scheduledTask);
    $queue->setNewsletter($newsletter);
    $queue->setNewsletterRenderedBody(['html' => '<p>body</p>', 'text' => 'body']);
    $this->entityManager->persist($queue);

    $this->entityManager->flush();
    return $queue;
  }
}
