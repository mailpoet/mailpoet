<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\NewsletterReplayMetadata;
use MailPoet\Newsletter\Sending\ScheduledTaskQueuedSubscriberRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscriber;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Statistics\StatisticsNewslettersRepository;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;

class LatestNewsletterSchedulerTest extends \MailPoetTest {
  private LatestNewsletterScheduler $scheduler;

  private NewslettersRepository $newslettersRepository;

  private ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository;

  private ScheduledTaskQueuedSubscriberRepository $scheduledTaskQueuedSubscriberRepository;

  public function _before() {
    parent::_before();
    $this->scheduler = $this->diContainer->get(LatestNewsletterScheduler::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->scheduledTaskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
    $this->scheduledTaskQueuedSubscriberRepository = $this->diContainer->get(ScheduledTaskQueuedSubscriberRepository::class);
  }

  public function testItFindsLatestCompletedNonReplayStandardNewsletterForSegment(): void {
    $segment = (new Segment())->create();
    $older = (new Newsletter())
      ->withSentStatus()
      ->withSegments([$segment])
      ->withSendingQueue(['processed_at' => Carbon::parse('2026-01-01 10:00:00')])
      ->create();
    $latest = (new Newsletter())
      ->withSentStatus()
      ->withSegments([$segment])
      ->withSendingQueue(['processed_at' => Carbon::parse('2026-01-02 10:00:00')])
      ->create();
    (new Newsletter())
      ->withSentStatus()
      ->withSegments([$segment])
      ->withSendingQueue([
        'processed_at' => Carbon::parse('2026-01-03 10:00:00'),
        'meta' => [NewsletterReplayMetadata::LATEST_NEWSLETTER_REPLAY => true],
      ])
      ->create();

    $source = $this->newslettersRepository->findLatestSentStandardForSegment((int)$segment->getId());

    $this->assertNotNull($source);
    $this->assertSame($latest->getId(), $source['newsletter']->getId());
    $this->assertNotSame($older->getId(), $source['newsletter']->getId());
  }

  public function testItFindsLatestSourceWhenMoreThanPreviousBoundReplayRowsAreNewer(): void {
    $segment = (new Segment())->create();
    $sourceNewsletter = (new Newsletter())
      ->withSentStatus()
      ->withSegments([$segment])
      ->withSendingQueue(['processed_at' => Carbon::parse('2026-01-02 10:00:00')])
      ->create();

    for ($i = 0; $i < 101; $i++) {
      $this->createReplayQueue(
        $sourceNewsletter,
        Carbon::parse('2026-01-03 10:00:00')->addMinutes($i),
        [NewsletterReplayMetadata::LATEST_NEWSLETTER_REPLAY => true]
      );
    }
    $this->entityManager->flush();

    $source = $this->newslettersRepository->findLatestSentStandardForSegment((int)$segment->getId());

    $this->assertNotNull($source);
    $this->assertSame($sourceNewsletter->getId(), $source['newsletter']->getId());
    $this->assertFalse(NewsletterReplayMetadata::isLatestNewsletterReplayMeta($source['queue']->getMeta()));
    $this->assertFalse(NewsletterReplayMetadata::isLatestNewsletterReplayMeta($source['task']->getMeta()));
  }

  public function testItCreatesFreshRenderReplayTaskWithAutomationMetadata(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    $sourceNewsletter = (new Newsletter())
      ->withSentStatus()
      ->withSegments([$segment])
      ->withSendingQueue([
        'processed_at' => Carbon::parse('2026-01-02 10:00:00'),
        'subject' => 'Rendered source subject',
      ])
      ->create();

    $result = $this->scheduler->schedule($subscriber, (int)$segment->getId(), $this->automationMeta());

    $this->assertSame(LatestNewsletterScheduler::OUTCOME_SCHEDULED, $result['outcome']);
    $this->assertInstanceOf(NewsletterEntity::class, $result['newsletter']);
    $this->assertSame($sourceNewsletter->getId(), $result['newsletter']->getId());
    $this->assertInstanceOf(ScheduledTaskSubscriber::class, $result['task_subscriber']);
    $this->assertTrue($result['task_subscriber']->isPending());
    $task = $result['task_subscriber']->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    // the recipient is queued for sending, not in the log
    $this->assertSame([(int)$subscriber->getId()], $this->getQueuedSubscriberIds($task));
    $this->assertCount(0, $task->getSubscribers());
    $queue = $task->getSendingQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);

    $meta = $task->getMeta();
    $this->assertIsArray($meta);
    $this->assertTrue($meta[NewsletterReplayMetadata::LATEST_NEWSLETTER_REPLAY]);
    $this->assertSame($sourceNewsletter->getId(), $meta[NewsletterReplayMetadata::REPLAY_SOURCE_NEWSLETTER_ID]);
    $this->assertSame($subscriber->getId(), $meta[NewsletterReplayMetadata::REPLAY_SUBSCRIBER_ID]);
    $this->assertSame(123, $meta[NewsletterReplayMetadata::AUTOMATION]['run_id']);
    $this->assertSame($meta, $queue->getMeta());
    $this->assertNull($queue->getNewsletterRenderedBody());
    $this->assertNull($queue->getNewsletterRenderedSubject());
    $this->assertSame(1, $queue->getCountToProcess());
    $this->assertSame(1, $queue->getCountTotal());
  }

