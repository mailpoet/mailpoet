<?php declare(strict_types = 1);

namespace MailPoet\Segments\RestApi\Endpoints;

use MailPoet\API\JSON\ResponseBuilders\SegmentsResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Segments\SegmentListingRepository;
use MailPoet\WP\Functions as WPFunctions;

class SegmentsListingEndpoint extends AbstractSegmentsListingEndpoint {
  /** @var SegmentListingRepository */
  private $segmentListingRepository;

  /** @var SegmentsResponseBuilder */
  private $segmentsResponseBuilder;

  public function __construct(
    ListingHandler $listingHandler,
    SegmentListingRepository $segmentListingRepository,
    SegmentsResponseBuilder $segmentsResponseBuilder
  ) {
    parent::__construct($listingHandler, [
      'name',
      'created_at',
      'updated_at',
      'average_engagement_score',
    ], 20);
    $this->segmentListingRepository = $segmentListingRepository;
    $this->segmentsResponseBuilder = $segmentsResponseBuilder;
  }

  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(AccessControl::PERMISSION_MANAGE_SEGMENTS);
  }

  protected function getListingRepository(): ListingRepository {
    return $this->segmentListingRepository;
  }

  protected function buildItems(array $rows, ListingDefinition $definition): array {
    return $this->segmentsResponseBuilder->buildForListing($rows);
  }

  protected function getDefaultSortBy(): string {
    return 'name';
  }

  protected function getDefaultSortOrder(): string {
    return 'asc';
  }

  protected function allowsSearch(): bool {
    return true;
  }

  protected function getDefaultParameters(): array {
    return ['lists'];
  }
}
