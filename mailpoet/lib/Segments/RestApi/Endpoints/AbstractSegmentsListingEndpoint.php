<?php declare(strict_types = 1);

namespace MailPoet\Segments\RestApi\Endpoints;

use MailPoet\API\REST\AbstractListingEndpoint;
use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\ListingRequestValidationTrait;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Validator\Builder;

abstract class AbstractSegmentsListingEndpoint extends AbstractListingEndpoint {
  use SegmentRequestValidationTrait;
  // Reused only for the shared `yyyy-MM-dd` date filter validators
  // (validateDateFilter / validateDateRange); pagination and sort use the
  // segment-specific SegmentRequestValidationTrait above.
  use ListingRequestValidationTrait;

  /** @var string[] */
  private $allowedSortFields;

  /** @var int */
  private $defaultPerPage;

  /**
   * @param string[] $allowedSortFields
   */
  public function __construct(
    ListingHandler $listingHandler,
    array $allowedSortFields,
    int $defaultPerPage
  ) {
    parent::__construct($listingHandler);
    $this->allowedSortFields = $allowedSortFields;
    $this->defaultPerPage = $defaultPerPage;
  }

  public function handle(Request $request): Response {
    $this->validateListingRequest($request);
    return parent::handle($request);
  }

  public static function getRequestSchema(): array {
    $schema = parent::getRequestSchema();
    $schema['limit'] = Builder::integer();
    $schema['offset'] = Builder::integer();
    $schema['sort_by'] = Builder::string();
    $schema['sort_order'] = Builder::string();
    return $schema;
  }

  abstract protected function allowsSearch(): bool;

  /**
   * Filter keys this listing accepts in the `filter` request param. Each
   * listing declares its own so unknown keys are rejected with a 400.
   *
   * @return string[]
   */
  abstract protected function getAllowedFilters(): array;

  protected function getListingValidationErrorPrefix(): string {
    return 'segments';
  }

  protected function getDefaultGroup(): ?string {
    return 'all';
  }

  protected function validateListingRequest(Request $request): void {
    $this->validateGroup(is_string($request->getParam('group')) ? (string)$request->getParam('group') : null);
    $orderParam = $request->getParam('order') ?? $request->getParam('sort_order');
    $this->validateOrder(is_string($orderParam) ? (string)$orderParam : null, $this->getDefaultSortOrder());

    $orderbyParam = $request->getParam('orderby') ?? $request->getParam('sort_by');
    $orderby = is_string($orderbyParam) && $orderbyParam !== ''
      ? (string)$orderbyParam
      : $this->getDefaultSortBy();
    if (!in_array($orderby, $this->allowedSortFields, true)) {
      throw new ApiException(
        sprintf(
          // translators: %s is the list of supported sort fields.
          __('Unsupported sort field. Allowed values are: %s.', 'mailpoet'),
          implode(', ', $this->allowedSortFields)
        ),
        400,
        'mailpoet_segments_invalid_orderby'
      );
    }

    $this->validatePage($request->getParam('page'));
    $this->validateOffset($request->getParam('offset'));
    $this->validatePerPage($request->getParam('per_page') ?? $request->getParam('limit'), $this->defaultPerPage);

    if (!$this->allowsSearch() && is_string($request->getParam('search')) && trim((string)$request->getParam('search')) !== '') {
      throw new ApiException(
        __('Search is not supported for this listing.', 'mailpoet'),
        400,
        'mailpoet_segments_search_not_supported'
      );
    }

    $this->validateFilters($request);
  }

  protected function validateFilters(Request $request): void {
    $filters = $request->getParam('filter');
    if ($filters === null || $filters === []) {
      return;
    }
    if (!is_array($filters)) {
      throw new ApiException(
        __('Filters must be an object.', 'mailpoet'),
        400,
        'mailpoet_segments_invalid_filter'
      );
    }

    $allowed = $this->getAllowedFilters();
    // Rebuild with verified string keys so the typed validators below receive an
    // array<string, mixed> rather than the request's array<mixed, mixed>.
    $normalizedFilters = [];
    foreach ($filters as $key => $value) {
      if (!is_string($key) || !in_array($key, $allowed, true)) {
        throw new ApiException(
          __('Unsupported segments filter.', 'mailpoet'),
          400,
          'mailpoet_segments_invalid_filter'
        );
      }
      $normalizedFilters[$key] = $value;
    }

    $this->validateScoreFilter($normalizedFilters);

    foreach ([['created_from', 'created_to'], ['updated_from', 'updated_to']] as [$fromKey, $toKey]) {
      if (!in_array($fromKey, $allowed, true)) {
        continue;
      }
      $from = $this->validateDateFilter($normalizedFilters, $fromKey);
      $to = $this->validateDateFilter($normalizedFilters, $toKey);
      $this->validateDateRange($from, $to);
    }
  }

  /**
   * @param array<string, mixed> $filters
   */
  private function validateScoreFilter(array $filters): void {
    foreach (['score_min', 'score_max'] as $key) {
      if (!array_key_exists($key, $filters) || $filters[$key] === '') {
        continue;
      }
      if (!is_numeric($filters[$key])) {
        throw new ApiException(
          __('The engagement score filter must be numeric.', 'mailpoet'),
          400,
          'mailpoet_segments_invalid_' . $key
        );
      }
    }
  }

  protected function getDefaultPerPage(): int {
    return $this->defaultPerPage;
  }
}
