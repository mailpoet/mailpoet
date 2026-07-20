<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;

class SubscribersEngagementScoreTest extends \MailPoetTest {
  /** @var SubscribersEngagementScore */
  private $worker;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(SubscribersEngagementScore::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
  }

  public function _after() {
    parent::_after();
    // These tests create more than a full batch of subscribers. The integration cleanup deletes rows
    // between tests but does not reset AUTO_INCREMENT, so truncate here to avoid leaking high subscriber
    // ids into id-range-sensitive tests such as SubscribersLastEngagementTest.
    $this->truncateEntity(SubscriberEntity::class);
  }

  public function testItRecalculatesAllDueSubscribersAcrossMultipleBatchesInSingleRun() {
    // More than one batch to prove the worker keeps looping instead of stopping after BATCH_SIZE.
    $this->createSubscribers(SubscribersEngagementScore::BATCH_SIZE + 2);

    $result = $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    verify($result)->true();
    $remaining = $this->subscribersRepository->findIdsByUpdatedScoreNotInLastMonth(SubscribersEngagementScore::BATCH_SIZE);
    verify(count($remaining))->equals(0);
  }

  public function testItInterruptsAtExecutionLimitAndResumesOnNextRun() {
    $this->createSubscribers(SubscribersEngagementScore::BATCH_SIZE + 2);
    $task = new ScheduledTaskEntity();

    // A timer in the distant past forces the execution limit on the first check, after a single batch.
    $exception = null;
    try {
      $this->worker->processTaskStrategy($task, 0);
    } catch (\Exception $e) {
      $exception = $e;
    }
    $this->assertInstanceOf(\Exception::class, $exception);
    verify($exception->getCode())->equals(CronHelper::DAEMON_EXECUTION_LIMIT_REACHED);
    $remainingAfterInterrupt = $this->subscribersRepository->findIdsByUpdatedScoreNotInLastMonth(SubscribersEngagementScore::BATCH_SIZE);
    verify(count($remainingAfterInterrupt))->equals(2);

    $result = $this->worker->processTaskStrategy($task, microtime(true));

    verify($result)->true();
    $remaining = $this->subscribersRepository->findIdsByUpdatedScoreNotInLastMonth(SubscribersEngagementScore::BATCH_SIZE);
    verify(count($remaining))->equals(0);
  }

  public function testItRecalculatesAllDueSegmentsAcrossMultipleBatchesInSingleRun() {
    $this->createSegments(SubscribersEngagementScore::SEGMENTS_BATCH_SIZE + 2);

    $result = $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    verify($result)->true();
    $remaining = $this->segmentsRepository->findByUpdatedScoreNotInLastDay(SubscribersEngagementScore::SEGMENTS_BATCH_SIZE);
    verify(count($remaining))->equals(0);
  }

  public function testItInterruptsSegmentRecalculationPerSegmentAtExecutionLimit() {
    $this->createSegments(3);

    $exception = null;
    try {
      $this->worker->processTaskStrategy(new ScheduledTaskEntity(), 0);
    } catch (\Exception $e) {
      $exception = $e;
    }
    $this->assertInstanceOf(\Exception::class, $exception);
    verify($exception->getCode())->equals(CronHelper::DAEMON_EXECUTION_LIMIT_REACHED);
    // The limit is checked after every segment, so exactly one segment was processed before the interrupt.
    $remaining = $this->segmentsRepository->findByUpdatedScoreNotInLastDay(SubscribersEngagementScore::SEGMENTS_BATCH_SIZE);
    verify(count($remaining))->equals(2);
  }

  private function createSegments(int $count): void {
    for ($i = 0; $i < $count; $i++) {
      $segment = new SegmentEntity(sprintf('Engagement score segment %d', $i), SegmentEntity::TYPE_DEFAULT, '');
      $this->entityManager->persist($segment);
    }
    $this->entityManager->flush();
  }

  private function createSubscribers(int $count): void {
    for ($i = 0; $i < $count; $i++) {
      $subscriber = new SubscriberEntity();
      $subscriber->setEmail(sprintf('engagement-score-%d@test.com', $i));
      $this->entityManager->persist($subscriber);
    }
    $this->entityManager->flush();
  }
}