  public function testItTreatsExistingStatisticsRowAsDuplicate(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    $sourceNewsletter = (new Newsletter())
      ->withSentStatus()
      ->withSegments([$segment])
      ->withSubscriber($subscriber, [
        'processed' => ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
        'failed' => ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED,
      ])
      ->withSendingQueue(['processed_at' => Carbon::parse('2026-01-02 10:00:00')])
      ->create();
    $queue = $sourceNewsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $this->diContainer->get(StatisticsNewslettersRepository::class)->createMultiple([[
      'newsletter_id' => $sourceNewsletter->getId(),
      'queue_id' => $queue->getId(),
      'subscriber_id' => $subscriber->getId(),
    ]]);

    $result = $this->scheduler->schedule($subscriber, (int)$segment->getId(), $this->automationMeta());

    $this->assertSame(LatestNewsletterScheduler::OUTCOME_DUPLICATE, $result['outcome']);
    $this->assertNull($result['task_subscriber']);
  }

  public function testItTreatsSuccessfulProcessedSendAsDuplicate(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    (new Newsletter())
      ->withSentStatus()
      ->withSegments([$segment])
      ->withSubscriber($subscriber, [
        'processed' => ScheduledTaskSubscriberEntity::STATUS_PROCESSED,
        'failed' => ScheduledTaskSubscriberEntity::FAIL_STATUS_OK,
      ])
      ->withSendingQueue(['processed_at' => Carbon::parse('2026-01-02 10:00:00')])
      ->create();

    $result = $this->scheduler->schedule($subscriber, (int)$segment->getId(), $this->automationMeta());

    $this->assertSame(LatestNewsletterScheduler::OUTCOME_DUPLICATE, $result['outcome']);
  }

