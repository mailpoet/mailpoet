<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\Automations;

use Codeception\Stub\Expected;
use MailPoet\Automation\Engine\Control\AutomationRunCreationResult;
use MailPoet\Automation\Engine\Control\AutomationRunCreator;
use MailPoet\Automation\Engine\Control\StepSchedulingResult;
use MailPoet\Automation\Engine\Data\Automation as AutomationData;
use MailPoet\Automation\Engine\Data\AutomationRun as AutomationRunData;
use MailPoet\Automation\Engine\Data\Filter;
use MailPoet\Automation\Engine\Data\FilterGroup;
use MailPoet\Automation\Engine\Data\Filters;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\ManualStart\ManualStartService;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\Automations\ManualAutomationStartWorker;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Logging\LogRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;
use MailPoet\Test\DataFactories\AutomationRun;
use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;
use RuntimeException;

class ManualAutomationStartWorkerTest extends \MailPoetTest {
  /** @var ManualStartService */
  private $manualStartService;

  /** @var ManualAutomationStartWorker */
  private $worker;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var AutomationRunLogStorage */
  private $automationRunLogStorage;

  public function _before() {
    parent::_before();
    wp_set_current_user(1);
    $this->manualStartService = $this->diContainer->get(ManualStartService::class);
    $this->worker = $this->diContainer->get(ManualAutomationStartWorker::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $this->automationRunLogStorage = $this->diContainer->get(AutomationRunLogStorage::class);
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
  }

  public function testItCreatesRunsForQueuedSubscribersWithManualStartLogData(): void {
    $segment = (new Segment())->create();
    $segmentId = $this->getSegmentId($segment);
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->manualStartService->preview($automation->getId(), $segmentId, null);
    $start = $this->manualStartService->start($automation->getId(), $segmentId, null, $preview['preview_signature']);

    $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    $completed = $this->worker->processTaskStrategy($task, microtime(true));
    $this->assertTrue($completed);

    $this->scheduledTasksRepository->refresh($task);
    $this->assertSame(ScheduledTaskEntity::STATUS_COMPLETED, $task->getStatus());
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertSame(1, $workerCounts['processed_count']);
    $this->assertSame(1, $workerCounts['created_count']);
    $this->assertSame(0, $workerCounts['failed_count']);
    $this->assertTrue($workerCounts['completion_log_saved']);

    $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
    $this->assertCount(1, $runs);
    $run = $runs[0];
    $this->assertSame(SomeoneSubscribesTrigger::KEY, $run->getTriggerKey());
    $this->assertCount(1, $run->getSubjects(SubscriberSubject::KEY));
    $this->assertCount(1, $run->getSubjects(SegmentSubject::KEY));
    $this->assertSame(['subscriber_id' => $subscriber->getId()], $run->getSubjects(SubscriberSubject::KEY)[0]->getArgs());
    $this->assertSame(['segment_id' => $segmentId], $run->getSubjects(SegmentSubject::KEY)[0]->getArgs());

    $logs = $this->automationRunLogStorage->getLogsForAutomationRun($run->getId());
    $this->assertCount(1, $logs);
    $this->assertSame('complete', $logs[0]->getStatus());
    $this->assertSame(SomeoneSubscribesTrigger::KEY, $logs[0]->getStepKey());
    $this->assertTrue($logs[0]->getData()['manual_start']);
    $this->assertSame($task->getId(), $logs[0]->getData()['manual_start_task_id']);
    $this->assertSame($segmentId, $logs[0]->getData()['manual_start_segment_id']);
    $this->assertSame(1, $logs[0]->getData()['manual_start_requested_by']);

    $taskSubscriber = $task->getSubscribers()->first();
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $taskSubscriber);
    $this->assertSame(ScheduledTaskSubscriberEntity::STATUS_PROCESSED, $taskSubscriber->getProcessed());
    $this->assertSame(ScheduledTaskSubscriberEntity::FAIL_STATUS_OK, $taskSubscriber->getFailed());
  }

