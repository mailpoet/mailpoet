<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Subscribers\ConfirmationEmailMailer;
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
}
