<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\WP\Functions;

class SegmentsResponseBuilder {
  const DATE_FORMAT = 'Y-m-d H:i:s';

  /** @var Functions */
  private $wp;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscriberRepository;

  public function __construct(
    Functions $wp,
    SegmentSubscribersRepository $segmentSubscriberRepository
  ) {
    $this->wp = $wp;
    $this->segmentSubscriberRepository = $segmentSubscriberRepository;
  }

  public function build(SegmentEntity $segment): array {
    $firstFilter = $segment->getDynamicFilters()->first();
    $filterData = $firstFilter ? $firstFilter->getFilterData() : null;
    return [
      'id' => (string)$segment->getId(), // (string) for BC
      'name' => $segment->getName(),
      'type' => $segment->getType(),
      'description' => $segment->getDescription(),
      'created_at' => $segment->getCreatedAt()->format(self::DATE_FORMAT),
      'updated_at' => $segment->getUpdatedAt()->format(self::DATE_FORMAT),
      'deleted_at' => ($deletedAt = $segment->getDeletedAt()) ? $deletedAt->format(self::DATE_FORMAT) : null,
      'average_engagement_score' => $segment->getAverageEngagementScore(),
      'filters_connect' => $filterData && $filterData->getParam('connect') ? $filterData->getParam('connect') : DynamicSegmentFilterData::CONNECT_TYPE_AND,
    ];
  }

  public function buildForListing(array $segments): array {
    $data = [];
    foreach ($segments as $segment) {
      $data[] = $this->buildListingItem($segment);
    }
    return $data;
  }

  private function buildListingItem(SegmentEntity $segment): array {
    $data = $this->build($segment);

    $data['subscribers_count'] = $this->segmentSubscriberRepository->getSubscribersStatisticsCount($segment);
    $data['subscribers_url'] = $this->wp->adminUrl(
      'admin.php?page=mailpoet-subscribers#/filter[segment=' . $segment->getId() . ']'
    );
    return $data;
  }
}
