<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\ManualStart;

use MailPoet\API\REST\ApiException;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Cron\Workers\Automations\ManualAutomationStartWorker;
use MailPoet\Entities\LogEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Logging\LogRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use Throwable;

class ManualStartService {
  private const LOG_LEVEL_INFO = 200;
  private const AUDIT_LOG_MESSAGE_QUEUED = 'Manual automation start queued.';

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var ManualStartAudienceRepository */
  private $audienceRepository;

  /** @var EntityManager */
  private $entityManager;

  /** @var LogRepository */
  private $logRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    AutomationStorage $automationStorage,
    SegmentsRepository $segmentsRepository,
    ManualStartAudienceRepository $audienceRepository,
    EntityManager $entityManager,
    LogRepository $logRepository,
    WPFunctions $wp
  ) {
    $this->automationStorage = $automationStorage;
    $this->segmentsRepository = $segmentsRepository;
    $this->audienceRepository = $audienceRepository;
    $this->entityManager = $entityManager;
    $this->logRepository = $logRepository;
    $this->wp = $wp;
  }

  /**
   * @return array{preview_signature: string, automation_id: int, segment_id: int, filter_segment_id: int|null, selected_count: int, eligible_count: int, skipped_by_reason: array<string, int>, deferred_reason_keys: string[], duplicate_in_progress: bool}
   */
  public function preview(int $automationId, int $segmentId, ?int $filterSegmentId): array {
    $context = $this->validateContext($automationId, $segmentId, $filterSegmentId);
    $this->assertNoActiveTask($automationId);
    return $this->buildPreview($context);
  }

  /**
   * @return array{task_id: int, automation_id: int, segment_id: int, filter_segment_id: int|null, selected_count: int, eligible_count: int, queued_count: int, skipped_by_reason: array<string, int>}
   */
  public function start(int $automationId, int $segmentId, ?int $filterSegmentId, string $previewSignature): array {
    $context = $this->validateContext($automationId, $segmentId, $filterSegmentId);
    $this->assertNoActiveTask($automationId);

    $lockName = $this->getLockName($automationId);
    $this->acquireLock($lockName);
    try {
      $context = $this->validateContext($automationId, $segmentId, $filterSegmentId);
      $this->assertNoActiveTask($automationId);
      $preview = $this->buildPreview($context);

      if (!hash_equals($preview['preview_signature'], $previewSignature)) {
        throw new ApiException(
          __('The audience changed since the last preview. Refresh the preview before queueing subscribers.', 'mailpoet'),
          409,
          'manual_start_stale_preview',
          [],
          null,
          ['preview' => $preview]
        );
      }

      if ($preview['eligible_count'] <= 0) {
        throw new ApiException(
          __('No subscribers are eligible to start this automation.', 'mailpoet'),
          422,
          'manual_start_zero_eligible'
        );
      }

      return $this->createTask($context, $preview, $previewSignature);
    } finally {
      $this->releaseLock($lockName);
    }
  }

  /**
   * @return array{supported: bool, trigger_key: string|null, segment_ids: int[]|null, disabled_reason?: string}
   */
  public function getMetadata(Automation $automation): array {
    $triggerStep = $automation->getTrigger(SomeoneSubscribesTrigger::KEY);
    if ($automation->getStatus() !== Automation::STATUS_ACTIVE) {
      return [
        'supported' => false,
        'trigger_key' => $triggerStep ? SomeoneSubscribesTrigger::KEY : null,
        'segment_ids' => null,
        'disabled_reason' => 'automation_not_active',
      ];
    }

    if (!$triggerStep) {
      return [
        'supported' => false,
        'trigger_key' => null,
        'segment_ids' => null,
        'disabled_reason' => 'unsupported_trigger',
      ];
    }

    return [
      'supported' => true,
      'trigger_key' => SomeoneSubscribesTrigger::KEY,
      'segment_ids' => $this->getAllowedSegmentIds($triggerStep),
    ];
  }

  private function validateContext(int $automationId, int $segmentId, ?int $filterSegmentId): ManualStartContext {
    if ($automationId <= 0) {
      throw new ApiException(__('Automation id is required.', 'mailpoet'), 400, 'manual_start_invalid_automation_id');
    }

    $automation = $this->automationStorage->getAutomation($automationId);
    if (!$automation) {
      throw new ApiException(__('Automation not found.', 'mailpoet'), 404, 'manual_start_automation_not_found');
    }
    if ($automation->getStatus() !== Automation::STATUS_ACTIVE) {
      throw new ApiException(__('Automation must be active before it can be started manually.', 'mailpoet'), 400, 'manual_start_automation_not_active');
    }

    $triggerStep = $automation->getTrigger(SomeoneSubscribesTrigger::KEY);
    if (!$triggerStep) {
      throw new ApiException(__('This automation trigger does not support manual starts.', 'mailpoet'), 400, 'manual_start_unsupported_trigger');
    }

    $segment = $this->validateSegment($segmentId, SegmentEntity::TYPE_DEFAULT, 'manual_start_invalid_segment');
    $allowedSegmentIds = $this->getAllowedSegmentIds($triggerStep);
    if ($allowedSegmentIds !== null && !in_array($segmentId, $allowedSegmentIds, true)) {
      throw new ApiException(__('The selected list is not allowed by this automation trigger.', 'mailpoet'), 400, 'manual_start_segment_not_allowed');
    }

    $filterSegment = null;
    if ($filterSegmentId !== null) {
      $filterSegment = $this->validateSegment($filterSegmentId, SegmentEntity::TYPE_DYNAMIC, 'manual_start_invalid_filter_segment');
    }

    return new ManualStartContext($automation, $triggerStep, $segment, $filterSegment);
  }

  private function validateSegment(int $segmentId, string $expectedType, string $errorCode): SegmentEntity {
    if ($segmentId <= 0) {
      throw new ApiException(__('Segment id must be a positive integer.', 'mailpoet'), 400, $errorCode);
    }

    $segment = $this->segmentsRepository->findOneById($segmentId);
    if (!$segment instanceof SegmentEntity) {
      throw new ApiException(__('Segment not found.', 'mailpoet'), 400, $errorCode);
    }
    if ($segment->getDeletedAt() !== null) {
      throw new ApiException(__('Deleted segments cannot be used to start an automation.', 'mailpoet'), 400, $errorCode);
    }
    if ($segment->getType() !== $expectedType) {
      throw new ApiException(__('The selected segment type is not supported for manual starts.', 'mailpoet'), 400, $errorCode);
    }
    return $segment;
  }

  /**
   * @return int[]|null
   */
  private function getAllowedSegmentIds(Step $triggerStep): ?array {
    $args = $triggerStep->getArgs();
    $segmentIds = $args['segment_ids'] ?? null;
    if (!is_array($segmentIds) || $segmentIds === []) {
      return null;
    }

    $ids = [];
    foreach ($segmentIds as $segmentId) {
      if (is_numeric($segmentId) && (int)$segmentId > 0) {
        $ids[] = (int)$segmentId;
      }
    }
    $ids = array_values(array_unique($ids));
    return $ids ?: null;
  }

  /**
   * @return array{preview_signature: string, automation_id: int, segment_id: int, filter_segment_id: int|null, selected_count: int, eligible_count: int, skipped_by_reason: array<string, int>, deferred_reason_keys: string[], duplicate_in_progress: bool}
   */
  private function buildPreview(ManualStartContext $context): array {
    $automation = $context->getAutomation();
    $counts = $this->audienceRepository->getPreviewCounts($automation->getId(), $context->getSegment(), $context->getFilterSegment());
    $preview = [
      'preview_signature' => '',
      'automation_id' => $automation->getId(),
      'segment_id' => $context->getSegmentId(),
      'filter_segment_id' => $context->getFilterSegmentId(),
      'selected_count' => $counts['selected_count'],
      'eligible_count' => $counts['eligible_count'],
      'skipped_by_reason' => $counts['skipped_by_reason'],
      'deferred_reason_keys' => [
        ManualStartAudienceRepository::REASON_TRIGGER_FILTER_MISMATCH,
        ManualStartAudienceRepository::REASON_RUN_CREATE_HOOK_REJECTED,
        ManualStartAudienceRepository::REASON_STEP_SCHEDULING_FAILED,
      ],
      'duplicate_in_progress' => false,
    ];
    $preview['preview_signature'] = $this->createPreviewSignature($automation, $preview);
    return $preview;
  }

  /**
   * @param array{preview_signature: string, automation_id: int, segment_id: int, filter_segment_id: int|null, selected_count: int, eligible_count: int, skipped_by_reason: array<string, int>, deferred_reason_keys: string[], duplicate_in_progress: bool} $preview
   * @return array{task_id: int, automation_id: int, segment_id: int, filter_segment_id: int|null, selected_count: int, eligible_count: int, queued_count: int, skipped_by_reason: array<string, int>}
   */
  private function createTask(ManualStartContext $context, array $preview, string $previewSignature): array {
    $automation = $context->getAutomation();
    $now = Carbon::now()->millisecond(0);
    $task = new ScheduledTaskEntity();
    $queuedCount = 0;

    try {
      $this->entityManager->wrapInTransaction(function () use ($context, $automation, $preview, $previewSignature, $now, $task, &$queuedCount): void {
        $task->setType(ManualAutomationStartWorker::TASK_TYPE);
        $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
        $task->setScheduledAt($now);
        $task->setPriority(ScheduledTaskEntity::PRIORITY_HIGH);
        $task->setMeta([
          'automation_id' => $automation->getId(),
          'automation_version_id' => $automation->getVersionId(),
          'trigger_key' => SomeoneSubscribesTrigger::KEY,
          'trigger_step_id' => $context->getTriggerStep()->getId(),
          'segment_id' => $context->getSegmentId(),
          'filter_segment_id' => $context->getFilterSegmentId(),
          'requested_by' => (int)$this->wp->getCurrentUserId(),
          'preview_signature' => $previewSignature,
          'selected_count' => $preview['selected_count'],
          'eligible_count' => $preview['eligible_count'],
          'queued_count' => 0,
          'skipped_by_reason' => $preview['skipped_by_reason'],
          'worker_counts' => $this->getEmptyWorkerCounts(),
          'queued_at' => $now->format('Y-m-d H:i:s'),
        ]);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $queuedCount = $this->audienceRepository->queueEligibleSubscribers(
          $task,
          $automation->getId(),
          $context->getSegment(),
          $context->getFilterSegment()
        );

        if ($queuedCount <= 0) {
          $this->entityManager->remove($task);
          $this->entityManager->flush();
          throw new ApiException(
            __('No subscribers are eligible to start this automation.', 'mailpoet'),
            422,
            'manual_start_zero_eligible'
          );
        }

        $meta = $task->getMeta() ?? [];
        $meta['queued_count'] = $queuedCount;
        $task->setMeta($meta);
        $this->entityManager->flush();
        $this->saveQueueAuditLog($task, $preview, $queuedCount);
      });
    } catch (Throwable $throwable) {
      if ($throwable instanceof ApiException) {
        throw $throwable;
      }
      throw new ApiException(
        __('Could not queue subscribers for manual automation start.', 'mailpoet'),
        500,
        'manual_start_queue_failed',
        [],
        $throwable
      );
    }

    return [
      'task_id' => (int)$task->getId(),
      'automation_id' => $automation->getId(),
      'segment_id' => $context->getSegmentId(),
      'filter_segment_id' => $context->getFilterSegmentId(),
      'selected_count' => $preview['selected_count'],
      'eligible_count' => $preview['eligible_count'],
      'queued_count' => $queuedCount,
      'skipped_by_reason' => $preview['skipped_by_reason'],
    ];
  }

  private function assertNoActiveTask(int $automationId): void {
    if ($this->findActiveTaskId($automationId) !== null) {
      throw new ApiException(
        __('Subscribers are already queued for this automation. Wait for the current manual start to finish before starting another one.', 'mailpoet'),
        409,
        'manual_start_in_progress'
      );
    }
  }

  private function findActiveTaskId(int $automationId): ?int {
    $tasksTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $taskId = $this->entityManager->getConnection()->executeQuery(
      "SELECT id
       FROM $tasksTable
       WHERE type = :type
         AND deleted_at IS NULL
         AND (status = :scheduled OR status IS NULL)
         AND CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.automation_id')) AS UNSIGNED) = :automationId
       ORDER BY id DESC
       LIMIT 1",
      [
        'type' => ManualAutomationStartWorker::TASK_TYPE,
        'scheduled' => ScheduledTaskEntity::STATUS_SCHEDULED,
        'automationId' => $automationId,
      ],
      [
        'type' => ParameterType::STRING,
        'scheduled' => ParameterType::STRING,
        'automationId' => ParameterType::INTEGER,
      ]
    )->fetchOne();

    return is_numeric($taskId) ? (int)$taskId : null;
  }

  /**
   * @param array{preview_signature: string, automation_id: int, segment_id: int, filter_segment_id: int|null, selected_count: int, eligible_count: int, skipped_by_reason: array<string, int>, deferred_reason_keys: string[], duplicate_in_progress: bool} $preview
   */
  private function createPreviewSignature(Automation $automation, array $preview): string {
    $payload = [
      'automation_id' => $automation->getId(),
      'automation_version_id' => $automation->getVersionId(),
      'segment_id' => $preview['segment_id'],
      'filter_segment_id' => $preview['filter_segment_id'],
      'selected_count' => $preview['selected_count'],
      'eligible_count' => $preview['eligible_count'],
      'skipped_by_reason' => $preview['skipped_by_reason'],
    ];
    return hash('sha256', (string)$this->wp->wpJsonEncode($payload));
  }

  /**
   * @return array{processed_count: int, created_count: int, failed_count: int, skipped_by_reason: array<string, int>, completion_log_saved: bool}
   */
  private function getEmptyWorkerCounts(): array {
    return [
      'processed_count' => 0,
      'created_count' => 0,
      'failed_count' => 0,
      'skipped_by_reason' => [],
      'completion_log_saved' => false,
    ];
  }

  /**
   * @param array{preview_signature: string, automation_id: int, segment_id: int, filter_segment_id: int|null, selected_count: int, eligible_count: int, skipped_by_reason: array<string, int>, deferred_reason_keys: string[], duplicate_in_progress: bool} $preview
   */
  private function saveQueueAuditLog(ScheduledTaskEntity $task, array $preview, int $queuedCount): void {
    $log = new LogEntity();
    $log->setName(LoggerFactory::TOPIC_API);
    $log->setLevel(self::LOG_LEVEL_INFO);
    $log->setMessage(self::AUDIT_LOG_MESSAGE_QUEUED);
    $log->setRawMessage(self::AUDIT_LOG_MESSAGE_QUEUED);
    $log->setContext([
      'action' => 'manual_automation_start_queued',
      'requested_by' => (int)$this->wp->getCurrentUserId(),
      'task_id' => $task->getId(),
      'automation_id' => $preview['automation_id'],
      'segment_id' => $preview['segment_id'],
      'filter_segment_id' => $preview['filter_segment_id'],
      'selected_count' => $preview['selected_count'],
      'eligible_count' => $preview['eligible_count'],
      'queued_count' => $queuedCount,
      'skipped_by_reason' => $preview['skipped_by_reason'],
    ]);
    $this->logRepository->saveLog($log);
  }

  private function acquireLock(string $lockName): void {
    $result = $this->entityManager->getConnection()->executeQuery(
      'SELECT GET_LOCK(:lockName, 10)',
      ['lockName' => $lockName],
      ['lockName' => ParameterType::STRING]
    )->fetchOne();
    if (!is_numeric($result) || (int)$result !== 1) {
      throw new ApiException(
        __('Subscribers are already being queued for this automation.', 'mailpoet'),
        409,
        'manual_start_in_progress'
      );
    }
  }

  private function releaseLock(string $lockName): void {
    $this->entityManager->getConnection()->executeQuery(
      'SELECT RELEASE_LOCK(:lockName)',
      ['lockName' => $lockName],
      ['lockName' => ParameterType::STRING]
    );
  }

  private function getLockName(int $automationId): string {
    return sprintf('mailpoet_manual_start_%d', $automationId);
  }
}