  public function testItCreatesRunsWithConcreteListSubjectWhenDynamicFilterIsUsed(): void {
    $segment = (new Segment())->create();
    $segmentId = $this->getSegmentId($segment);
    $filterSegment = (new DynamicSegment())->withEngagementScoreFilter(40, 'higherThan')->create();
    $filterSegmentId = $this->getSegmentId($filterSegment);
    $subscriber = (new Subscriber())->withSegments([$segment])->withEngagementScore(50)->create();
    (new Subscriber())->withSegments([$segment])->withEngagementScore(10)->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->manualStartService->preview($automation->getId(), $segmentId, $filterSegmentId);
    $this->assertSame(1, $preview['eligible_count']);
    $start = $this->manualStartService->start($automation->getId(), $segmentId, $filterSegmentId, $preview['preview_signature']);

    $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $completed = $this->worker->processTaskStrategy($task, microtime(true));
    $this->assertTrue($completed);

    $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
    $this->assertCount(1, $runs);
    $run = $runs[0];
    $this->assertSame(['subscriber_id' => $subscriber->getId()], $run->getSubjects(SubscriberSubject::KEY)[0]->getArgs());
    $this->assertSame(['segment_id' => $segmentId], $run->getSubjects(SegmentSubject::KEY)[0]->getArgs());

    $logs = $this->automationRunLogStorage->getLogsForAutomationRun($run->getId());
    $this->assertCount(1, $logs);
    $this->assertSame($filterSegmentId, $logs[0]->getData()['manual_start_filter_segment_id']);
  }

