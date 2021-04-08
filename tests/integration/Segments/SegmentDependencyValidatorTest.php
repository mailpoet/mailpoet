<?php declare(strict_types = 1);

namespace MailPoet\Segments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\WP\Functions as WPFunctions;

class SegmentDependencyValidatorTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
    $this->cleanup();
  }

  public function testItMissingPluginsForWooCommerceDynamicSegment(): void {
    $dynamicSegment = $this->createSegment([
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => 'purchasedCategory',
      'category_id' => 1,
    ]);
    // Plugin is not active
    $validator = $this->createValidator(false);
    $missingPlugins = $validator->getMissingPluginsBySegment($dynamicSegment);
    expect($missingPlugins)->equals(['WooCommerce']);

    // Plugin is active
    $validator = $this->createValidator(true);
    $missingPlugins = $validator->getMissingPluginsBySegment($dynamicSegment);
    expect($missingPlugins)->equals([]);
  }

  private function createSegment(array $filterData): SegmentEntity {
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $filterData = new DynamicSegmentFilterData($filterData);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $filterData);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $segment;
  }

  private function createValidator(bool $isPluginActive): SegmentDependencyValidator {
    $wp = $this->make(WPFunctions::class, [
      'isPluginActive' => $isPluginActive,
    ]);
    return new SegmentDependencyValidator($wp);
  }

  private function cleanup(): void {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(DynamicSegmentFilterEntity::class);
  }
}
