<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\SegmentDependencyValidator;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\WP\Functions;

class DynamicSegmentsResponseBuilder {
  const DATE_FORMAT = 'Y-m-d H:i:s';

  /** @var SegmentsResponseBuilder */
  private $segmentsResponseBuilder;

  /** @var Functions */
  private $wp;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscriberRepository;

  /** @var SegmentDependencyValidator */
  private $segmentDependencyValidator;

  public function __construct(
    Functions $wp,
    SegmentSubscribersRepository $segmentSubscriberRepository,
    SegmentsResponseBuilder $segmentsResponseBuilder,
    SegmentDependencyValidator $segmentDependencyValidator
  ) {
    $this->segmentsResponseBuilder = $segmentsResponseBuilder;
    $this->segmentSubscriberRepository = $segmentSubscriberRepository;
    $this->wp = $wp;
    $this->segmentDependencyValidator = $segmentDependencyValidator;
  }

  public function build(SegmentEntity $segmentEntity) {
    $data = $this->segmentsResponseBuilder->build($segmentEntity);
    $data = $this->addMissingPluginProperties($segmentEntity, $data);
    // So far we allow dynamic segments to have only one filter
    $filter = $segmentEntity->getDynamicFilters()->first();
    if (!$filter instanceof DynamicSegmentFilterEntity) {
      return $data;
    }
    return array_merge($data, $filter->getFilterData()->getData() ?? []);
  }

  public function buildForListing(array $segments): array {
    $data = [];
    foreach ($segments as $segment) {
      $data[] = $this->buildListingItem($segment);
    }
    return $data;
  }

  private function addMissingPluginProperties(SegmentEntity $segment, array $data): array {
    $missingPlugins = $this->segmentDependencyValidator->getMissingPluginsBySegment($segment);
    if ($missingPlugins) {
      $missingPlugin = reset($missingPlugins);
      $data['is_plugin_missing'] = true;
      $data['missing_plugin_message'] = sprintf(
        __('Activate the %s plugin to see the number of subscribers and enable the editing of this segment.', 'mailpoet'),
        $missingPlugin
      );
    } else {
      $data['is_plugin_missing'] = false;
      $data['missing_plugin_message'] = null;
    }
    return $data;
  }

  private function buildListingItem(SegmentEntity $segment): array {
    $data = $this->segmentsResponseBuilder->build($segment);
    $data = $this->addMissingPluginProperties($segment, $data);
    $data['subscribers_url'] = $this->wp->adminUrl(
      'admin.php?page=mailpoet-subscribers#/filter[segment=' . $segment->getId() . ']'
    );

    $data['count_all'] = $this->segmentSubscriberRepository->getSubscribersCount((int)$segment->getId());
    $data['count_subscribed'] = $this->segmentSubscriberRepository->getSubscribersCount((int)$segment->getId(), SubscriberEntity::STATUS_SUBSCRIBED);
    return $data;
  }
}
