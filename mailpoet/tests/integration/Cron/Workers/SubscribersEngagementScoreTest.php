<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;

class SubscribersEngagementScoreTest extends \MailPoetTest {
  /** @var SubscribersEngagementScore */
  private $worker;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(SubscribersEngagementScore::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
  }

  public function testItRecalculatesAllDueSubscribersAcrossMultipleBatchesInSingleRun() {
    // More than one batch to prove the worker keeps looping instead of stopping after BATCH_SIZE.
    $this->createSubscribers(SubscribersEngagementScore::BATCH_SIZE + 2);

    $result = $this->worker->processTaskStrategy(new ScheduledTaskEntity(), microtime(true));

    verify($result)->true();
    $remaining = $this->subscribersRepository->findByUpdatedScoreNotInLastMonth(SubscribersEngagementScore::BATCH_SIZE);
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
    verify($exception->getMessage())->stringStartsWith('The maximum execution time');
    $remainingAfterInterrupt = $this->subscribersRepository->findByUpdatedScoreNotInLastMonth(SubscribersEngagementScore::BATCH_SIZE);
    verify(count($remainingAfterInterrupt))->equals(2);

    $result = $this->worker->processTaskStrategy($task, microtime(true));

    verify($result)->true();
    $remaining = $this->subscribersRepository->findByUpdatedScoreNotInLastMonth(SubscribersEngagementScore::BATCH_SIZE);
    verify(count($remaining))->equals(0);
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
