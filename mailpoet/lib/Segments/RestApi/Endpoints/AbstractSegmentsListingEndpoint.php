<?php declare(strict_types = 1);

namespace MailPoet\Segments\RestApi\Endpoints;

use MailPoet\API\REST\AbstractListingEndpoint;
use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Validator\Builder;

abstract class AbstractSegmentsListingEndpoint extends AbstractListingEndpoint {
  use SegmentRequestValidationTrait;

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
  }

  protected function getDefaultPerPage(): int {
    return $this->defaultPerPage;
  }
}
