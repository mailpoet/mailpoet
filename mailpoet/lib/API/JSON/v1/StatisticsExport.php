<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\Workers\StatisticsExport as StatisticsExportWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class StatisticsExport extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var StatisticsExporter */
  private $exporter;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var CapabilitiesManager */
  private $capabilitiesManager;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    StatisticsExporter $exporter,
    LoggerFactory $loggerFactory,
    ScheduledTasksRepository $scheduledTasksRepository,
    CapabilitiesManager $capabilitiesManager,
    WPFunctions $wp
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->exporter = $exporter;
    $this->loggerFactory = $loggerFactory;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->capabilitiesManager = $capabilitiesManager;
    $this->wp = $wp;
  }

  public function exportCampaign($data = []) {
    $newsletterId = isset($data['id']) ? (int)$data['id'] : 0;
    if ($newsletterId <= 0) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Missing newsletter id.', 'mailpoet'),
      ]);
    }

    $format = $this->resolveFormat($data);
    if ($format === null) {
      return $this->unsupportedFormatResponse();
    }

    $newsletter = $this->newslettersRepository->findOneById($newsletterId);
    if (!$newsletter) {
      return $this->newsletterNotFoundResponse();
    }

    try {
      $result = $this->exporter->exportSingleAggregate($newsletter, $format);
    } catch (\Throwable $e) {
      if (function_exists('error_log')) {
        // phpcs:disable QITStandard.PHP.DebugCode.DebugFunctionFound
        error_log((string)$e); // phpcs:ignore Squiz.PHP.DiscouragedFunctions
        // phpcs:enable QITStandard.PHP.DebugCode.DebugFunctionFound
      }
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_API)->warning('Campaign statistics export failed.', [
        'exceptionMessage' => $e->getMessage(),
        'exceptionTrace' => $e->getTraceAsString(),
      ]);
      return $this->errorResponse([
        APIError::UNKNOWN => __('Could not generate the export. Please try again.', 'mailpoet'),
      ], [], Response::STATUS_BAD_REQUEST);
    }

    return $this->successResponse($result);
  }

  public function exportRecipients($data = []) {
    $newsletterId = isset($data['id']) ? (int)$data['id'] : 0;
    if ($newsletterId <= 0) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Missing newsletter id.', 'mailpoet'),
      ]);
    }

    $format = $this->resolveFormat($data);
    if ($format === null) {
      return $this->unsupportedFormatResponse();
    }

    if ($this->isDetailedAnalyticsRestricted()) {
      return $this->errorResponse([
        APIError::FORBIDDEN => __('Per-recipient export requires a MailPoet plan with detailed analytics.', 'mailpoet'),
      ], [], Response::STATUS_FORBIDDEN);
    }

    $newsletter = $this->newslettersRepository->findOneById($newsletterId);
    if (!$newsletter) {
      return $this->newsletterNotFoundResponse();
    }

    $task = new ScheduledTaskEntity();
    $task->setType(StatisticsExportWorker::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setScheduledAt(Carbon::now()->millisecond(0));
    $task->setPriority(ScheduledTaskEntity::PRIORITY_HIGH);
    $task->setMeta([
      'job_type' => StatisticsExportWorker::JOB_TYPE_RECIPIENTS,
      'newsletter_id' => $newsletter->getId(),
      'format' => $format,
      'requested_by' => (int)$this->wp->getCurrentUserId(),
    ]);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    return $this->successResponse($this->buildStatusPayload($task));
  }

  public function getStatus($data = []) {
    $taskId = isset($data['task_id']) ? (int)$data['task_id'] : 0;
    if ($taskId <= 0) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Missing task id.', 'mailpoet'),
      ]);
    }

    if ($this->isDetailedAnalyticsRestricted()) {
      return $this->errorResponse([
        APIError::FORBIDDEN => __('Per-recipient export requires a MailPoet plan with detailed analytics.', 'mailpoet'),
      ], [], Response::STATUS_FORBIDDEN);
    }

    $task = $this->scheduledTasksRepository->findOneById($taskId);
    if (!$task instanceof ScheduledTaskEntity || $task->getType() !== StatisticsExportWorker::TASK_TYPE) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('Export task not found.', 'mailpoet'),
      ], [], Response::STATUS_NOT_FOUND);
    }

    $meta = $task->getMeta() ?? [];
    $jobType = isset($meta['job_type']) ? (string)$meta['job_type'] : '';
    if ($jobType !== StatisticsExportWorker::JOB_TYPE_RECIPIENTS) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('Export task not found.', 'mailpoet'),
      ], [], Response::STATUS_NOT_FOUND);
    }

    $requestedBy = isset($meta['requested_by']) ? (int)$meta['requested_by'] : 0;
    if ($requestedBy !== (int)$this->wp->getCurrentUserId()) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('Export task not found.', 'mailpoet'),
      ], [], Response::STATUS_NOT_FOUND);
    }

    return $this->successResponse($this->buildStatusPayload($task));
  }

  private function isDetailedAnalyticsRestricted(): bool {
    $capability = $this->capabilitiesManager->getCapability('detailedAnalytics');
    return $capability === null || $capability->isRestricted;
  }

  private function resolveFormat(array $data): ?string {
    $format = isset($data['format']) && is_string($data['format'])
      ? strtolower($data['format'])
      : StatisticsExporter::FORMAT_CSV;
    if ($format !== StatisticsExporter::FORMAT_CSV && $format !== StatisticsExporter::FORMAT_XLSX) {
      return null;
    }
    return $format;
  }

  private function unsupportedFormatResponse() {
    return $this->badRequest([
      APIError::BAD_REQUEST => __('Unsupported export format. Use csv or xlsx.', 'mailpoet'),
    ]);
  }

  private function newsletterNotFoundResponse() {
    return $this->errorResponse([
      APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
    ], [], Response::STATUS_NOT_FOUND);
  }

  /**
   * @return array{
   *   taskId: int,
   *   status: string,
   *   exportFileURL?: string,
   *   totalExported?: int,
   *   error?: string,
   * }
   */
  private function buildStatusPayload(ScheduledTaskEntity $task): array {
    $meta = $task->getMeta() ?? [];
    $status = $task->getStatus() ?? ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING;

    $payload = [
      'taskId' => (int)$task->getId(),
      'status' => $status,
    ];
    if (isset($meta['export_file_url'])) {
      $payload['exportFileURL'] = (string)$meta['export_file_url'];
    }
    if (isset($meta['total_exported'])) {
      $payload['totalExported'] = (int)$meta['total_exported'];
    }
    if (isset($meta['error'])) {
      $payload['error'] = (string)$meta['error'];
    }
    return $payload;
  }
}
