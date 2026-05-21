<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Endpoint;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\Workers\StatisticsExport;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Newsletter\BulkActionController;
use MailPoet\Newsletter\BulkActionException;
use MailPoet\Newsletter\Listing\NewsletterListingRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\Validator\Builder;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

/**
 * `POST /mailpoet/v1/newsletters/bulk-action`
 *
 * Mirrors the legacy JSON endpoint: `trash`, `restore`, and `delete` flow
 * through {@see BulkActionController}; `export_stats` is handled here because
 * it is async (it queues a scheduled task) and gated behind a premium
 * capability instead of the bulk dispatcher's plain list of supported actions.
 */
class NewslettersBulkActionEndpoint extends Endpoint {
  public const ACTION_EXPORT_STATS = 'export_stats';

  /** @var ListingHandler */
  private $listingHandler;

  /** @var NewsletterListingRepository */
  private $newsletterListingRepository;

  /** @var BulkActionController */
  private $bulkActionController;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var CapabilitiesManager */
  private $capabilitiesManager;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    ListingHandler $listingHandler,
    NewsletterListingRepository $newsletterListingRepository,
    BulkActionController $bulkActionController,
    ScheduledTasksRepository $scheduledTasksRepository,
    CapabilitiesManager $capabilitiesManager,
    WPFunctions $wp
  ) {
    $this->listingHandler = $listingHandler;
    $this->newsletterListingRepository = $newsletterListingRepository;
    $this->bulkActionController = $bulkActionController;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->capabilitiesManager = $capabilitiesManager;
    $this->wp = $wp;
  }

  public function checkPermissions(): bool {
    return $this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_EMAILS);
  }

  public function handle(Request $request): Response {
    $actionParam = $request->getParam('action');
    $action = is_string($actionParam) ? $actionParam : '';
    $selectAll = $request->getParam('select_all') === true;
    $selection = $this->getSelection($request);
    if ($selection === [] && !$selectAll) {
      throw new ApiException(
        __('No newsletters selected.', 'mailpoet'),
        400,
        'mailpoet_newsletters_missing_selection'
      );
    }
    $definition = $this->buildDefinition($request, $selection);

    if ($action === self::ACTION_EXPORT_STATS) {
      return $this->handleExportStats($request, $definition);
    }

    try {
      $result = $this->bulkActionController->execute($action, $definition);
    } catch (BulkActionException $exception) {
      throw new ApiException(
        $exception->getMessage(),
        $exception->getStatusCode(),
        $exception->getErrorCode()
      );
    }

    return new Response([
      'action' => $result['action'],
      'count' => $result['count'],
    ]);
  }

  public static function getRequestSchema(): array {
    return [
      'action' => Builder::string()->required(),
      'selection' => Builder::array(Builder::integer()),
      'type' => Builder::string(),
      'group' => Builder::string(),
      'search' => Builder::string(),
      'filter' => Builder::object(),
      'parent_id' => Builder::integer(),
      'select_all' => Builder::boolean(),
      'format' => Builder::string(),
    ];
  }

  private function handleExportStats(Request $request, ListingDefinition $definition): Response {
    $capability = $this->capabilitiesManager->getCapability('detailedAnalytics');
    if ($capability === null || $capability->isRestricted) {
      throw new ApiException(
        __('Bulk statistics export requires a MailPoet plan with detailed analytics.', 'mailpoet'),
        403,
        'mailpoet_newsletters_export_forbidden'
      );
    }

    $ids = array_values(array_map('intval', $this->newsletterListingRepository->getActionableIds($definition)));
    if ($ids === []) {
      throw new ApiException(
        __('No newsletters selected for export.', 'mailpoet'),
        400,
        'mailpoet_newsletters_export_empty'
      );
    }

    $formatParam = $request->getParam('format');
    $format = is_string($formatParam) ? strtolower($formatParam) : StatisticsExporter::FORMAT_CSV;
    if ($format !== StatisticsExporter::FORMAT_CSV && $format !== StatisticsExporter::FORMAT_XLSX) {
      throw new ApiException(
        __('Unsupported export format. Use csv or xlsx.', 'mailpoet'),
        400,
        'mailpoet_newsletters_export_invalid_format'
      );
    }

    $task = new ScheduledTaskEntity();
    $task->setType(StatisticsExport::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setScheduledAt(Carbon::now()->millisecond(0));
    $task->setPriority(ScheduledTaskEntity::PRIORITY_HIGH);
    $task->setMeta([
      'job_type' => StatisticsExport::JOB_TYPE_BULK,
      'newsletter_ids' => $ids,
      'format' => $format,
      'requested_by' => (int)$this->wp->getCurrentUserId(),
    ]);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    return new Response([
      'action' => self::ACTION_EXPORT_STATS,
      'task_id' => (int)$task->getId(),
      'count' => count($ids),
    ]);
  }

  /**
   * @return int[]
   */
  private function getSelection(Request $request): array {
    $selection = $request->getParam('selection');
    return is_array($selection) ? $this->toIntList($selection) : [];
  }

  /**
   * @param int[] $selection
   */
  private function buildDefinition(Request $request, array $selection): ListingDefinition {
    $filter = $request->getParam('filter');
    $searchParam = $request->getParam('search');
    $groupParam = $request->getParam('group');
    $typeParam = $request->getParam('type');
    $parentIdParam = $request->getParam('parent_id');

    $params = [];
    if (is_string($typeParam) && in_array($typeParam, NewslettersListingEndpoint::SUPPORTED_TYPES, true)) {
      $params['type'] = $typeParam;
    }
    if (is_numeric($parentIdParam)) {
      $params['parentId'] = (int)$parentIdParam;
    }

    return $this->listingHandler->getListingDefinition([
      'offset' => 0,
      'limit' => 0,
      'sort_by' => 'id',
      'sort_order' => 'desc',
      'search' => is_string($searchParam) ? $searchParam : null,
      'group' => is_string($groupParam) ? $groupParam : null,
      'filter' => is_array($filter) ? $filter : [],
      'selection' => $selection,
      'params' => $params,
    ]);
  }

  /**
   * @param array<mixed> $values
   * @return int[]
   */
  private function toIntList(array $values): array {
    $ints = [];
    foreach ($values as $value) {
      if (is_scalar($value) && (int)$value > 0) {
        $ints[] = (int)$value;
      }
    }
    return array_values(array_unique($ints));
  }
}
