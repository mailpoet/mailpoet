<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers\Automations;

use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\ManualStart\ManualStartService;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Cron\Workers\Automations\ManualAutomationStartWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;
use MailPoet\Test\DataFactories\AutomationRun;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

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

  private function getSegmentId(SegmentEntity $segment): int {
    $id = $segment->getId();
    if ($id === null) {
      $this->fail('Segment ID is required for manual start tests.');
    }
    return $id;
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
