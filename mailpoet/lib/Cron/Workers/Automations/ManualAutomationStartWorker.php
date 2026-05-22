<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers\Automations;

use MailPoet\Automation\Engine\Control\AutomationRunCreationResult;
use MailPoet\Automation\Engine\Control\AutomationRunCreator;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\ManualStart\ManualStartAudienceRepository;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Entities\LogEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Logging\LogRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use Throwable;

class ManualAutomationStartWorker extends SimpleWorker {
  const TASK_TYPE = 'automation_manual_start';
  const AUTOMATIC_SCHEDULING = false;
  const SUPPORT_MULTIPLE_INSTANCES = false;
  const BATCH_SIZE = 100;

  private const AUDIT_LOG_MESSAGE_COMPLETED = 'Manual automation start completed.';
  private const LOG_LEVEL_INFO = 200;

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunCreator */
  private $automationRunCreator;

  /** @var SomeoneSubscribesTrigger */
  private $trigger;

  /** @var ManualStartAudienceRepository */
  private $audienceRepository;

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  /** @var LogRepository */
  private $logRepository;

  public function __construct(
    AutomationStorage $automationStorage,
    AutomationRunCreator $automationRunCreator,
    SomeoneSubscribesTrigger $trigger,
    ManualStartAudienceRepository $audienceRepository,
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository,
    LogRepository $logRepository
  ) {
    parent::__construct();
    $this->automationStorage = $automationStorage;
    $this->automationRunCreator = $automationRunCreator;
    $this->trigger = $trigger;
    $this->audienceRepository = $audienceRepository;
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
    $this->logRepository = $logRepository;
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $meta = $task->getMeta() ?? [];
    $automationId = $this->getRequiredInt($meta['automation_id'] ?? null);
    $automationVersionId = $this->getRequiredInt($meta['automation_version_id'] ?? null);
    $segmentId = $this->getRequiredInt($meta['segment_id'] ?? null);
    $filterSegmentId = $this->getOptionalInt($meta['filter_segment_id'] ?? null);

    if ($automationId <= 0 || $automationVersionId <= 0 || $segmentId <= 0) {
      $task->setStatus(ScheduledTaskEntity::STATUS_INVALID);
      $this->scheduledTasksRepository->persist($task);
      $this->scheduledTasksRepository->flush();
      return false;
    }

    $subscriberIds = $this->scheduledTaskSubscribersRepository->getSubscriberIdsBatchForTask((int)$task->getId(), 0, self::BATCH_SIZE);
    if (!$subscriberIds) {
      $this->saveCompletionIfNeeded($task);
      return true;
    }

    $automation = $this->automationStorage->getAutomation($automationId, $automationVersionId);
    $counts = $this->getWorkerCounts($task);
    $segmentIneligibleReason = $this->audienceRepository->getSegmentIneligibleReason($segmentId, $filterSegmentId);

    try {
      foreach ($subscriberIds as $subscriberId) {
        $this->cronHelper->enforceExecutionLimit($timer);
        $subscriberId = (int)$subscriberId;
        $counts['processed_count']++;

        if (!$automation instanceof Automation || $automation->getStatus() !== Automation::STATUS_ACTIVE) {
          $this->saveSkipped($task, $subscriberId, ManualStartAudienceRepository::REASON_AUTOMATION_INACTIVE, $counts);
          continue;
        }

        if ($segmentIneligibleReason !== null) {
          $this->saveSkipped($task, $subscriberId, $segmentIneligibleReason, $counts);
          continue;
        }

        $reason = $this->audienceRepository->getSubscriberIneligibleReason($subscriberId, $segmentId, $filterSegmentId);
        if ($reason !== null) {
          $this->saveSkipped($task, $subscriberId, $reason, $counts);
          continue;
        }
        if ($this->audienceRepository->hasSubscriberEnteredAutomation($automationId, $subscriberId)) {
          $this->saveSkipped($task, $subscriberId, ManualStartAudienceRepository::REASON_ALREADY_ENTERED, $counts);
          continue;
        }

        $subjects = [
          new Subject(SegmentSubject::KEY, ['segment_id' => $segmentId]),
          new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriberId]),
        ];

        try {
          $result = $this->automationRunCreator->createForAutomation($automation, $this->trigger, $subjects, $this->getTriggerLogData($task));
        } catch (Throwable $throwable) {
          $this->saveSkipped($task, $subscriberId, ManualStartAudienceRepository::REASON_STEP_SCHEDULING_FAILED, $counts);
          continue;
        }

        if ($result->isCreated()) {
          $counts['created_count']++;
          $this->scheduledTaskSubscribersRepository->updateProcessedSubscribers($task, [$subscriberId]);
          continue;
        }

        $this->saveSkipped($task, $subscriberId, $this->mapCreationResultToReason($result), $counts);
      }
    } catch (Throwable $throwable) {
      $this->saveWorkerCounts($task, $counts);
      throw $throwable;
    }

    $this->saveWorkerCounts($task, $counts);

    if ($this->scheduledTaskSubscribersRepository->countUnprocessed($task) > 0) {
      return false;
    }

    $this->saveCompletionIfNeeded($task);
    return true;
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
        $skippedByReasonCounts[$reason] = $this->getRequiredInt($count);
      }
    }
    return [
      'processed_count' => $this->getRequiredInt($counts['processed_count'] ?? null),
      'created_count' => $this->getRequiredInt($counts['created_count'] ?? null),
      'failed_count' => $this->getRequiredInt($counts['failed_count'] ?? null),
      'skipped_by_reason' => $skippedByReasonCounts,
      'completion_log_saved' => (bool)($counts['completion_log_saved'] ?? false),
    ];
  }

  /**
   * @param array{processed_count: int, created_count: int, failed_count: int, skipped_by_reason: array<string, int>, completion_log_saved: bool} $counts
   */
  private function saveWorkerCounts(ScheduledTaskEntity $task, array $counts): void {
    $meta = $task->getMeta() ?? [];
    $meta['worker_counts'] = $counts;
    $task->setMeta($meta);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }

  /**
   * @param array{processed_count: int, created_count: int, failed_count: int, skipped_by_reason: array<string, int>, completion_log_saved: bool} $counts
   */
  private function saveSkipped(ScheduledTaskEntity $task, int $subscriberId, string $reason, array &$counts): void {
    $counts['failed_count']++;
    $counts['skipped_by_reason'][$reason] = ($counts['skipped_by_reason'][$reason] ?? 0) + 1;
    $this->scheduledTaskSubscribersRepository->saveError($task, $subscriberId, 'skipped:' . $reason);
  }

  private function mapCreationResultToReason(AutomationRunCreationResult $result): string {
    if ($result->getStatus() === AutomationRunCreationResult::STATUS_RUN_CREATE_HOOK_REJECTED) {
      return ManualStartAudienceRepository::REASON_RUN_CREATE_HOOK_REJECTED;
    }
    if ($result->getStatus() === AutomationRunCreationResult::STATUS_STEP_SCHEDULING_FAILED) {
      return ManualStartAudienceRepository::REASON_STEP_SCHEDULING_FAILED;
    }
    return ManualStartAudienceRepository::REASON_TRIGGER_FILTER_MISMATCH;
  }

  /**
   * @return array<string, scalar>
   */
  private function getTriggerLogData(ScheduledTaskEntity $task): array {
    $meta = $task->getMeta() ?? [];
    $data = [
      'manual_start' => true,
      'manual_start_task_id' => $this->getRequiredInt($task->getId()),
      'manual_start_segment_id' => $this->getRequiredInt($meta['segment_id'] ?? null),
      'manual_start_requested_by' => $this->getRequiredInt($meta['requested_by'] ?? null),
    ];
    $filterSegmentId = $this->getOptionalInt($meta['filter_segment_id'] ?? null);
    if ($filterSegmentId !== null) {
      $data['manual_start_filter_segment_id'] = $filterSegmentId;
    }
    return $data;
  }

  private function saveCompletionIfNeeded(ScheduledTaskEntity $task): void {
    $counts = $this->getWorkerCounts($task);
    if ($counts['completion_log_saved']) {
      return;
    }

    $meta = $task->getMeta() ?? [];
    $log = new LogEntity();
    $log->setName(LoggerFactory::TOPIC_CRON);
    $log->setLevel(self::LOG_LEVEL_INFO);
    $log->setMessage(self::AUDIT_LOG_MESSAGE_COMPLETED);
    $log->setRawMessage(self::AUDIT_LOG_MESSAGE_COMPLETED);
    $log->setContext([
      'action' => 'manual_automation_start_completed',
      'task_id' => $task->getId(),
      'automation_id' => $meta['automation_id'] ?? null,
      'segment_id' => $meta['segment_id'] ?? null,
      'filter_segment_id' => $meta['filter_segment_id'] ?? null,
      'processed_count' => $counts['processed_count'],
      'created_count' => $counts['created_count'],
      'failed_count' => $counts['failed_count'],
      'skipped_by_reason' => $counts['skipped_by_reason'],
      'final_status' => ScheduledTaskEntity::STATUS_COMPLETED,
    ]);
    $this->logRepository->saveLog($log);
    $counts['completion_log_saved'] = true;
    $this->saveWorkerCounts($task, $counts);
  }

  /** @param mixed $value */
  private function getRequiredInt($value): int {
    if (is_numeric($value)) {
      return (int)$value;
    }
    return 0;
  }

  /** @param mixed $value */
  private function getOptionalInt($value): ?int {
    if (is_numeric($value)) {
      return (int)$value;
    }
    return null;
  }
}
