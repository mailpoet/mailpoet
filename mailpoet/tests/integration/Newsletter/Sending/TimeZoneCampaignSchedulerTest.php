<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Sending;

use Codeception\Util\Stub;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueWorker;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Newsletter\NewsletterDeleteController;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Sending\TimeZoneCampaignScheduler;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterOption;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\SendingQueue as SendingQueueFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\Util\License\Features\Data\Capability;
use MailPoet\WP\Functions as WPFunctions;

class TimeZoneCampaignSchedulerTest extends \MailPoetTest {
  /** @var NewsletterOption */
  private $newsletterOptionFactory;

  public function _before() {
    parent::_before();
    $this->newsletterOptionFactory = new NewsletterOption();
  }

  public function testItCreatesGroupedQueuesAndSnapshotsRecipients(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('Europe/Bratislava')->create();
    (new SubscriberFactory())->withSegments([$segment])->create();
    $newsletter = (new NewsletterFactory())
      ->withSubject('Time zone campaign')
      ->withDefaultBody()
      ->withSegments([$segment])
      ->create();
    $localDate = $this->getUtcDate('+3 days');
    $this->createTimeZoneScheduleOptions($newsletter, $localDate, '12:00:00');

    $scheduler = $this->getScheduler();
    $scheduler->schedule($newsletter);

    $queues = $this->diContainer->get(SendingQueuesRepository::class)->findBy(['newsletter' => $newsletter]);
    verify(count($queues))->equals(3);
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_SCHEDULED);

    $totalSubscribers = 0;
    $perTimezone = [];
    foreach ($queues as $queue) {
      $task = $queue->getTask();
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
      $meta = $queue->getMeta();
      $this->assertIsArray($meta);
      verify($meta[TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE])->true();
      $tz = $meta[TimeZoneCampaignScheduler::META_GROUP_TIMEZONE];
      $perTimezone[$tz] = ($perTimezone[$tz] ?? 0) + $queue->getCountTotal();
      $totalSubscribers += $queue->getCountTotal();
      if ($tz === 'America/New_York') {
        verify($meta[TimeZoneCampaignScheduler::META_FALLBACK_USED])->false();
        $expected = new \DateTimeImmutable("{$localDate} 12:00:00", new \DateTimeZone('America/New_York'));
        $expected = $expected->setTimezone(new \DateTimeZone('UTC'));
        $scheduledAt = $task->getScheduledAt();
        $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
        verify($scheduledAt->format('Y-m-d H:i:s'))->equals($expected->format('Y-m-d H:i:s'));
      }
    }

