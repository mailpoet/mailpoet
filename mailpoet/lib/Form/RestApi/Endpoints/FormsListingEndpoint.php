<?php declare(strict_types = 1);

namespace MailPoet\Form\RestApi\Endpoints;

use MailPoet\API\JSON\ResponseBuilders\FormsResponseBuilder;
use MailPoet\API\REST\AbstractListingEndpoint;
use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\ListingRequestValidationTrait;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\Listing\FormListingRepository;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Validator\Builder;
use MailPoet\WP\Functions as WPFunctions;

class FormsListingEndpoint extends AbstractListingEndpoint {
  use ListingRequestValidationTrait;

  private const ALLOWED_SORT_FIELDS = ['name', 'created_at', 'updated_at'];
  private const ALLOWED_FILTERS = ['status', 'created_from', 'created_to', 'updated_from', 'updated_to'];

  /** @var FormListingRepository */
  private $formListingRepository;

  /** @var FormsResponseBuilder */
  private $formsResponseBuilder;

  public function __construct(
    ListingHandler $listingHandler,
    FormListingRepository $formListingRepository,
    FormsResponseBuilder $formsResponseBuilder
  ) {
    parent::__construct($listingHandler);
    $this->formListingRepository = $formListingRepository;
    $this->formsResponseBuilder = $formsResponseBuilder;
  }

  public function handle(Request $request): Response {
    $this->validateRequest($request);
    return parent::handle($request);
  }

  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(AccessControl::PERMISSION_MANAGE_FORMS);
  }

  public static function getRequestSchema(): array {
    $schema = parent::getRequestSchema();
    $schema['limit'] = Builder::integer();
    $schema['offset'] = Builder::integer();
    $schema['filter'] = Builder::object();
    return $schema;
  }

  protected function getListingRepository(): ListingRepository {
    return $this->formListingRepository;
  }

  protected function buildItems(array $rows, ListingDefinition $definition): array {
    return $this->formsResponseBuilder->buildForListing($rows);
  }

  protected function getDefaultSortBy(): string {
    return 'updated_at';
  }

  protected function getDefaultSortOrder(): string {
    return 'desc';
  }

  protected function getDefaultGroup(): ?string {
    return 'all';
  }

  protected function getListingValidationErrorPrefix(): string {
    return 'forms';
  }

  private function validateRequest(Request $request): void {
    $this->validateSortField($request->getParam('orderby'), self::ALLOWED_SORT_FIELDS);
    $this->validateSortField($request->getParam('sort_by'), self::ALLOWED_SORT_FIELDS);
    $this->validateSortOrder($request->getParam('order'));
    $this->validateSortOrder($request->getParam('sort_order'));
    $this->validatePagination($request);
    $this->validateFilters($request->getParam('filter'));
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
        'mailpoet_forms_invalid_filter'
      );
    }

    $normalizedFilters = [];
    foreach ($filters as $filter => $value) {
      if (!is_string($filter) || !in_array($filter, self::ALLOWED_FILTERS, true)) {
        throw new ApiException(
          __('Unsupported forms filter.', 'mailpoet'),
          400,
          'mailpoet_forms_invalid_filter'
        );
      }
      $normalizedFilters[$filter] = $value;
    }

    $this->validateStatusFilter($normalizedFilters);
    $this->validateDateRange(
      $this->validateDateFilter($normalizedFilters, 'created_from'),
      $this->validateDateFilter($normalizedFilters, 'created_to')
    );
    $this->validateDateRange(
      $this->validateDateFilter($normalizedFilters, 'updated_from'),
      $this->validateDateFilter($normalizedFilters, 'updated_to')
    );
  }

  /** @param array<string, mixed> $filters */
  private function validateStatusFilter(array $filters): void {
    if (!array_key_exists('status', $filters) || $filters['status'] === '' || $filters['status'] === []) {
      return;
    }
    $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
    $allowed = [FormEntity::STATUS_ENABLED, FormEntity::STATUS_DISABLED];
    foreach ($statuses as $status) {
      if (!is_string($status) || !in_array($status, $allowed, true)) {
        throw new ApiException(
          sprintf(
            // translators: %s is a comma-separated list of allowed form statuses.
            __('Unsupported status filter. Allowed values are: %s.', 'mailpoet'),
            implode(', ', $allowed)
          ),
          400,
          'mailpoet_forms_invalid_status'
        );
      }
    }
  }
}