  public function testItTreatsPendingNonReplayTaskAsDuplicate(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    $sourceNewsletter = (new Newsletter())
      ->withSentStatus()
      ->withSegments([$segment])
      ->withSendingQueue(['processed_at' => Carbon::parse('2026-01-02 10:00:00')])
      ->create();

    $task = new ScheduledTaskEntity();
    $task->setType(SendingQueue::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $task->setSendingQueue($queue);
    $queue->setNewsletter($sourceNewsletter);
    $this->entityManager->persist($queue);

    $taskSubscriber = new ScheduledTaskQueuedSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($taskSubscriber);
    $this->entityManager->flush();

    $result = $this->scheduler->schedule($subscriber, (int)$segment->getId(), $this->automationMeta());

    $this->assertSame(LatestNewsletterScheduler::OUTCOME_DUPLICATE, $result['outcome']);
    $this->assertNull($result['task_subscriber']);
  }

  public function testItDoesNotTreatCompletedUnprocessedReplayTaskAsDuplicate(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    $sourceNewsletter = (new Newsletter())
      ->withSentStatus()
      ->withSegments([$segment])
      ->withSendingQueue(['processed_at' => Carbon::parse('2026-01-02 10:00:00')])
      ->create();
    $staleReplayTask = $this->createReplayQueue(
      $sourceNewsletter,
      Carbon::parse('2026-01-03 10:00:00'),
      [NewsletterReplayMetadata::LATEST_NEWSLETTER_REPLAY => true]
    );
    $staleReplayTask->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $staleTaskSubscriber = new ScheduledTaskSubscriberEntity(
      $staleReplayTask,
      $subscriber,
      ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
      ScheduledTaskSubscriberEntity::FAIL_STATUS_OK
    );
    $this->entityManager->persist($staleTaskSubscriber);
    $this->entityManager->flush();

    $result = $this->scheduler->schedule($subscriber, (int)$segment->getId(), $this->automationMeta());

    $this->assertSame(LatestNewsletterScheduler::OUTCOME_SCHEDULED, $result['outcome']);
    $this->assertInstanceOf(ScheduledTaskSubscriber::class, $result['task_subscriber']);
    $task = $result['task_subscriber']->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $this->assertNotSame($staleReplayTask->getId(), $task->getId());
  }

  public function testItTreatsPendingReplayFromDifferentRunAsDuplicate(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    (new Newsletter())
      ->withSentStatus()
      ->withSegments([$segment])
      ->withSendingQueue(['processed_at' => Carbon::parse('2026-01-02 10:00:00')])
      ->create();

    $firstRun = $this->scheduler->schedule($subscriber, (int)$segment->getId(), $this->automationMeta());
    $secondRun = $this->scheduler->schedule($subscriber, (int)$segment->getId(), [
      'id' => 10,
      'run_id' => 456,
      'step_id' => 'step-id',
      'run_number' => 1,
    ]);

    $this->assertSame(LatestNewsletterScheduler::OUTCOME_SCHEDULED, $firstRun['outcome']);
    $this->assertSame(LatestNewsletterScheduler::OUTCOME_DUPLICATE, $secondRun['outcome']);
    $this->assertNull($secondRun['task_subscriber']);
  }

  public function testSaveErrorAndPauseStopsReplayTask(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    (new Newsletter())
      ->withSentStatus()
      ->withSegments([$segment])
      ->withSendingQueue(['processed_at' => Carbon::parse('2026-01-02 10:00:00')])
      ->create();

    $result = $this->scheduler->schedule($subscriber, (int)$segment->getId(), $this->automationMeta());
    $taskSubscriber = $result['task_subscriber'];
    $this->assertInstanceOf(ScheduledTaskSubscriber::class, $taskSubscriber);
    $task = $taskSubscriber->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    $this->scheduler->saveErrorAndPause($taskSubscriber, 'Replay timed out');
    $this->entityManager->refresh($task);

    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $task->getStatus());

    // the recipient was moved from the queue to the log as failed
    $this->assertSame(0, $this->scheduledTaskQueuedSubscriberRepository->countForTask($task));
    $logSubscriber = $this->scheduledTaskSubscribersRepository->findOneBy(['task' => $task]);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $logSubscriber);
    $this->assertSame(ScheduledTaskSubscriberEntity::STATUS_PROCESSED, $logSubscriber->getProcessed());
    $this->assertSame(ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED, $logSubscriber->getFailed());
    $this->assertSame('Replay timed out', $logSubscriber->getError());
  }

  /** @return int[] */
  private function getQueuedSubscriberIds(ScheduledTaskEntity $task): array {
    $taskId = $task->getId();
    if (!$taskId) {
      return [];
    }
    return $this->scheduledTaskQueuedSubscriberRepository->getSubscriberIdsBatchForTask($taskId, 0, 100);
  }

  private function createReplayQueue(NewsletterEntity $newsletter, Carbon $processedAt, array $meta): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(SendingQueue::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $task->setProcessedAt($processedAt);
    $task->setMeta($meta);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $task->setSendingQueue($queue);
    $queue->setNewsletter($newsletter);
    $queue->setCountProcessed(1);
    $queue->setCountTotal(1);
    $queue->setMeta($meta);
    $this->entityManager->persist($queue);
    $newsletter->getQueues()->add($queue);

    return $task;
  }

  /** @return array{id: mixed, run_id: mixed, step_id: mixed, run_number: mixed} */
  private function automationMeta(): array {
    return [
      'id' => 10,
      'run_id' => 123,
      'step_id' => 'step-id',
      'run_number' => 1,
    ];
  }
}
