<?php declare(strict_types = 1);

namespace MailPoet\Segments\RestApi\Endpoints;

use MailPoet\API\JSON\ResponseBuilders\DynamicSegmentsResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Segments\DynamicSegments\DynamicSegmentsListingRepository;
use MailPoet\WP\Functions as WPFunctions;

class DynamicSegmentsListingEndpoint extends AbstractSegmentsListingEndpoint {
  /** @var DynamicSegmentsListingRepository */
  private $dynamicSegmentsListingRepository;

  /** @var DynamicSegmentsResponseBuilder */
  private $dynamicSegmentsResponseBuilder;

  public function __construct(
    ListingHandler $listingHandler,
    DynamicSegmentsListingRepository $dynamicSegmentsListingRepository,
    DynamicSegmentsResponseBuilder $dynamicSegmentsResponseBuilder
  ) {
    parent::__construct($listingHandler, [
      'name',
      'created_at',
      'updated_at',
    ], 25);
    $this->dynamicSegmentsListingRepository = $dynamicSegmentsListingRepository;
    $this->dynamicSegmentsResponseBuilder = $dynamicSegmentsResponseBuilder;
  }

  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(AccessControl::PERMISSION_MANAGE_SEGMENTS);
  }

  protected function getListingRepository(): ListingRepository {
    return $this->dynamicSegmentsListingRepository;
  }

  protected function buildItems(array $rows, ListingDefinition $definition): array {
    return $this->dynamicSegmentsResponseBuilder->buildForListing($rows);
  }

  protected function getDefaultSortBy(): string {
    return 'updated_at';
  }

  protected function getDefaultSortOrder(): string {
    return 'desc';
  }

  protected function allowsSearch(): bool {
    return true;
  }

  protected function getAllowedFilters(): array {
    return ['created_from', 'created_to', 'updated_from', 'updated_to'];
  }

  protected function getDefaultParameters(): array {
    return ['segments'];
  }
}
