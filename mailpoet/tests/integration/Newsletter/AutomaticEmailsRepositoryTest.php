<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\ScheduledTaskQueuedSubscriber;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber;
use MailPoet\Test\DataFactories\SendingQueue;
use MailPoet\Test\DataFactories\Subscriber;

class AutomaticEmailsRepositoryTest extends \MailPoetTest {
  /** @var AutomaticEmailsRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(AutomaticEmailsRepository::class);
  }

  public function testItDetectsSubscriberQueuedForTheNewsletter(): void {
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())->create();
    $task = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    (new SendingQueue())->create($task, $newsletter);
    (new ScheduledTaskQueuedSubscriber())->create($task, $subscriber);

    verify($this->repository->wasScheduledForSubscriber((int)$newsletter->getId(), (int)$subscriber->getId()))->true();
  }

  public function testItDetectsSubscriberInTheProcessedLog(): void {
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())->create();
    $task = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_COMPLETED);
    (new SendingQueue())->create($task, $newsletter);
    (new ScheduledTaskSubscriber())->createProcessed($task, $subscriber);

    verify($this->repository->wasScheduledForSubscriber((int)$newsletter->getId(), (int)$subscriber->getId()))->true();
  }

  public function testItDoesNotMatchSubscriberQueuedForADifferentNewsletter(): void {
    // Regression: without parentheses around the OR-EXISTS, the queued-subscriber
    // check escaped the `q.newsletter = :newsletterId` filter, so a subscriber
    // queued for ANY automatic email looked "already scheduled" for every one.
    $subscriber = (new Subscriber())->create();
    $targetNewsletter = (new Newsletter())->create();
    $otherNewsletter = (new Newsletter())->create();

    $targetTask = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    (new SendingQueue())->create($targetTask, $targetNewsletter);

    $otherTask = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, ScheduledTaskEntity::STATUS_SCHEDULED);
    (new SendingQueue())->create($otherTask, $otherNewsletter);
    (new ScheduledTaskQueuedSubscriber())->create($otherTask, $subscriber);

    verify($this->repository->wasScheduledForSubscriber((int)$targetNewsletter->getId(), (int)$subscriber->getId()))->false();
  }
}
