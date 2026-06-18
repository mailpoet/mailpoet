<?php declare(strict_types = 1);

namespace MailPoet\Logging\RestApi\Endpoints;

use DateTimeImmutable;
use MailPoet\API\REST\AbstractListingEndpoint;
use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\LogEntity;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Logging\LogListingRepository;
use MailPoet\Validator\Builder;
use MailPoet\WP\Functions as WPFunctions;

class LogsListingEndpoint extends AbstractListingEndpoint {
  private const ALLOWED_SORT_FIELDS = ['created_at', 'name'];
  private const ALLOWED_SORT_ORDERS = ['asc', 'desc'];
  private const DEFAULT_SORT_FIELD = 'created_at';
  private const DEFAULT_SORT_ORDER = 'desc';
  private const ALLOWED_FILTERS = ['from', 'to', 'name', 'level'];

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

  private function validateRequest(Request $request): void {
    $this->validateSortField($request->getParam('orderby'));
    $this->validateSortField($request->getParam('sort_by'));
    $this->validateSortOrder($request->getParam('order'));
    $this->validateSortOrder($request->getParam('sort_order'));
    $this->validatePositiveInteger($request->getParam('page'), 'page', 1, self::MAX_PAGE);
    $this->validatePositiveInteger($request->getParam('per_page'), 'per_page', 1, self::MAX_PER_PAGE);
    $this->validatePositiveInteger($request->getParam('limit'), 'limit', 1, self::MAX_PER_PAGE);
    $this->validatePositiveInteger($request->getParam('offset'), 'offset', 0, self::MAX_PAGE);
    $this->validateFilters($request->getParam('filter'));
  }

  /** @param mixed $sortField */
  private function validateSortField($sortField): void {
    if ($sortField === null || $sortField === '') {
      return;
    }
    if (!is_string($sortField) || !in_array($sortField, self::ALLOWED_SORT_FIELDS, true)) {
      throw new ApiException(
        sprintf(
          // translators: %s is a comma-separated list of allowed sort fields.
          __('Unsupported sort field. Allowed values are: %s.', 'mailpoet'),
          implode(', ', self::ALLOWED_SORT_FIELDS)
        ),
        400,
        'mailpoet_logs_invalid_orderby'
      );
    }
  }

  /** @param mixed $sortOrder */
  private function validateSortOrder($sortOrder): void {
    if ($sortOrder === null || $sortOrder === '') {
      return;
    }
    if (!is_string($sortOrder) || !in_array(strtolower($sortOrder), self::ALLOWED_SORT_ORDERS, true)) {
      throw new ApiException(
        sprintf(
          // translators: %s is a comma-separated list of allowed sort orders.
          __('Unsupported sort order. Allowed values are: %s.', 'mailpoet'),
          implode(', ', self::ALLOWED_SORT_ORDERS)
        ),
        400,
        'mailpoet_logs_invalid_order'
      );
    }
  }

  /**
   * @param mixed $value
   */
  private function validatePositiveInteger($value, string $name, int $min, int $max): void {
    if ($value === null || $value === '') {
      return;
    }
    $integer = $this->getIntegerValue($value);
    if ($integer === null || $integer < $min || $integer > $max) {
      throw new ApiException(
        sprintf(
          // translators: %1$s is a request parameter name, %2$d is the maximum accepted value.
          __('%1$s must be an integer no greater than %2$d.', 'mailpoet'),
          $name,
          $max
        ),
        400,
        'mailpoet_logs_invalid_' . $name
      );
    }
  }

  /** @param mixed $value */
  private function getIntegerValue($value): ?int {
    if (is_int($value)) {
      return $value;
    }
    if (is_string($value) && ctype_digit($value)) {
      return (int)$value;
    }
    return null;
  }

  /** @param mixed $filters */
  private function validateFilters($filters): void {
    if ($filters === null || $filters === []) {
      return;
    }
    if (!is_array($filters)) {
      throw new ApiException(
        __('Filters must be an object.', 'mailpoet'),
        400,
        'mailpoet_logs_invalid_filter'
      );
    }

    $normalizedFilters = [];
    foreach ($filters as $filter => $value) {
      if (!is_string($filter) || !in_array($filter, self::ALLOWED_FILTERS, true)) {
        throw new ApiException(
          __('Unsupported logs filter.', 'mailpoet'),
          400,
          'mailpoet_logs_invalid_filter'
        );
      }
      $normalizedFilters[$filter] = $value;
    }

    $this->validateStringListFilter($normalizedFilters, 'name');
    $this->validateLevelFilter($normalizedFilters);

    $from = $this->validateDateFilter($normalizedFilters, 'from');
    $to = $this->validateDateFilter($normalizedFilters, 'to');
    // Keep in sync with getDateRangeError() in logs/url-state.ts.
    if ($from && $to && $from > $to) {
      throw new ApiException(
        __('The from date must be before or equal to the to date.', 'mailpoet'),
        400,
        'mailpoet_logs_invalid_date_range'
      );
    }
  }

  /**
   * @param array<string, mixed> $filters
   */
  private function validateDateFilter(array $filters, string $field): ?DateTimeImmutable {
    if (!array_key_exists($field, $filters) || $filters[$field] === '') {
      return null;
    }
    if (!is_string($filters[$field])) {
      throw new ApiException(
        __('Log date filters must use the YYYY-MM-DD format.', 'mailpoet'),
        400,
        'mailpoet_logs_invalid_' . $field
      );
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $filters[$field]);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $filters[$field]) {
      throw new ApiException(
        __('Log date filters must use the YYYY-MM-DD format.', 'mailpoet'),
        400,
        'mailpoet_logs_invalid_' . $field
      );
    }
    return $date;
  }

  /**
   * @param array<string, mixed> $filters
   */
  private function validateStringListFilter(array $filters, string $field): void {
    if (!array_key_exists($field, $filters) || $filters[$field] === '' || $filters[$field] === []) {
      return;
    }
    if (!is_array($filters[$field])) {
      throw new ApiException(
        __('The name filter must be an array of strings.', 'mailpoet'),
        400,
        'mailpoet_logs_invalid_' . $field
      );
    }
    foreach ($filters[$field] as $value) {
      if (!is_string($value)) {
        throw new ApiException(
          __('The name filter must be an array of strings.', 'mailpoet'),
          400,
          'mailpoet_logs_invalid_' . $field
        );
      }
    }
  }

  /**
   * @param array<string, mixed> $filters
   */
  private function validateLevelFilter(array $filters): void {
    if (!array_key_exists('level', $filters) || $filters['level'] === '' || $filters['level'] === []) {
      return;
    }
    if (!is_array($filters['level'])) {
      throw new ApiException(
        __('The level filter must be an array of integers.', 'mailpoet'),
        400,
        'mailpoet_logs_invalid_level'
      );
    }
    foreach ($filters['level'] as $value) {
      if ($this->getIntegerValue($value) === null) {
        throw new ApiException(
          __('The level filter must be an array of integers.', 'mailpoet'),
          400,
          'mailpoet_logs_invalid_level'
        );
      }
    }
  }
}
