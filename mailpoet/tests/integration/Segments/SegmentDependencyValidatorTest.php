<?php declare(strict_types = 1);

namespace MailPoet\Segments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;

class SegmentDependencyValidatorTest extends \MailPoetTest {
  public function testItMissingPluginsForWooCommerceDynamicSegment(): void {
    $dynamicSegment = $this->createSegment(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      WooCommerceCategory::ACTION_CATEGORY,
      [
        'category_ids' => ['1'],
        'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      ]
    );
    // Plugin is not active
    $validator = $this->createValidator(false);
    $missingPlugins = $validator->getMissingPluginsBySegment($dynamicSegment);
    expect($missingPlugins)->equals(['WooCommerce']);

    // Plugin is active
    $validator = $this->createValidator(true);
    $missingPlugins = $validator->getMissingPluginsBySegment($dynamicSegment);
    expect($missingPlugins)->equals([]);
  }

  private function createSegment(string $filterType, string $action, array $filterData): SegmentEntity {
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $filterData = new DynamicSegmentFilterData($filterType, $action, $filterData);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $filterData);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $segment;
  }

  private function createValidator(
    bool $isPluginActive,
    bool $hasValidPremiumKey = true,
    bool $subscribersLimitReached = false
  ): SegmentDependencyValidator {
    $wp = $this->make(WPFunctions::class, [
      'isPluginActive' => $isPluginActive,
    ]);
    $subscribersFeature = $this->make(SubscribersFeature::class, [
      'hasValidPremiumKey' => $hasValidPremiumKey,
      'check' => $subscribersLimitReached,
    ]);
    return new SegmentDependencyValidator($subscribersFeature, $wp);
  }
}
