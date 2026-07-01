<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\InactiveSubscribersMaintenance;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\InactiveSubscribersController;
use MailPoet\Subscribers\SubscribersEmailCountsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoetVendor\Carbon\Carbon;

class InactiveSubscribersMaintenanceTest extends \MailPoetTest {
  /** @var CronHelper */
  private $cronHelper;

  /** @var SettingsController */
  private $settings;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var ScheduledTaskFactory */
  private $scheduledTaskFactory;

  /** @var NewsletterEntity */
  private $newsletter;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->scheduledTaskFactory = new ScheduledTaskFactory();
    $this->cronHelper = ContainerWrapper::getInstance()->get(CronHelper::class);
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_PARTIAL);
    $this->settings->set('deactivate_subscriber_after_inactive_days', 5);
    $this->settings->delete(InactiveSubscribersMaintenance::LAST_EMAIL_COUNT_AT_SETTING);

    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->entityManager->getConnection()->executeQuery('DROP TABLE IF EXISTS inactive_task_ids');
    $this->entityManager->getConnection()->executeQuery('DROP TABLE IF EXISTS inactive_subscriber_ids');

    $this->newsletter = new NewsletterEntity();
    $this->newsletter->setSubject('Subject');
    $this->newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->persist($this->newsletter);
    $this->entityManager->flush();
  }

  public function testItDoesNotRunWhenTrackingIsDisabled(): void {
    $this->settings->set('tracking.level', TrackingConfig::LEVEL_BASIC);
    $inactiveSubscribersController = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Expected::never(),
      'markActiveSubscribers' => Expected::never(),
      'reactivateInactiveSubscribers' => Expected::never(),
    ], $this);
    $subscribersEmailCountsController = Stub::make(SubscribersEmailCountsController::class, [
      'updateSubscribersEmailCounts' => Expected::never(),
      'hasNewSendingTasksSince' => Expected::never(),
    ], $this);

    $worker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'inactiveSubscribersController' => $inactiveSubscribersController,
      'subscribersEmailCountsController' => $subscribersEmailCountsController,
    ]);
    $worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    $task = $this->findScheduledMaintenanceTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $this->assertInstanceOf(\DateTimeInterface::class, $task->getScheduledAt());
    verify($task->getScheduledAt())->greaterThan(new Carbon());
  }

  public function testItReactivatesInactiveSubscribersWhenInactivityIsDisabled(): void {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 0);
    $inactiveSubscribersController = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Expected::never(),
      'markActiveSubscribers' => Expected::never(),
      'reactivateInactiveSubscribers' => Expected::once(),
    ], $this);
    $subscribersEmailCountsController = Stub::make(SubscribersEmailCountsController::class, [
      'updateSubscribersEmailCounts' => Expected::never(),
      'hasNewSendingTasksSince' => Expected::never(),
    ], $this);

    $worker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'inactiveSubscribersController' => $inactiveSubscribersController,
      'subscribersEmailCountsController' => $subscribersEmailCountsController,
    ]);
    $worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    $task = $this->findScheduledMaintenanceTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $this->assertInstanceOf(\DateTimeInterface::class, $task->getScheduledAt());
    verify($task->getScheduledAt())->greaterThan(new Carbon());
  }

  public function testItRefreshesStaleEmailCountBeforeMarkingInactive(): void {
    $subscriber = $this->createSubscriber('stale@email.com', 20, SubscriberEntity::STATUS_SUBSCRIBED, 0);
    $this->createCompletedSendingTasksForSubscriber($subscriber, 7, 10);
    $this->createCompletedSendingTasksForSubscriber($subscriber, 3, 3);

    $worker = $this->diContainer->get(InactiveSubscribersMaintenance::class);
    $worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    $this->entityManager->clear();
    $subscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber->getEmailCount())->equals(10);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_INACTIVE);
  }

  public function testItUsesStoredSettingAsIncrementalBaseline(): void {
    $this->createSubscribers(1);
    $baselineDate = (new Carbon())->subDays(4);
    $this->settings->set(InactiveSubscribersMaintenance::LAST_EMAIL_COUNT_AT_SETTING, $baselineDate->format(\DateTimeInterface::ATOM));
    $hasNewSendingTasksDates = [];
    $emailCountDates = [];

    $subscribersEmailCountsController = Stub::make(SubscribersEmailCountsController::class, [
      'updateSubscribersEmailCounts' => Expected::once(function($dateLastProcessed, $startId, $endId) use (&$emailCountDates) {
        $emailCountDates[] = $dateLastProcessed;
        return 0;
      }),
      'hasNewSendingTasksSince' => Expected::once(function($dateLastProcessed) use (&$hasNewSendingTasksDates) {
        $hasNewSendingTasksDates[] = $dateLastProcessed;
        return true;
      }),
    ], $this);
    $inactiveSubscribersController = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Expected::once(0),
      'markActiveSubscribers' => Expected::once(0),
      'reactivateInactiveSubscribers' => Expected::never(),
    ], $this);

    $worker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => $subscribersEmailCountsController,
      'inactiveSubscribersController' => $inactiveSubscribersController,
    ]);
    $worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    verify($this->formatDates($hasNewSendingTasksDates))->equals([$baselineDate->format('Y-m-d H:i:s')]);
    verify($this->formatDates($emailCountDates))->equals([$baselineDate->format('Y-m-d H:i:s')]);
  }

  public function testItKeepsEmailCountBaselineAcrossDisabledPeriod(): void {
    $this->createSubscribers(1);
    $firstRunScheduledAt = (new Carbon())->subDays(10);

    // First run counts and records the baseline (the run cutoff, not the scheduled time).
    $firstRunDates = [];
    $firstRunCutoffs = [];
    $firstRunWorker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => Stub::make(SubscribersEmailCountsController::class, [
        'updateSubscribersEmailCounts' => Expected::once(function($dateLastProcessed, $startId, $endId, $now) use (&$firstRunDates, &$firstRunCutoffs) {
          $firstRunDates[] = $dateLastProcessed;
          $firstRunCutoffs[] = $now;
          return 0;
        }),
        'hasNewSendingTasksSince' => Expected::never(),
      ], $this),
      'inactiveSubscribersController' => Stub::make(InactiveSubscribersController::class, [
        'markInactiveSubscribers' => Expected::once(0),
        'markActiveSubscribers' => Expected::once(0),
        'reactivateInactiveSubscribers' => Expected::never(),
      ], $this),
    ]);
    $firstRunWorker->processTaskStrategy(
      $this->scheduledTaskFactory->create(InactiveSubscribersMaintenance::TASK_TYPE, null, $firstRunScheduledAt),
      microtime(true)
    );

    // Inactivity detection is turned off: the run skips counting and must not move the baseline.
    $this->settings->set('deactivate_subscriber_after_inactive_days', 0);
    $disabledRunWorker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => Stub::make(SubscribersEmailCountsController::class, [
        'updateSubscribersEmailCounts' => Expected::never(),
        'hasNewSendingTasksSince' => Expected::never(),
      ], $this),
      'inactiveSubscribersController' => Stub::make(InactiveSubscribersController::class, [
        'markInactiveSubscribers' => Expected::never(),
        'markActiveSubscribers' => Expected::never(),
        'reactivateInactiveSubscribers' => Expected::once(),
      ], $this),
    ]);
    $disabledRunWorker->processTaskStrategy(
      $this->scheduledTaskFactory->create(InactiveSubscribersMaintenance::TASK_TYPE, null, (new Carbon())->subDays(5)),
      microtime(true)
    );

    // Re-enabled: the incremental baseline must still be the first (last counted) run, not the disabled one.
    $this->settings->set('deactivate_subscriber_after_inactive_days', 5);
    $reenabledRunDates = [];
    $reenabledRunWorker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => Stub::make(SubscribersEmailCountsController::class, [
        'updateSubscribersEmailCounts' => Expected::once(function($dateLastProcessed, $startId, $endId) use (&$reenabledRunDates) {
          $reenabledRunDates[] = $dateLastProcessed;
          return 0;
        }),
        'hasNewSendingTasksSince' => Expected::once(true),
      ], $this),
      'inactiveSubscribersController' => Stub::make(InactiveSubscribersController::class, [
        'markInactiveSubscribers' => Expected::once(0),
        'markActiveSubscribers' => Expected::once(0),
        'reactivateInactiveSubscribers' => Expected::never(),
      ], $this),
    ]);
    $reenabledRunWorker->processTaskStrategy($this->createRunningTask(), microtime(true));

    verify($firstRunDates)->equals([null]);
    $this->assertCount(1, $firstRunCutoffs);
    verify($this->formatDates($reenabledRunDates))->equals([$firstRunCutoffs[0]->format('Y-m-d H:i:s')]);
  }

  public function testItStoresRunCutoffAsBaselineNotScheduledTime(): void {
    $this->createSubscribers(1);
    $scheduledLongAgo = (new Carbon())->subDays(10);
    $capturedCutoffs = [];

    $worker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => Stub::make(SubscribersEmailCountsController::class, [
        'updateSubscribersEmailCounts' => Expected::once(function($dateLastProcessed, $startId, $endId, $now) use (&$capturedCutoffs) {
          $capturedCutoffs[] = $now;
          return 0;
        }),
        'hasNewSendingTasksSince' => Expected::never(),
      ], $this),
      'inactiveSubscribersController' => Stub::make(InactiveSubscribersController::class, [
        'markInactiveSubscribers' => Expected::once(0),
        'markActiveSubscribers' => Expected::once(0),
        'reactivateInactiveSubscribers' => Expected::never(),
      ], $this),
    ]);
    // Task scheduled long ago (as if cron ran late) -- the baseline must be the run cutoff, not scheduledAt.
    $worker->processTaskStrategy(
      $this->scheduledTaskFactory->create(InactiveSubscribersMaintenance::TASK_TYPE, null, $scheduledLongAgo),
      microtime(true)
    );

    $stored = $this->settings->get(InactiveSubscribersMaintenance::LAST_EMAIL_COUNT_AT_SETTING);
    $this->assertIsString($stored);
    $this->assertCount(1, $capturedCutoffs);
    verify(Carbon::parse($stored)->format('Y-m-d H:i:s'))->equals($capturedCutoffs[0]->format('Y-m-d H:i:s'));
    verify(Carbon::parse($stored)->getTimestamp())->greaterThan($scheduledLongAgo->getTimestamp());
  }

  public function testItDoesNotRecountAWindowWhenMarkingFails(): void {
    $subscriberIds = $this->createSubscribers(1);
    $task = $this->createRunningTask();

    // First run: counting succeeds and the cursor is committed, then marking throws.
    $firstRunUpdateCalls = 0;
    $firstRunWorker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => Stub::make(SubscribersEmailCountsController::class, [
        'updateSubscribersEmailCounts' => Expected::once(function() use (&$firstRunUpdateCalls) {
          $firstRunUpdateCalls++;
          return 0;
        }),
        'hasNewSendingTasksSince' => Expected::never(),
      ], $this),
      'inactiveSubscribersController' => Stub::make(InactiveSubscribersController::class, [
        'markInactiveSubscribers' => Expected::once(function() {
          throw new \RuntimeException('marking failed');
        }),
        'markActiveSubscribers' => Expected::never(),
        'reactivateInactiveSubscribers' => Expected::never(),
      ], $this),
    ]);

    try {
      $firstRunWorker->processTaskStrategy($task, microtime(true));
      $this->fail('Expected the marking failure to bubble up.');
    } catch (\RuntimeException $e) {
      verify($e->getMessage())->equals('marking failed');
    }

    // Cursor was committed before marking, so the counted window will not be reprocessed.
    $this->entityManager->clear();
    $task = $this->scheduledTasksRepository->findOneById((int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $meta = $task->getMeta();
    $this->assertIsArray($meta);
    verify($meta['last_subscriber_id'])->equals($subscriberIds[0] + 1);

    // Resume: the already-counted window is not counted again.
    $secondRunWorker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => Stub::make(SubscribersEmailCountsController::class, [
        'updateSubscribersEmailCounts' => Expected::never(),
        'hasNewSendingTasksSince' => Expected::never(),
      ], $this),
      'inactiveSubscribersController' => Stub::make(InactiveSubscribersController::class, [
        'markInactiveSubscribers' => Expected::never(),
        'markActiveSubscribers' => Expected::once(0),
        'reactivateInactiveSubscribers' => Expected::never(),
      ], $this),
    ]);
    $secondRunWorker->processTaskStrategy($task, microtime(true));

    verify($firstRunUpdateCalls)->equals(1);
  }

  public function testItResetsBaselineWhenPartiallyCountedRunIsDisabled(): void {
    $this->createSubscribers(1);
    // A prior completed run left a baseline; a later run counted one window then stalled,
    // persisting its cursor and frozen cutoff but not yet the baseline.
    $this->settings->set(InactiveSubscribersMaintenance::LAST_EMAIL_COUNT_AT_SETTING, (new Carbon())->subDays(3)->format(\DateTimeInterface::ATOM));
    $partialTask = $this->createRunningTask();
    $partialTask->setMeta([
      'last_subscriber_id' => 1,
      'email_count_cutoff' => (new Carbon())->format(\DateTimeInterface::ATOM),
    ]);
    $this->entityManager->flush();

    // Inactivity gets disabled before the partial run resumes -> it completes via the early
    // return and must drop the now-stale baseline.
    $this->settings->set('deactivate_subscriber_after_inactive_days', 0);
    $disabledWorker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => Stub::make(SubscribersEmailCountsController::class, [
        'updateSubscribersEmailCounts' => Expected::never(),
        'hasNewSendingTasksSince' => Expected::never(),
      ], $this),
      'inactiveSubscribersController' => Stub::make(InactiveSubscribersController::class, [
        'markInactiveSubscribers' => Expected::never(),
        'markActiveSubscribers' => Expected::never(),
        'reactivateInactiveSubscribers' => Expected::once(),
      ], $this),
    ]);
    $disabledWorker->processTaskStrategy($partialTask, microtime(true));

    verify($this->settings->get(InactiveSubscribersMaintenance::LAST_EMAIL_COUNT_AT_SETTING))->null();

    // Re-enabled: with no baseline the next run full-recounts (dateLastProcessed = null),
    // resetting counts instead of incrementing the already-counted window again.
    $this->settings->set('deactivate_subscriber_after_inactive_days', 5);
    $reenabledDates = [];
    $reenabledWorker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => Stub::make(SubscribersEmailCountsController::class, [
        'updateSubscribersEmailCounts' => Expected::once(function($dateLastProcessed, $startId, $endId, $now) use (&$reenabledDates) {
          $reenabledDates[] = $dateLastProcessed;
          return 0;
        }),
        'hasNewSendingTasksSince' => Expected::never(),
      ], $this),
      'inactiveSubscribersController' => Stub::make(InactiveSubscribersController::class, [
        'markInactiveSubscribers' => Expected::once(0),
        'markActiveSubscribers' => Expected::once(0),
        'reactivateInactiveSubscribers' => Expected::never(),
      ], $this),
    ]);
    $reenabledWorker->processTaskStrategy($this->createRunningTask(), microtime(true));

    verify($reenabledDates)->equals([null]);
  }

  public function testItProcessesAllSubscriberWindows(): void {
    $subscriberIds = $this->createSubscribers(InactiveSubscribersMaintenance::BATCH_SIZE + 1);
    $emailCountCalls = [];
    $inactiveCalls = [];
    $subscribersEmailCountsController = Stub::make(SubscribersEmailCountsController::class, [
      'updateSubscribersEmailCounts' => Expected::exactly(2, function($dateLastProcessed, $startId, $endId) use (&$emailCountCalls) {
        $emailCountCalls[] = [$startId, $endId];
        return 0;
      }),
      'hasNewSendingTasksSince' => Expected::never(),
    ], $this);
    $inactiveSubscribersController = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Expected::exactly(2, function($daysToInactive, $startId, $endId) use (&$inactiveCalls) {
        $inactiveCalls[] = [$daysToInactive, $startId, $endId];
        return 0;
      }),
      'markActiveSubscribers' => Expected::once(0),
      'reactivateInactiveSubscribers' => Expected::never(),
    ], $this);

    $worker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => $subscribersEmailCountsController,
      'inactiveSubscribersController' => $inactiveSubscribersController,
    ]);
    $worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    // The cursor starts at 0 (lower bound), so the first window's startId is 0, not the first subscriber id.
    verify($emailCountCalls)->equals([
      [0, $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE - 1]],
      [$subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE], $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE]],
    ]);
    verify($inactiveCalls)->equals([
      [5, 0, $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE - 1]],
      [5, $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE], $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE]],
    ]);
  }

  public function testItPersistsProgressAndResumesAfterExecutionLimit(): void {
    $subscriberIds = $this->createSubscribers(InactiveSubscribersMaintenance::BATCH_SIZE + 1);
    $task = $this->createRunningTask();
    $firstRunEmailCountCalls = [];
    $firstRunInactiveCalls = [];
    $firstRunWorker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => Stub::make(SubscribersEmailCountsController::class, [
        'updateSubscribersEmailCounts' => Expected::once(function($dateLastProcessed, $startId, $endId) use (&$firstRunEmailCountCalls) {
          $firstRunEmailCountCalls[] = [$startId, $endId];
          return 0;
        }),
        'hasNewSendingTasksSince' => Expected::never(),
      ], $this),
      'inactiveSubscribersController' => Stub::make(InactiveSubscribersController::class, [
        'markInactiveSubscribers' => Expected::once(function($daysToInactive, $startId, $endId) use (&$firstRunInactiveCalls) {
          $firstRunInactiveCalls[] = [$startId, $endId];
          return 0;
        }),
        'markActiveSubscribers' => Expected::never(),
        'reactivateInactiveSubscribers' => Expected::never(),
      ], $this),
    ]);

    try {
      $firstRunWorker->processTaskStrategy($task, microtime(true) - $this->cronHelper->getDaemonExecutionLimit());
      $this->fail('Expected the worker to stop at the daemon execution limit.');
    } catch (\Exception $e) {
      verify($e->getCode())->equals(CronHelper::DAEMON_EXECUTION_LIMIT_REACHED);
    }

    $this->entityManager->clear();
    $task = $this->scheduledTasksRepository->findOneById((int)$task->getId());
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $meta = $task->getMeta();
    $this->assertIsArray($meta);
    verify($meta['last_subscriber_id'])->equals($subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE]);
    verify($firstRunEmailCountCalls)->equals([[0, $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE - 1]]]);
    verify($firstRunInactiveCalls)->equals([[0, $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE - 1]]]);

    $secondRunEmailCountCalls = [];
    $secondRunInactiveCalls = [];
    $secondRunWorker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => Stub::make(SubscribersEmailCountsController::class, [
        'updateSubscribersEmailCounts' => Expected::once(function($dateLastProcessed, $startId, $endId) use (&$secondRunEmailCountCalls) {
          $secondRunEmailCountCalls[] = [$startId, $endId];
          return 0;
        }),
        'hasNewSendingTasksSince' => Expected::never(),
      ], $this),
      'inactiveSubscribersController' => Stub::make(InactiveSubscribersController::class, [
        'markInactiveSubscribers' => Expected::once(function($daysToInactive, $startId, $endId) use (&$secondRunInactiveCalls) {
          $secondRunInactiveCalls[] = [$startId, $endId];
          return 0;
        }),
        'markActiveSubscribers' => Expected::once(0),
        'reactivateInactiveSubscribers' => Expected::never(),
      ], $this),
    ]);

    $secondRunWorker->processTaskStrategy($task, microtime(true));

    verify($secondRunEmailCountCalls)->equals([[
      $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE],
      $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE],
    ]]);
    verify($secondRunInactiveCalls)->equals([[
      $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE],
      $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE],
    ]]);
  }

  public function testItRunsReactivationAfterDeactivationWindows(): void {
    $subscriberIds = $this->createSubscribers(1);
    $calls = [];
    $subscribersEmailCountsController = Stub::make(SubscribersEmailCountsController::class, [
      'updateSubscribersEmailCounts' => Expected::once(function($dateLastProcessed, $startId, $endId) use (&$calls) {
        $calls[] = ['update', $startId, $endId];
        return 0;
      }),
      'hasNewSendingTasksSince' => Expected::never(),
    ], $this);
    $inactiveSubscribersController = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Expected::once(function($daysToInactive, $startId, $endId) use (&$calls) {
        $calls[] = ['inactive', $startId, $endId];
        return 0;
      }),
      'markActiveSubscribers' => Expected::once(function($daysToInactive, $batchSize) use (&$calls) {
        $calls[] = ['active', $batchSize];
        return 0;
      }),
      'reactivateInactiveSubscribers' => Expected::never(),
    ], $this);

    $worker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => $subscribersEmailCountsController,
      'inactiveSubscribersController' => $inactiveSubscribersController,
    ]);
    $worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    verify($calls)->equals([
      ['update', 0, $subscriberIds[0]],
      ['inactive', 0, $subscriberIds[0]],
      ['active', InactiveSubscribersMaintenance::BATCH_SIZE],
    ]);
  }

  public function testItSchedulesNextRunWhenFinished(): void {
    $inactiveSubscribersController = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Expected::never(),
      'markActiveSubscribers' => Expected::once(0),
      'reactivateInactiveSubscribers' => Expected::never(),
    ], $this);
    $subscribersEmailCountsController = Stub::make(SubscribersEmailCountsController::class, [
      'updateSubscribersEmailCounts' => Expected::never(),
      'hasNewSendingTasksSince' => Expected::never(),
    ], $this);

    $worker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'inactiveSubscribersController' => $inactiveSubscribersController,
      'subscribersEmailCountsController' => $subscribersEmailCountsController,
    ]);
    $worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    $task = $this->findScheduledMaintenanceTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $this->assertInstanceOf(\DateTimeInterface::class, $task->getScheduledAt());
    verify($task->getScheduledAt())->greaterThan(new Carbon());
  }

  public function testItUpdatesEmailCountsBeforeMarkingInactiveForEachWindow(): void {
    $subscriberIds = $this->createSubscribers(InactiveSubscribersMaintenance::BATCH_SIZE + 1);
    $calls = [];
    $subscribersEmailCountsController = Stub::make(SubscribersEmailCountsController::class, [
      'updateSubscribersEmailCounts' => Expected::exactly(2, function($dateLastProcessed, $startId, $endId) use (&$calls) {
        $calls[] = ['update', $startId, $endId];
        return 0;
      }),
      'hasNewSendingTasksSince' => Expected::never(),
    ], $this);
    $inactiveSubscribersController = Stub::make(InactiveSubscribersController::class, [
      'markInactiveSubscribers' => Expected::exactly(2, function($daysToInactive, $startId, $endId) use (&$calls) {
        $calls[] = ['inactive', $startId, $endId];
        return 0;
      }),
      'markActiveSubscribers' => Expected::once(0),
      'reactivateInactiveSubscribers' => Expected::never(),
    ], $this);

    $worker = $this->getServiceWithOverrides(InactiveSubscribersMaintenance::class, [
      'subscribersEmailCountsController' => $subscribersEmailCountsController,
      'inactiveSubscribersController' => $inactiveSubscribersController,
    ]);
    $worker->processTaskStrategy($this->createRunningTask(), microtime(true));

    verify($calls)->equals([
      ['update', 0, $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE - 1]],
      ['inactive', 0, $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE - 1]],
      ['update', $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE], $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE]],
      ['inactive', $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE], $subscriberIds[InactiveSubscribersMaintenance::BATCH_SIZE]],
    ]);
  }

  private function createRunningTask(): ScheduledTaskEntity {
    return $this->scheduledTaskFactory->create(
      InactiveSubscribersMaintenance::TASK_TYPE,
      null,
      Carbon::now()
    );
  }

  private function findScheduledMaintenanceTask(): ?ScheduledTaskEntity {
    return $this->scheduledTasksRepository->findOneBy([
      'type' => InactiveSubscribersMaintenance::TASK_TYPE,
      'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
    ], ['createdAt' => 'DESC']);
  }

  /**
   * @param \DateTimeInterface[] $dates
   * @return string[]
   */
  private function formatDates(array $dates): array {
    return array_map(function(\DateTimeInterface $date): string {
      return $date->format('Y-m-d H:i:s');
    }, $dates);
  }

  private function createSubscriber(
    string $email,
    int $createdDaysAgo = 0,
    string $status = SubscriberEntity::STATUS_SUBSCRIBED,
    int $emailCount = 0
  ): SubscriberEntity {
    $createdAt = (new Carbon())->subDays($createdDaysAgo);
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setStatus($status);
    $subscriber->setCreatedAt($createdAt);
    $subscriber->setEmailCount($emailCount);
    $this->entityManager->persist($subscriber);
    $subscriber->setLastSubscribedAt($createdAt);
    $this->entityManager->flush();
    return $subscriber;
  }

  /**
   * @return int[]
   */
  private function createSubscribers(int $count): array {
    $createdAt = (new Carbon())->subDays(20);
    $subscribers = [];
    for ($i = 0; $i < $count; $i++) {
      $subscriber = new SubscriberEntity();
      $subscriber->setEmail(sprintf('subscriber-%d@example.com', $i));
      $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
      $subscriber->setCreatedAt($createdAt);
      $this->entityManager->persist($subscriber);
      $subscribers[] = $subscriber;
    }
    $this->entityManager->flush();

    $ids = [];
    foreach ($subscribers as $subscriber) {
      $subscriber->setLastSubscribedAt($createdAt);
      $ids[] = (int)$subscriber->getId();
    }
    $this->entityManager->flush();

    return $ids;
  }

  private function createCompletedSendingTasksForSubscriber(SubscriberEntity $subscriber, int $numTasks = 1, int $processedDaysAgo = 0): void {
    for ($i = 0; $i < $numTasks; $i++) {
      [$task] = $this->createCompletedSendingTask($processedDaysAgo);
      $this->addSubscriberToTask($subscriber, $task);
    }
  }

  private function createCompletedSendingTask(int $processedDaysAgo = 0): array {
    $processedAt = (new Carbon())->subDays($processedDaysAgo)->addHours(2);
    $task = new ScheduledTaskEntity();
    $task->setType(SendingQueue::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $task->setCreatedAt($processedAt);
    $task->setProcessedAt($processedAt);
    $this->entityManager->persist($task);
    $this->entityManager->flush();
    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $queue->setNewsletter($this->newsletter);
    $this->entityManager->persist($queue);
    $this->entityManager->flush();
    return [$task, $queue];
  }

  private function addSubscriberToTask(
    SubscriberEntity $subscriber,
    ScheduledTaskEntity $task,
    int $daysAgo = 0
  ): ScheduledTaskSubscriberEntity {
    $createdAt = (new Carbon())->subDays($daysAgo);
    $taskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber);
    $taskSubscriber->setCreatedAt($createdAt);
    $this->entityManager->persist($taskSubscriber);
    $this->entityManager->flush();
    return $taskSubscriber;
  }
}