  public function testItSkipsSubscribersThatAlreadyEnteredBeforeWorkerRuns(): void {
    $segment = (new Segment())->create();
    $segmentId = $this->getSegmentId($segment);
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->manualStartService->preview($automation->getId(), $segmentId, null);
    $start = $this->manualStartService->start($automation->getId(), $segmentId, null, $preview['preview_signature']);
    (new AutomationRun())
      ->withAutomation($automation)
      ->withTriggerKey(SomeoneSubscribesTrigger::KEY)
      ->withSubject(new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriber->getId()]))
      ->withSubject(new Subject(SegmentSubject::KEY, ['segment_id' => $segmentId]))
      ->create();

    $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    $completed = $this->worker->processTaskStrategy($task, microtime(true));
    $this->assertTrue($completed);

    $this->scheduledTasksRepository->refresh($task);
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertSame(1, $workerCounts['processed_count']);
    $this->assertSame(0, $workerCounts['created_count']);
    $this->assertSame(1, $workerCounts['failed_count']);
    $this->assertSame(1, $workerCounts['skipped_by_reason']['already_entered']);

    $runs = $this->automationRunStorage->getAutomationRunsForAutomation($automation);
    $this->assertCount(1, $runs);
  }

  public function testItAllowsSubscribersWithFailedOrCancelledPriorRuns(): void {
    $segment = (new Segment())->create();
    $segmentId = $this->getSegmentId($segment);
    $failedSubscriber = (new Subscriber())->withSegments([$segment])->create();
    $cancelledSubscriber = (new Subscriber())->withSegments([$segment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    (new AutomationRun())
      ->withAutomation($automation)
      ->withTriggerKey(SomeoneSubscribesTrigger::KEY)
      ->withStatus(AutomationRunData::STATUS_FAILED)
      ->withSubject(new Subject(SubscriberSubject::KEY, ['subscriber_id' => $failedSubscriber->getId()]))
      ->withSubject(new Subject(SegmentSubject::KEY, ['segment_id' => $segmentId]))
      ->create();
    (new AutomationRun())
      ->withAutomation($automation)
      ->withTriggerKey(SomeoneSubscribesTrigger::KEY)
      ->withStatus(AutomationRunData::STATUS_CANCELLED)
      ->withSubject(new Subject(SubscriberSubject::KEY, ['subscriber_id' => $cancelledSubscriber->getId()]))
      ->withSubject(new Subject(SegmentSubject::KEY, ['segment_id' => $segmentId]))
      ->create();

    $preview = $this->manualStartService->preview($automation->getId(), $segmentId, null);
    $this->assertSame(2, $preview['eligible_count']);
    $this->assertSame(0, $preview['skipped_by_reason']['already_entered']);
    $start = $this->manualStartService->start($automation->getId(), $segmentId, null, $preview['preview_signature']);

    $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $completed = $this->worker->processTaskStrategy($task, microtime(true));
    $this->assertTrue($completed);

    $this->scheduledTasksRepository->refresh($task);
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertSame(2, $workerCounts['created_count']);
    $this->assertSame(0, $workerCounts['failed_count']);
    $this->assertCount(4, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
  }

  public function testItRevalidatesDeletedListBeforeCreatingRuns(): void {
    $segment = (new Segment())->create();
    $segmentId = $this->getSegmentId($segment);
    $subscriber = (new Subscriber())->withSegments([$segment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->manualStartService->preview($automation->getId(), $segmentId, null);
    $start = $this->manualStartService->start($automation->getId(), $segmentId, null, $preview['preview_signature']);
    $segment->setDeletedAt(new \DateTimeImmutable());
    $this->entityManager->flush();

    $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $completed = $this->worker->processTaskStrategy($task, microtime(true));

    $this->assertTrue($completed);
    $this->scheduledTasksRepository->refresh($task);
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertSame(1, $workerCounts['processed_count']);
    $this->assertSame(0, $workerCounts['created_count']);
    $this->assertSame(1, $workerCounts['failed_count']);
    $this->assertSame(1, $workerCounts['skipped_by_reason']['not_in_list']);
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));

    $taskSubscriber = $this->getTaskSubscriber((int)$task->getId(), (int)$subscriber->getId());
    $this->assertSame(ScheduledTaskSubscriberEntity::STATUS_PROCESSED, $taskSubscriber->getProcessed());
    $this->assertSame(ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED, $taskSubscriber->getFailed());
    $this->assertSame('skipped:not_in_list', $taskSubscriber->getError());
  }

  public function testItRevalidatesInvalidListTypeBeforeCreatingRuns(): void {
    $segment = (new Segment())->create();
    $segmentId = $this->getSegmentId($segment);
    (new Subscriber())->withSegments([$segment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->manualStartService->preview($automation->getId(), $segmentId, null);
    $start = $this->manualStartService->start($automation->getId(), $segmentId, null, $preview['preview_signature']);
    $segment->setType(SegmentEntity::TYPE_DYNAMIC);
    $this->entityManager->flush();

    $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $completed = $this->worker->processTaskStrategy($task, microtime(true));

    $this->assertTrue($completed);
    $this->scheduledTasksRepository->refresh($task);
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertSame(1, $workerCounts['processed_count']);
    $this->assertSame(0, $workerCounts['created_count']);
    $this->assertSame(1, $workerCounts['failed_count']);
    $this->assertSame(1, $workerCounts['skipped_by_reason']['not_in_list']);
    $this->assertCount(0, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
  }

  public function testItPersistsProgressBeforeExecutionLimitCanInterruptNextSubscriber(): void {
    $segment = (new Segment())->create();
    $segmentId = $this->getSegmentId($segment);
    $firstSubscriber = (new Subscriber())->withSegments([$segment])->create();
    $secondSubscriber = (new Subscriber())->withSegments([$segment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->manualStartService->preview($automation->getId(), $segmentId, null);
    $start = $this->manualStartService->start($automation->getId(), $segmentId, null, $preview['preview_signature']);
    $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    $calls = 0;
    $originalCronHelper = $this->replaceWorkerProperty(
      $this->worker,
      SimpleWorker::class,
      'cronHelper',
      $this->make(CronHelper::class, [
        'enforceExecutionLimit' => Expected::exactly(2, function ($_timer = null) use (&$calls): void {
          $calls++;
          if ($calls === 2) {
            throw new RuntimeException('time limit');
          }
        }),
      ])
    );

    try {
      $this->worker->processTaskStrategy($task, microtime(true));
      $this->fail('Expected execution limit interruption.');
    } catch (RuntimeException $exception) {
      $this->assertSame('time limit', $exception->getMessage());
    } finally {
      $this->replaceWorkerProperty($this->worker, SimpleWorker::class, 'cronHelper', $originalCronHelper);
    }

    $this->scheduledTasksRepository->refresh($task);
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertSame(1, $workerCounts['processed_count']);
    $this->assertSame(1, $workerCounts['created_count']);
    $this->assertSame(0, $workerCounts['failed_count']);
    $this->assertSame(ScheduledTaskSubscriberEntity::STATUS_PROCESSED, $this->getTaskSubscriber((int)$task->getId(), (int)$firstSubscriber->getId())->getProcessed());
    $this->assertSame(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED, $this->getTaskSubscriber((int)$task->getId(), (int)$secondSubscriber->getId())->getProcessed());

    $completed = $this->worker->processTaskStrategy($task, microtime(true));
    $this->assertTrue($completed);
    $this->scheduledTasksRepository->refresh($task);
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertSame(2, $workerCounts['processed_count']);
    $this->assertSame(2, $workerCounts['created_count']);
    $this->assertSame(0, $workerCounts['failed_count']);
    $this->assertCount(2, $this->automationRunStorage->getAutomationRunsForAutomation($automation));
  }

  public function testCompletionLogSavedFlagIsNotSetWhenSavingLogFails(): void {
    $segment = (new Segment())->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();
    $task = $this->createManualStartTask($automation, $segment);

    $originalLogRepository = $this->replaceWorkerProperty(
      $this->worker,
      ManualAutomationStartWorker::class,
      'logRepository',
      $this->make(LogRepository::class, [
        'saveLog' => Expected::once(function (): void {
          throw new RuntimeException('log failed');
        }),
      ])
    );

    try {
      $this->worker->processTaskStrategy($task, microtime(true));
      $this->fail('Expected completion log failure.');
    } catch (RuntimeException $exception) {
      $this->assertSame('log failed', $exception->getMessage());
    } finally {
      $this->replaceWorkerProperty($this->worker, ManualAutomationStartWorker::class, 'logRepository', $originalLogRepository);
    }

    $this->scheduledTasksRepository->refresh($task);
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertFalse($workerCounts['completion_log_saved']);

    $completed = $this->worker->processTaskStrategy($task, microtime(true));
    $this->assertTrue($completed);
    $this->scheduledTasksRepository->refresh($task);
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertTrue($workerCounts['completion_log_saved']);
  }

  public function testItSkipsWhenAutomationIsInactiveBeforeWorkerRuns(): void {
    $segment = (new Segment())->create();
    $segmentId = $this->getSegmentId($segment);
    (new Subscriber())->withSegments([$segment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->manualStartService->preview($automation->getId(), $segmentId, null);
    $start = $this->manualStartService->start($automation->getId(), $segmentId, null, $preview['preview_signature']);
    $automation->setStatus(AutomationData::STATUS_DRAFT);
    $this->diContainer->get(AutomationStorage::class)->updateAutomation($automation);

    $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $completed = $this->worker->processTaskStrategy($task, microtime(true));

    $this->assertTrue($completed);
    $this->scheduledTasksRepository->refresh($task);
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertSame(1, $workerCounts['processed_count']);
    $this->assertSame(0, $workerCounts['created_count']);
    $this->assertSame(1, $workerCounts['failed_count']);
    $this->assertSame(1, $workerCounts['skipped_by_reason']['automation_inactive']);
  }

  public function testItSkipsSubscriberChangesBeforeWorkerRuns(): void {
    $segment = (new Segment())->create();
    $segmentId = $this->getSegmentId($segment);
    $deletedSubscriber = (new Subscriber())->withSegments([$segment])->create();
    $unsubscribedSubscriber = (new Subscriber())->withSegments([$segment])->create();
    $removedFromListSubscriber = (new Subscriber())->withSegments([$segment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->manualStartService->preview($automation->getId(), $segmentId, null);
    $start = $this->manualStartService->start($automation->getId(), $segmentId, null, $preview['preview_signature']);

    $deletedSubscriber->setDeletedAt(new \DateTimeImmutable());
    $unsubscribedSubscriber->setStatus(\MailPoet\Entities\SubscriberEntity::STATUS_UNSUBSCRIBED);
    foreach ($removedFromListSubscriber->getSubscriberSegments() as $subscriberSegment) {
      $subscriberSegment->setStatus(\MailPoet\Entities\SubscriberEntity::STATUS_UNSUBSCRIBED);
    }
    $this->entityManager->flush();

    $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $completed = $this->worker->processTaskStrategy($task, microtime(true));

    $this->assertTrue($completed);
    $this->scheduledTasksRepository->refresh($task);
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertSame(3, $workerCounts['processed_count']);
    $this->assertSame(0, $workerCounts['created_count']);
    $this->assertSame(3, $workerCounts['failed_count']);
    $this->assertSame(1, $workerCounts['skipped_by_reason']['deleted']);
    $this->assertSame(1, $workerCounts['skipped_by_reason']['unsubscribed']);
    $this->assertSame(1, $workerCounts['skipped_by_reason']['not_in_list']);
  }

  public function testItSkipsTriggerFilterMismatchBeforeCreatingRun(): void {
    $segment = (new Segment())->create();
    $segmentId = $this->getSegmentId($segment);
    (new Subscriber())->withSegments([$segment])->create();
    $unknownSegmentId = $segmentId + 100000;
    $filters = new Filters('and', [
      new FilterGroup('g1', 'and', [
        new Filter('f1', 'enum_array', 'mailpoet:subscriber:segments', 'matches-any-of', ['value' => [$unknownSegmentId]]),
      ]),
    ]);
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withStep(new Step('trigger', Step::TYPE_TRIGGER, SomeoneSubscribesTrigger::KEY, [], [], $filters))
      ->create();

    $preview = $this->manualStartService->preview($automation->getId(), $segmentId, null);
    $start = $this->manualStartService->start($automation->getId(), $segmentId, null, $preview['preview_signature']);
    $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $completed = $this->worker->processTaskStrategy($task, microtime(true));

    $this->assertTrue($completed);
    $this->scheduledTasksRepository->refresh($task);
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertSame(1, $workerCounts['processed_count']);
    $this->assertSame(0, $workerCounts['created_count']);
    $this->assertSame(1, $workerCounts['failed_count']);
    $this->assertSame(1, $workerCounts['skipped_by_reason']['trigger_filter_mismatch']);
  }

  public function testItSkipsRunCreateHookRejection(): void {
    $segment = (new Segment())->create();
    $segmentId = $this->getSegmentId($segment);
    (new Subscriber())->withSegments([$segment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->manualStartService->preview($automation->getId(), $segmentId, null);
    $start = $this->manualStartService->start($automation->getId(), $segmentId, null, $preview['preview_signature']);
    $rejectRunCreation = function ($_createRun): bool {
      return false;
    };
    add_filter(Hooks::AUTOMATION_RUN_CREATE, $rejectRunCreation);

    $completed = false;
    $task = null;
    try {
      $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      $completed = $this->worker->processTaskStrategy($task, microtime(true));
    } finally {
      remove_filter(Hooks::AUTOMATION_RUN_CREATE, $rejectRunCreation);
    }

    $this->assertTrue($completed);
    $this->scheduledTasksRepository->refresh($task);
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertSame(1, $workerCounts['processed_count']);
    $this->assertSame(0, $workerCounts['created_count']);
    $this->assertSame(1, $workerCounts['failed_count']);
    $this->assertSame(1, $workerCounts['skipped_by_reason']['run_create_hook_rejected']);
  }

  public function testItSkipsStepSchedulingFailure(): void {
    $segment = (new Segment())->create();
    $segmentId = $this->getSegmentId($segment);
    (new Subscriber())->withSegments([$segment])->create();
    $automation = (new AutomationFactory())
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();

    $preview = $this->manualStartService->preview($automation->getId(), $segmentId, null);
    $start = $this->manualStartService->start($automation->getId(), $segmentId, null, $preview['preview_signature']);
    $originalRunCreator = $this->replaceWorkerProperty(
      $this->worker,
      ManualAutomationStartWorker::class,
      'automationRunCreator',
      $this->make(AutomationRunCreator::class, [
        'createForAutomation' => Expected::once(
          AutomationRunCreationResult::schedulingFailed(
            123,
            StepSchedulingResult::enqueueFailed('next-step')
          )
        ),
      ])
    );

    $completed = false;
    $task = null;
    try {
      $task = $this->scheduledTasksRepository->findOneById($start['task_id']);
      $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
      $completed = $this->worker->processTaskStrategy($task, microtime(true));
    } finally {
      $this->replaceWorkerProperty($this->worker, ManualAutomationStartWorker::class, 'automationRunCreator', $originalRunCreator);
    }

    $this->assertTrue($completed);
    $this->scheduledTasksRepository->refresh($task);
    $workerCounts = $this->getWorkerCounts($task);
    $this->assertSame(1, $workerCounts['processed_count']);
    $this->assertSame(0, $workerCounts['created_count']);
    $this->assertSame(1, $workerCounts['failed_count']);
    $this->assertSame(1, $workerCounts['skipped_by_reason']['step_scheduling_failed']);
  }

  private function getSegmentId(SegmentEntity $segment): int {
    $id = $segment->getId();
    if ($id === null) {
      $this->fail('Segment ID is required for manual start tests.');
    }
    return $id;
  }

  private function createManualStartTask(AutomationData $automation, SegmentEntity $segment): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(ManualAutomationStartWorker::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setMeta([
      'automation_id' => $automation->getId(),
      'automation_version_id' => $automation->getVersionId(),
      'segment_id' => $segment->getId(),
      'filter_segment_id' => null,
      'requested_by' => 1,
      'worker_counts' => [
        'processed_count' => 0,
        'created_count' => 0,
        'failed_count' => 0,
        'skipped_by_reason' => [],
        'completion_log_saved' => false,
      ],
    ]);
    $this->entityManager->persist($task);
    $this->entityManager->flush();
    return $task;
  }

  private function getTaskSubscriber(int $taskId, int $subscriberId): ScheduledTaskSubscriberEntity {
    $taskSubscriber = $this->entityManager->getRepository(ScheduledTaskSubscriberEntity::class)->findOneBy([
      'task' => $taskId,
      'subscriber' => $subscriberId,
    ]);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $taskSubscriber);
    return $taskSubscriber;
  }

  /**
   * @param class-string $className
   * @param object $value
   * @return object
   */
  private function replaceWorkerProperty(object $worker, string $className, string $propertyName, object $value): object {
    $property = new \ReflectionProperty($className, $propertyName);
    $property->setAccessible(true);
    $previousValue = $property->getValue($worker);
    if (!is_object($previousValue)) {
      $this->fail("Expected $propertyName to contain an object.");
    }
    $property->setValue($worker, $value);
    return $previousValue;
  }

  /**
   * @return array{processed_count: int, created_count: int, failed_count: int, skipped_by_reason: array<string, int>, completion_log_saved: bool}
   */
  private function getWorkerCounts(ScheduledTaskEntity $task): array {
    $meta = $task->getMeta() ?? [];
    $counts = isset($meta['worker_counts']) && is_array($meta['worker_counts']) ? $meta['worker_counts'] : [];
    $skippedByReason = isset($counts['skipped_by_reason']) && is_array($counts['skipped_by_reason']) ? $counts['skipped_by_reason'] : [];
    $skippedByReasonCounts = [];
    foreach ($skippedByReason as $reason => $count) {
      if (is_string($reason)) {
        $skippedByReasonCounts[$reason] = $this->getInt($count);
      }
    }
    return [
      'processed_count' => $this->getInt($counts['processed_count'] ?? null),
      'created_count' => $this->getInt($counts['created_count'] ?? null),
      'failed_count' => $this->getInt($counts['failed_count'] ?? null),
      'skipped_by_reason' => $skippedByReasonCounts,
      'completion_log_saved' => (bool)($counts['completion_log_saved'] ?? false),
    ];
  }

  /** @param mixed $value */
  private function getInt($value): int {
    if (is_numeric($value)) {
      return (int)$value;
    }
    return 0;
  }
}
