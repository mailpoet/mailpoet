<?php declare(strict_types = 1);

namespace MailPoet\Logging\RestApi\Endpoints;

use MailPoet\API\REST\AbstractListingEndpoint;
use MailPoet\API\REST\ListingRequestValidationTrait;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\LogEntity;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Logging\LogListingRepository;
use MailPoet\Logging\RestApi\LogsFilterTrait;
use MailPoet\Validator\Builder;
use MailPoet\WP\Functions as WPFunctions;

class LogsListingEndpoint extends AbstractListingEndpoint {
  use ListingRequestValidationTrait;
  use LogsFilterTrait;

  private const ALLOWED_SORT_FIELDS = ['created_at', 'name'];
  private const DEFAULT_SORT_FIELD = 'created_at';
  private const DEFAULT_SORT_ORDER = 'desc';

  /** @var LogListingRepository */
  private $logListingRepository;

  public function __construct(
    ListingHandler $listingHandler,
    LogListingRepository $logListingRepository
  ) {
    parent::__construct($listingHandler);
    $this->logListingRepository = $logListingRepository;
  }

  public function handle(Request $request): Response {
    $this->validateRequest($request);
    return parent::handle($request);
  }

  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(AccessControl::PERMISSION_ACCESS_PLUGIN_ADMIN);
  }

  public static function getRequestSchema(): array {
    $schema = parent::getRequestSchema();
    $schema['limit'] = Builder::integer();
    $schema['offset'] = Builder::integer();
    $schema['filter'] = Builder::object();
    return $schema;
  }

  protected function getListingRepository(): ListingRepository {
    return $this->logListingRepository;
  }

  protected function buildItems(array $rows, ListingDefinition $definition): array {
    $items = [];
    foreach ($rows as $row) {
      if (!$row instanceof LogEntity) {
        continue;
      }
      $items[] = $this->buildItem($row);
    }
    return $items;
  }

  private function buildItem(LogEntity $log): array {
    $createdAt = $log->getCreatedAt();
    return [
      'id' => (int)$log->getId(),
      'name' => $log->getName() ?? '',
      'level' => $log->getLevel(),
      'message' => $log->getMessage() ?? '',
      'created_at' => $createdAt ? $createdAt->format('Y-m-d H:i:s') : null,
    ];
  }

  protected function getDefaultSortBy(): string {
    return self::DEFAULT_SORT_FIELD;
  }

  protected function getDefaultSortOrder(): string {
    return self::DEFAULT_SORT_ORDER;
  }

  protected function getListingValidationErrorPrefix(): string {
    return 'logs';
  }

  private function validateRequest(Request $request): void {
    $this->validateSortField($request->getParam('orderby'), self::ALLOWED_SORT_FIELDS);
    $this->validateSortField($request->getParam('sort_by'), self::ALLOWED_SORT_FIELDS);
    $this->validateSortOrder($request->getParam('order'));
    $this->validateSortOrder($request->getParam('sort_order'));
    $this->validatePagination($request);
    // Keep the range check in sync with getDateRangeError() in logs/url-state.ts.
    $this->validateAndNormalizeLogsFilter($request->getParam('filter'));
  }
}
