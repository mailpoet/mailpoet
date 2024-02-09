<?php declare(strict_types = 1);

namespace integration\Migrations\App;

use DateTimeImmutable;
use DateTimeInterface;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Migrations\App\Migration_20240207_105912_App;
use MailPoet\Statistics\StatisticsNewslettersRepository;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber;
use MailPoet\Test\DataFactories\SendingQueue;
use MailPoet\Test\DataFactories\Subscriber;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20240207_105912_App_Test extends \MailPoetTest {
  /** @var Migration_20240207_105912_App */
  private $migration;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20240207_105912_App($this->diContainer);
  }

  public function testItPausesInvalidTasksWithUnprocessedSubscribers(): void {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SENDING);
    $task = $this->createTask($newsletter, [
      'status' => ScheduledTaskEntity::STATUS_INVALID,
      'processedSubscribers' => 3,
      'unprocessedSubscribers' => 1,
      'failedSubscribers' => 1,
    ]);

    $this->migration->run();

    $this->refreshAll([$newsletter, $task]);
    $this->assertSame(NewsletterEntity::STATUS_SENDING, $newsletter->getStatus());
    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $task->getStatus());
  }

  public function testItCompletesInvalidTasksWithAllProcessedSubscribers(): void {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SENDING);
    $task = $this->createTask($newsletter, [
      'status' => ScheduledTaskEntity::STATUS_INVALID,
      'processedSubscribers' => 3,
      'unprocessedSubscribers' => 0,
      'failedSubscribers' => 1,
    ]);

    $this->migration->run();

    $this->refreshAll([$newsletter, $task]);
    $this->assertSame(NewsletterEntity::STATUS_SENT, $newsletter->getStatus());
    $this->assertEquals($this->getMaxUpdatedDateFromTaskSubscribers($task), $newsletter->getSentAt());
    $this->assertSame(ScheduledTaskEntity::STATUS_COMPLETED, $task->getStatus());
  }

  public function testItIgnoresNonSendingNewsletters(): void {
    $newsletter1 = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SCHEDULED);
    $task1 = $this->createTask($newsletter1, [
      'status' => ScheduledTaskEntity::STATUS_INVALID,
      'processedSubscribers' => 3,
      'unprocessedSubscribers' => 1,
      'failedSubscribers' => 1,
    ]);

    $newsletter2 = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_ACTIVE);
    $task2 = $this->createTask($newsletter1, [
      'status' => ScheduledTaskEntity::STATUS_INVALID,
      'processedSubscribers' => 3,
      'unprocessedSubscribers' => 1,
      'failedSubscribers' => 1,
    ]);

    $this->migration->run();

    $this->refreshAll([$newsletter1, $task1, $newsletter2, $task2]);
    $this->assertSame(NewsletterEntity::STATUS_SCHEDULED, $newsletter1->getStatus());
    $this->assertNull($newsletter1->getSentAt());
    $this->assertSame(ScheduledTaskEntity::STATUS_INVALID, $task1->getStatus());
    $this->assertSame(NewsletterEntity::STATUS_ACTIVE, $newsletter2->getStatus());
    $this->assertNull($newsletter2->getSentAt());
    $this->assertSame(ScheduledTaskEntity::STATUS_INVALID, $task2->getStatus());
  }

  public function testItIgnoresNonCampaignNewsletters(): void {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_WELCOME, NewsletterEntity::STATUS_SENDING);
    $task = $this->createTask($newsletter, [
      'status' => ScheduledTaskEntity::STATUS_INVALID,
      'processedSubscribers' => 3,
      'unprocessedSubscribers' => 1,
      'failedSubscribers' => 1,
    ]);

    $this->migration->run();

    $this->refreshAll([$newsletter, $task]);
    $this->assertSame(NewsletterEntity::STATUS_SENDING, $newsletter->getStatus());
    $this->assertNull($newsletter->getSentAt());
    $this->assertSame(ScheduledTaskEntity::STATUS_INVALID, $task->getStatus());
  }

  public function testItIgnoresDeletedTasks(): void {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SENDING);
    $task = $this->createTask($newsletter, [
      'status' => ScheduledTaskEntity::STATUS_INVALID,
      'processedSubscribers' => 3,
      'unprocessedSubscribers' => 1,
      'failedSubscribers' => 1,
      'isDeleted' => true,
    ]);

    $this->migration->run();

    $this->refreshAll([$newsletter, $task]);
    $this->assertSame(NewsletterEntity::STATUS_SENDING, $newsletter->getStatus());
    $this->assertNull($newsletter->getSentAt());
    $this->assertSame(ScheduledTaskEntity::STATUS_INVALID, $task->getStatus());
    $this->assertNotNull($task->getDeletedAt());
  }

  public function testItIgnoresDeletedNewsletters(): void {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SENDING, true);
    $task = $this->createTask($newsletter, [
      'status' => ScheduledTaskEntity::STATUS_INVALID,
      'processedSubscribers' => 3,
      'unprocessedSubscribers' => 1,
      'failedSubscribers' => 1,
    ]);

    $this->migration->run();

    $this->refreshAll([$newsletter, $task]);
    $this->assertSame(NewsletterEntity::STATUS_SENDING, $newsletter->getStatus());
    $this->assertNull($newsletter->getSentAt());
    $this->assertSame(ScheduledTaskEntity::STATUS_INVALID, $task->getStatus());
  }

  public function testMultipleNewslettersAtOnce(): void {
    // invalid task with unprocessed subscribers
    $newsletter1 = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SENDING);
    $task1 = $this->createTask($newsletter1, [
      'status' => ScheduledTaskEntity::STATUS_INVALID,
      'processedSubscribers' => 3,
      'unprocessedSubscribers' => 1,
      'failedSubscribers' => 1,
    ]);

    // invalid task with all subscribers processed
    $newsletter2 = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SENDING);
    $task2 = $this->createTask($newsletter2, [
      'status' => ScheduledTaskEntity::STATUS_INVALID,
      'processedSubscribers' => 3,
      'unprocessedSubscribers' => 0,
      'failedSubscribers' => 1,
    ]);

    // invalid task with non-sending newsletter
    $newsletter3 = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SCHEDULED);
    $task3 = $this->createTask($newsletter3, [
      'status' => ScheduledTaskEntity::STATUS_INVALID,
      'processedSubscribers' => 3,
      'unprocessedSubscribers' => 1,
      'failedSubscribers' => 1,
    ]);

    // invalid task with non-campaign newsletter
    $newsletter4 = $this->createNewsletter(NewsletterEntity::TYPE_WELCOME, NewsletterEntity::STATUS_SENDING);
    $task4 = $this->createTask($newsletter4, [
      'status' => ScheduledTaskEntity::STATUS_INVALID,
      'processedSubscribers' => 3,
      'unprocessedSubscribers' => 1,
      'failedSubscribers' => 1,
    ]);

    $this->migration->run();

    $this->refreshAll([$newsletter1, $task1, $newsletter2, $task2, $newsletter3, $task3, $newsletter4, $task4]);
    $this->assertSame(NewsletterEntity::STATUS_SENDING, $newsletter1->getStatus());
    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $task1->getStatus());
    $this->assertNull($newsletter1->getSentAt());
    $this->assertSame(NewsletterEntity::STATUS_SENT, $newsletter2->getStatus());
    $this->assertSame(ScheduledTaskEntity::STATUS_COMPLETED, $task2->getStatus());
    $this->assertEquals($this->getMaxUpdatedDateFromTaskSubscribers($task2), $newsletter2->getSentAt());
    $this->assertSame(NewsletterEntity::STATUS_SCHEDULED, $newsletter3->getStatus());
    $this->assertSame(ScheduledTaskEntity::STATUS_INVALID, $task3->getStatus());
    $this->assertNull($newsletter3->getSentAt());
    $this->assertSame(NewsletterEntity::STATUS_SENDING, $newsletter4->getStatus());
    $this->assertSame(ScheduledTaskEntity::STATUS_INVALID, $task4->getStatus());
    $this->assertNull($newsletter4->getSentAt());
  }

  public function testItUpdatesCountsWhenCompletingInvalidTasks(): void {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SENDING);
    $task = $this->createTask($newsletter, [
      'status' => ScheduledTaskEntity::STATUS_INVALID,
      'processedSubscribers' => 3,
      'unprocessedSubscribers' => 0,
      'failedSubscribers' => 1,
    ]);

    $queue = $task->getSendingQueue();
    $this->assertNotNull($queue);

    // incorrect processed/to-process counts
    $queue->setCountProcessed(0);
    $queue->setCountToProcess(4);
    $queue->setCountTotal(4);
    $this->entityManager->flush();

    $this->migration->run();

    $this->refreshAll([$newsletter, $task, $queue]);
    $this->assertSame(NewsletterEntity::STATUS_SENT, $newsletter->getStatus());
    $this->assertEquals($this->getMaxUpdatedDateFromTaskSubscribers($task), $newsletter->getSentAt());
    $this->assertSame(ScheduledTaskEntity::STATUS_COMPLETED, $task->getStatus());

    // counts should be correct now
    $this->assertSame(4, $queue->getCountProcessed());
    $this->assertSame(0, $queue->getCountToProcess());
    $this->assertSame(4, $queue->getCountTotal());
  }

  public function testItBackfillsMissingNewsletterStatistics(): void {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, NewsletterEntity::STATUS_SENT);
    $task = $this->createTask($newsletter, [
      'status' => ScheduledTaskEntity::STATUS_COMPLETED,
      'processedSubscribers' => 3,
      'unprocessedSubscribers' => 2,
      'failedSubscribers' => 1,
    ]);

    $repository = $this->diContainer->get(StatisticsNewslettersRepository::class);
    $this->assertCount(0, $repository->findAll());
    $this->assertNull($newsletter->getSentAt());

    $this->migration->run();

    $this->refreshAll([$newsletter, $task]);
    $this->assertSame(NewsletterEntity::STATUS_SENT, $newsletter->getStatus());
    $this->assertEquals($this->getMaxUpdatedDateFromTaskSubscribers($task), $newsletter->getSentAt());
    $this->assertSame(ScheduledTaskEntity::STATUS_COMPLETED, $task->getStatus());

    $stats = $repository->findAll();
    $processedSubscribers = $task->getSubscribersByProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED);
    $this->assertCount(4, $stats); // 3 ok + 1 failed
    $this->assertCount(4, $processedSubscribers); // 3 ok + 1 failed

    for ($i = 0; $i < 4; $i++) {
      $this->assertSame($newsletter, $stats[$i]->getNewsletter());
      $this->assertSame($task->getSendingQueue(), $stats[$i]->getQueue());

      $subscriberIndex = array_search($stats[$i]->getSubscriber(), $processedSubscribers, true);
      $subscriber = $processedSubscribers[$subscriberIndex] ?? null;
      $this->assertNotNull($subscriber);
      $this->assertSame($subscriber, $stats[$i]->getSubscriber());
      $this->assertEquals($subscriber->getUpdatedAt(), $stats[$i]->getSentAt());
    }
  }

  private function createNewsletter(string $newsletterType, string $newsletterStatus, bool $isDeleted = false): NewsletterEntity {
    $newsletterFactory = (new NewsletterFactory())
      ->withType($newsletterType)
      ->withStatus($newsletterStatus);

    if ($isDeleted) {
      $newsletterFactory->withDeleted();
    }
    return $newsletterFactory->create();
  }

  /**
   * @param NewsletterEntity $newsletter
   * @param array{
   *   status?: string,
   *   processedSubscribers?: int,
   *   unprocessedSubscribers?: int,
   *   failedSubscribers?: int,
   * } $params
   */
  private function createTask(NewsletterEntity $newsletter, array $params = []): ScheduledTaskEntity {
    $taskStatus = $params['status'] ?? ScheduledTaskEntity::STATUS_INVALID;
    $processedSubscribers = $params['processedSubscribers'] ?? 0;
    $unprocessedSubscribers = $params['unprocessedSubscribers'] ?? 0;
    $failedSubscribers = $params['failedSubscribers'] ?? 0;
    $isDeleted = $params['isDeleted'] ?? false;

    $task = (new ScheduledTask())->create(SendingQueueWorker::TASK_TYPE, $taskStatus);
    if ($isDeleted) {
      $task->setDeletedAt(new DateTimeImmutable());
      $this->entityManager->flush();
    }

    for ($i = 0; $i < $processedSubscribers; $i++) {
      (new ScheduledTaskSubscriber())->createProcessed($task, (new Subscriber())->create());
    }

    for ($i = 0; $i < $unprocessedSubscribers; $i++) {
      (new ScheduledTaskSubscriber())->createUnprocessed($task, (new Subscriber())->create());
    }

    for ($i = 0; $i < $failedSubscribers; $i++) {
      (new ScheduledTaskSubscriber())->createFailed($task, (new Subscriber())->create());
    }

    (new SendingQueue())->create($task, $newsletter);
    return $task;
  }

  private function refreshAll(array $entities) {
    foreach ($entities as $entity) {
      $this->entityManager->refresh($entity);
    }
  }

  private function getMaxUpdatedDateFromTaskSubscribers(ScheduledTaskEntity $task): DateTimeInterface {
    $date = new DateTimeImmutable('1900-01-01');
    foreach ($task->getSubscribers() as $subscriber) {
      $date = max($date, $subscriber->getUpdatedAt());
    }
    return $date;
  }
}