    verify($perTimezone['America/New_York'] ?? 0)->equals(1);
    verify($perTimezone['Europe/Bratislava'] ?? 0)->equals(2);
    verify($totalSubscribers)->equals(3);
  }

  public function testItReplacesExistingWebsiteTimeQueueWhenSchedulingByTimeZone(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    $newsletter = (new NewsletterFactory())
      ->withDefaultBody()
      ->withSegments([$segment])
      ->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('+3 days'), '12:00:00');
    $oldTask = (new ScheduledTaskFactory())->create(
      SendingQueueWorker::TASK_TYPE,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      $this->getUtcDateTime('+2 days')
    );
    $oldQueue = (new SendingQueueFactory())->create($oldTask, $newsletter);
    $oldTaskId = (int)$oldTask->getId();
    $oldQueueId = (int)$oldQueue->getId();

    $this->getScheduler()->schedule($newsletter);

    $sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    verify($sendingQueuesRepository->findOneById($oldQueueId))->null();
    verify($scheduledTasksRepository->findOneById($oldTaskId))->null();
    foreach ($sendingQueuesRepository->findBy(['newsletter' => $newsletter]) as $queue) {
      verify($this->getScheduler()->isTimeZoneQueue($queue))->true();
    }
  }

  public function testItMarksFallbackGroupExplicitly(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    (new SubscriberFactory())->withSegments([$segment])->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('+3 days'), '09:00:00');

    $this->getScheduler()->schedule($newsletter);

    $queues = $this->diContainer->get(SendingQueuesRepository::class)->findBy(['newsletter' => $newsletter]);
    $byTimezone = [];
    foreach ($queues as $queue) {
      $meta = $queue->getMeta() ?? [];
      $byTimezone[$meta[TimeZoneCampaignScheduler::META_GROUP_TIMEZONE]] = $meta;
    }

    verify($byTimezone['America/New_York'][TimeZoneCampaignScheduler::META_FALLBACK_USED])->false();
    verify($byTimezone['Europe/Bratislava'][TimeZoneCampaignScheduler::META_FALLBACK_USED])->true();
    verify($byTimezone['Europe/Bratislava'][TimeZoneCampaignScheduler::META_SITE_TIMEZONE])->equals('Europe/Bratislava');
  }

  public function testItRejectsRestrictedCapability(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('Europe/Bratislava')->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('+3 days'), '12:00:00');

    $this->expectException(\Exception::class);
    $this->expectExceptionCode(403);

    $this->getScheduler(true)->schedule($newsletter);
  }

  public function testItRejectsPastTimeZoneGroups(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('Europe/Bratislava')->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('-1 day'), '12:00:00');

    $this->expectException(\Exception::class);
    $this->expectExceptionCode(400);
    $this->expectExceptionMessageMatches('/already passed/');

    $this->getScheduler()->schedule($newsletter);
  }

  public function testItRejectsLeadTimeBelow24Hours(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $tooSoon = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('+12 hours');
    $this->createTimeZoneScheduleOptions(
      $newsletter,
      $tooSoon->format('Y-m-d'),
      $this->roundDownToQuarterHour($tooSoon)->format('H:i:s')
    );

    $this->expectException(\Exception::class);
    $this->expectExceptionCode(400);
    $this->expectExceptionMessageMatches('/lead time/');

    $this->getScheduler(false, 'UTC')->schedule($newsletter);
  }

  public function testItRejectsTimeOutsideQuarterHourBoundary(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('+3 days'), '09:07:00');

    $this->expectException(\Exception::class);
    $this->expectExceptionCode(400);
    $this->expectExceptionMessageMatches('/scheduled time/');

    $this->getScheduler()->schedule($newsletter);
  }

  public function testItRejectsTimeWithNonZeroSeconds(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('+3 days'), '09:00:30');

    $this->expectException(\Exception::class);
    $this->expectExceptionCode(400);
    $this->expectExceptionMessageMatches('/scheduled time/');

    $this->getScheduler()->schedule($newsletter);
  }

  public function testItHandlesDstAwareConversion(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    // US DST starts on 2027-03-14, so 2027-03-15 is firmly inside EDT (UTC-4).
    $this->createTimeZoneScheduleOptions($newsletter, '2027-03-15', '09:00:00');

    $this->getScheduler()->schedule($newsletter);

    $queues = $this->diContainer->get(SendingQueuesRepository::class)->findBy(['newsletter' => $newsletter]);
    $task = $queues[0]->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $scheduledAt = $task->getScheduledAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $scheduledAt);
    // 09:00 EDT (UTC-4) on 2027-03-15 is 13:00 UTC, not 14:00 (which would be EST UTC-5).
    verify($scheduledAt->format('Y-m-d H:i:s'))->equals('2027-03-15 13:00:00');
  }

  public function testItRefusesReplaceWhenAQueueAlreadyStarted(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('Europe/Bratislava')->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('+3 days'), '12:00:00');

    $this->getScheduler()->schedule($newsletter);
    $queues = $this->diContainer->get(SendingQueuesRepository::class)->findBy(['newsletter' => $newsletter]);
    verify(count($queues))->greaterThanOrEqual(2);

    $queues[0]->setCountProcessed(1);
    $this->entityManager->flush();

    verify($this->getScheduler()->canReplaceScheduledCampaign($newsletter))->false();

    $this->expectException(\Exception::class);
    $this->expectExceptionCode(400);
    $this->expectExceptionMessageMatches('/can no longer be edited/');

    $this->getScheduler()->schedule($newsletter);
  }

  public function testHasIncompleteCampaignQueuesReportsAggregateState(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('Europe/Bratislava')->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('+3 days'), '12:00:00');

    $scheduler = $this->getScheduler();
    $scheduler->schedule($newsletter);

    $queues = $this->diContainer->get(SendingQueuesRepository::class)->findBy(['newsletter' => $newsletter]);
    foreach ($queues as $queue) {
      $task = $queue->getTask();
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    }
    // Mark one as still scheduled.
    $firstTask = $queues[0]->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $firstTask);
    $firstTask->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->entityManager->flush();

    verify($scheduler->hasIncompleteCampaignQueues($queues[1]))->true();

    $firstTask->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $this->entityManager->flush();

    verify($scheduler->hasIncompleteCampaignQueues($queues[1]))->false();
  }

  public function testAggregateStatusIsDeterministicAcrossMixedBatchStatuses(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('Europe/Bratislava')->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('+3 days'), '12:00:00');

    $scheduler = $this->getScheduler();
    $scheduler->schedule($newsletter);

    $queues = $this->diContainer->get(SendingQueuesRepository::class)->findBy(['newsletter' => $newsletter]);
    verify(count($queues))->equals(2);
    $tasks = [];
    foreach ($queues as $queue) {
      $task = $queue->getTask();
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      $tasks[] = $task;
    }

    $assertAggregateStatus = function (string $expected) use ($scheduler, $queues): void {
      $data = $scheduler->getAggregateQueueData($queues[0]);
      $this->assertIsArray($data);
      verify($data['status'])->equals($expected);
    };
    $assertAggregateStatusNull = function () use ($scheduler, $queues): void {
      $data = $scheduler->getAggregateQueueData($queues[0]);
      $this->assertIsArray($data);
      verify($data['status'])->null();
    };

    // Mix of CANCELLED + SCHEDULED must report SCHEDULED (a batch is still pending).
    // Previously this returned whichever status came first in the sorted queue list.
    $tasks[0]->setStatus(ScheduledTaskEntity::STATUS_CANCELLED);
    $tasks[1]->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->entityManager->flush();
    $assertAggregateStatus(ScheduledTaskEntity::STATUS_SCHEDULED);

    // Order independence: the result must not depend on which batch holds CANCELLED.
    $tasks[0]->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $tasks[1]->setStatus(ScheduledTaskEntity::STATUS_CANCELLED);
    $this->entityManager->flush();
    $assertAggregateStatus(ScheduledTaskEntity::STATUS_SCHEDULED);

    // PAUSED dominates everything because it requires user action.
    $tasks[0]->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
    $tasks[1]->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $this->entityManager->flush();
    $assertAggregateStatus(ScheduledTaskEntity::STATUS_PAUSED);

    // A null status (active sending) wins over any terminal sibling.
    $tasks[0]->setStatus(null);
    $tasks[1]->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $this->entityManager->flush();
    $assertAggregateStatusNull();

    // Purely terminal mix prefers COMPLETED (some progress was made) over CANCELLED.
    $tasks[0]->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $tasks[1]->setStatus(ScheduledTaskEntity::STATUS_CANCELLED);
    $this->entityManager->flush();
    $assertAggregateStatus(ScheduledTaskEntity::STATUS_COMPLETED);
  }

  public function testPauseAndResumeAffectAllSiblings(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('Europe/Bratislava')->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('+3 days'), '12:00:00');

    $scheduler = $this->getScheduler();
    $scheduler->schedule($newsletter);

    $queues = $this->diContainer->get(SendingQueuesRepository::class)->findBy(['newsletter' => $newsletter]);
    $scheduler->pauseCampaign($queues[0]);

    foreach ($queues as $queue) {
      $task = $queue->getTask();
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);
    }

    $scheduler->resumeCampaign($queues[0]);

    foreach ($queues as $queue) {
      $task = $queue->getTask();
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
    }
  }

  public function testResumeDoesNotDemoteFullySentCampaign(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('Europe/Bratislava')->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('+3 days'), '12:00:00');

    $scheduler = $this->getScheduler();
    $scheduler->schedule($newsletter);

    // Simulate every batch having finished sending: counts equal and tasks completed,
    // newsletter marked as SENT (the state markNewsletterAsSent leaves the campaign in).
    $queues = $this->diContainer->get(SendingQueuesRepository::class)->findBy(['newsletter' => $newsletter]);
    foreach ($queues as $queue) {
      $task = $queue->getTask();
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      $queue->setCountProcessed($queue->getCountTotal());
      $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    }
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->flush();

    $scheduler->resumeCampaign($queues[0]);

    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_SENT);
    foreach ($queues as $queue) {
      $task = $queue->getTask();
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_COMPLETED);
    }
  }

  public function testPauseStillPausesQueuesWithZeroCounts(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('Europe/Bratislava')->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('+3 days'), '12:00:00');

    $scheduler = $this->getScheduler();
    $scheduler->schedule($newsletter);

    $queues = $this->diContainer->get(SendingQueuesRepository::class)->findBy(['newsletter' => $newsletter]);
    // Force one batch into the "both counts are zero" edge case the bug exploits.
    $queues[0]->setCountTotal(0);
    $queues[0]->setCountProcessed(0);
    $this->entityManager->flush();

    $scheduler->pauseCampaign($queues[0]);

    foreach ($queues as $queue) {
      $task = $queue->getTask();
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      verify($task->getStatus())->equals(ScheduledTaskEntity::STATUS_PAUSED);
    }
  }

  public function testNewsletterDeleteRemovesAllSiblingQueuesAndSubscribers(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('Europe/Bratislava')->create();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('+3 days'), '12:00:00');

    $this->getScheduler()->schedule($newsletter);

    $sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
    $scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $scheduledTaskSubscribersRepository = $this->diContainer->get(ScheduledTaskSubscribersRepository::class);
    $newsletterId = (int)$newsletter->getId();

    $queueIds = array_map(fn(SendingQueueEntity $queue): int => (int)$queue->getId(), $sendingQueuesRepository->findBy(['newsletter' => $newsletter]));
    verify(count($queueIds))->greaterThanOrEqual(2);

    $taskIds = [];
    foreach ($sendingQueuesRepository->findBy(['newsletter' => $newsletter]) as $queue) {
      $task = $queue->getTask();
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      $taskIds[] = (int)$task->getId();
    }

    $totalTaskSubscribers = 0;
    foreach ($taskIds as $taskId) {
      $totalTaskSubscribers += count($scheduledTaskSubscribersRepository->findBy(['task' => $taskId]));
    }
    verify($totalTaskSubscribers)->greaterThanOrEqual(2);

    $this->diContainer->get(NewsletterDeleteController::class)->bulkDelete([$newsletterId]);

    verify($sendingQueuesRepository->findBy(['newsletter' => $newsletterId]))->equals([]);
    foreach ($taskIds as $taskId) {
      verify($scheduledTasksRepository->findOneById($taskId))->null();
      verify($scheduledTaskSubscribersRepository->findBy(['task' => $taskId]))->equals([]);
    }
  }

  public function testItOnlyAttachesSubscribedRecipientsAtScheduleTime(): void {
    $segment = (new SegmentFactory())->create();
    (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    $unsubscribed = (new SubscriberFactory())->withSegments([$segment])->withTimeZone('America/New_York')->create();
    $unsubscribed->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->entityManager->flush();
    $newsletter = (new NewsletterFactory())->withDefaultBody()->withSegments([$segment])->create();
    $this->createTimeZoneScheduleOptions($newsletter, $this->getUtcDate('+3 days'), '12:00:00');

    $this->getScheduler()->schedule($newsletter);

    $queues = $this->diContainer->get(SendingQueuesRepository::class)->findBy(['newsletter' => $newsletter]);
    $total = 0;
    foreach ($queues as $queue) {
      $total += $queue->getCountTotal();
    }
    verify($total)->equals(1);
  }

  private function createTimeZoneScheduleOptions(NewsletterEntity $newsletter, string $localDate, string $localTime): void {
    $this->newsletterOptionFactory->createMultipleOptions($newsletter, [
      NewsletterOptionFieldEntity::NAME_IS_SCHEDULED => '1',
      NewsletterOptionFieldEntity::NAME_SCHEDULE_MODE => TimeZoneCampaignScheduler::SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
      NewsletterOptionFieldEntity::NAME_SCHEDULED_LOCAL_DATE => $localDate,
      NewsletterOptionFieldEntity::NAME_SCHEDULED_LOCAL_TIME => $localTime,
      NewsletterOptionFieldEntity::NAME_SCHEDULED_AT => $this->getUtcDateTime('+3 days')->format('Y-m-d H:i:s'),
    ]);
  }

  private function getUtcDate(string $modifier): string {
    return $this->getUtcDateTime($modifier)->format('Y-m-d');
  }

  private function getUtcDateTime(string $modifier): \DateTimeImmutable {
    return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify($modifier);
  }

  private function roundDownToQuarterHour(\DateTimeImmutable $dateTime): \DateTimeImmutable {
    $minutes = (int)$dateTime->format('i');
    $rounded = $minutes - ($minutes % 15);
    return $dateTime->setTime((int)$dateTime->format('H'), $rounded, 0);
  }

  private function getScheduler(bool $restricted = false, string $siteTimeZone = 'Europe/Bratislava'): TimeZoneCampaignScheduler {
    return $this->getServiceWithOverrides(TimeZoneCampaignScheduler::class, [
      'capabilitiesManager' => Stub::makeEmpty(CapabilitiesManager::class, [
        'getCapability' => new Capability('sendByTimezone', Capability::TYPE_BOOLEAN, $restricted),
      ]),
      'featuresController' => Stub::make(FeaturesController::class, [
        'isSupported' => true,
      ]),
      'wp' => Stub::make(WPFunctions::class, [
        'wpTimezone' => new \DateTimeZone($siteTimeZone),
      ]),
    ]);
  }
}
