<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use Codeception\Stub;
use MailPoet\Entities\LogEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Logging\LogRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class BulkConfirmationEmailResendTest extends \MailPoetTest {
  public function testItMarksIneligibleSubscribersAsSkipped(): void {
    $subscriber = (new SubscriberFactory())
      ->withEmail('maxed-confirmation@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withCountConfirmations(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS)
      ->create();

    $task = new ScheduledTaskEntity();
    $task->setType(BulkConfirmationEmailResend::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING);
    $task->setScheduledAt(Carbon::now());
    $this->entityManager->persist($task);
    $this->entityManager->flush();

    $taskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($taskSubscriber);
    $this->entityManager->flush();

    $worker = $this->diContainer->get(BulkConfirmationEmailResend::class);
    verify($worker->supportsMultipleInstances())->false();
    verify($worker->scheduleAutomatically())->false();
    verify($worker->processTaskStrategy($task, microtime(true)))->true();

    $this->entityManager->refresh($taskSubscriber);
    $this->entityManager->refresh($task);
    verify($taskSubscriber->getProcessed())->equals(ScheduledTaskSubscriberEntity::STATUS_PROCESSED);
    verify($taskSubscriber->getFailed())->equals(ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED);
    verify($taskSubscriber->getError())->equals('skipped:max_confirmations_reached');
    $taskMeta = $task->getMeta();
    $this->assertIsArray($taskMeta);
    verify($taskMeta['failed_count'])->equals(1);
    verify($taskMeta['skipped_by_reason']['max_confirmations_reached'])->equals(1);
    verify($this->diContainer->get(ScheduledTaskSubscribersRepository::class)->countUnprocessed($task))->equals(0);
  }

  public function testItRecordsSentFailedSkippedCountsAndIgnoresUnrelatedTasks(): void {
    $sent = (new SubscriberFactory())
      ->withEmail('bulk-sent@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();
    $sendFailed = (new SubscriberFactory())
      ->withEmail('bulk-send-failed@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();
    $skipped = (new SubscriberFactory())
      ->withEmail('bulk-skipped@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();
    $unrelated = (new SubscriberFactory())
      ->withEmail('bulk-unrelated@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();

    $task = $this->createTaskWithSubscribers([$sent, $sendFailed, $skipped]);
    $unrelatedTask = $this->createTaskWithSubscribers([$unrelated]);
    $unrelatedTaskSubscriber = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findOneBy([
      'task' => $unrelatedTask,
      'subscriber' => $unrelated,
    ]);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $unrelatedTaskSubscriber);

    $mailer = Stub::makeEmpty(ConfirmationEmailMailer::class, [
      'sendAdminConfirmationEmail' => function(SubscriberEntity $subscriber): array {
        if ($subscriber->getEmail() === 'bulk-sent@mailpoet.com') {
          return ['status' => 'sent'];
        }
        if ($subscriber->getEmail() === 'bulk-send-failed@mailpoet.com') {
          return ['status' => 'send_failed', 'reason' => 'sending_method'];
        }
        return ['status' => 'skipped', 'reason' => 'recently_sent'];
      },
    ], $this);
    $worker = new BulkConfirmationEmailResend(
      $mailer,
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(ScheduledTaskSubscribersRepository::class),
      $this->diContainer->get(LogRepository::class)
    );

    verify($worker->processTaskStrategy($task, microtime(true)))->true();

    $this->entityManager->refresh($task);
    $this->entityManager->refresh($unrelatedTaskSubscriber);
    $sentTaskSubscriber = $this->getTaskSubscriber($task, $sent);
    $sendFailedTaskSubscriber = $this->getTaskSubscriber($task, $sendFailed);
    $skippedTaskSubscriber = $this->getTaskSubscriber($task, $skipped);

    verify($sentTaskSubscriber->getProcessed())->equals(ScheduledTaskSubscriberEntity::STATUS_PROCESSED);
    verify($sentTaskSubscriber->getFailed())->equals(ScheduledTaskSubscriberEntity::FAIL_STATUS_OK);
    verify($sendFailedTaskSubscriber->getProcessed())->equals(ScheduledTaskSubscriberEntity::STATUS_PROCESSED);
    verify($sendFailedTaskSubscriber->getFailed())->equals(ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED);
    verify($sendFailedTaskSubscriber->getError())->equals('send_failed:sending_method');
    verify($skippedTaskSubscriber->getProcessed())->equals(ScheduledTaskSubscriberEntity::STATUS_PROCESSED);
    verify($skippedTaskSubscriber->getFailed())->equals(ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED);
    verify($skippedTaskSubscriber->getError())->equals('skipped:recently_sent');
    verify($unrelatedTaskSubscriber->getProcessed())->equals(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED);

    $taskMeta = $task->getMeta();
    $this->assertIsArray($taskMeta);
    verify($taskMeta['sent_count'])->equals(1);
    verify($taskMeta['failed_count'])->equals(2);
    verify($taskMeta['skipped_by_reason']['recently_sent'])->equals(1);

    $completionLog = $this->entityManager->getRepository(LogEntity::class)->findOneBy([
      'name' => LoggerFactory::TOPIC_CRON,
      'message' => 'Bulk confirmation email resend completed.',
    ]);
    $this->assertInstanceOf(LogEntity::class, $completionLog);
    $context = $completionLog->getContext();
    $this->assertIsArray($context);
    verify($context['sent_count'])->equals(1);
    verify($context['failed_count'])->equals(2);
    verify($context['skipped_by_reason']['recently_sent'])->equals(1);
  }

  /**
   * @param SubscriberEntity[] $subscribers
   */
  private function createTaskWithSubscribers(array $subscribers): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(BulkConfirmationEmailResend::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING);
    $task->setScheduledAt(Carbon::now());
    $this->entityManager->persist($task);
    $this->entityManager->flush();

    foreach ($subscribers as $subscriber) {
      $this->entityManager->persist(new ScheduledTaskSubscriberEntity($task, $subscriber));
    }
    $this->entityManager->flush();

    return $task;
  }

  private function getTaskSubscriber(ScheduledTaskEntity $task, SubscriberEntity $subscriber): ScheduledTaskSubscriberEntity {
    $taskSubscriber = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findOneBy([
      'task' => $task,
      'subscriber' => $subscriber,
    ]);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $taskSubscriber);
    return $taskSubscriber;
  }
}
