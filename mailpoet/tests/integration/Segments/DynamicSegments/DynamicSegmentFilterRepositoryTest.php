<?php declare(strict_types = 1);

namespace MailPoet\Segments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\DynamicSegments\DynamicSegmentFilterRepository;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceTotalSpent;

class DynamicSegmentFilterRepositoryTest extends \MailPoetTest {
  /** @var DynamicSegmentFilterRepository */
  private $dynamicSegmentFilterRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function _before(): void {
    parent::_before();
    $this->dynamicSegmentFilterRepository = $this->diContainer->get(DynamicSegmentFilterRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
  }

  public function testItReturnsDynamicSegmentFilterBySegmentTypeAndAction(): void {
    $segment = $this->createSegment('Dynamic Segment');
    $this->createDynamicSegmentFilter($segment, DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceTotalSpent::ACTION_TOTAL_SPENT);

    $dynamicFilter = $this->dynamicSegmentFilterRepository->findOnyByFilterTypeAndAction(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      WooCommerceTotalSpent::ACTION_TOTAL_SPENT
    );
    $this->assertInstanceOf(DynamicSegmentFilterEntity::class, $dynamicFilter);
    expect($dynamicFilter->getFilterData()->getFilterType())->equals(DynamicSegmentFilterData::TYPE_WOOCOMMERCE);
    expect($dynamicFilter->getFilterData()->getAction())->equals(WooCommerceTotalSpent::ACTION_TOTAL_SPENT);

    $dynamicFilter = $this->dynamicSegmentFilterRepository->findOnyByFilterTypeAndAction(
      DynamicSegmentFilterData::TYPE_USER_ROLE,
      UserRole::TYPE
    );
    expect($dynamicFilter)->null();
  }

  private function createSegment(string $name): SegmentEntity {
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DYNAMIC, '');
    $this->segmentsRepository->persist($segment);
    $this->segmentsRepository->flush();
    return $segment;
  }

  private function createDynamicSegmentFilter(
    SegmentEntity $segment,
    string $filterType,
    string $action
  ): DynamicSegmentFilterEntity {
    $filter = new DynamicSegmentFilterEntity($segment, new DynamicSegmentFilterData($filterType, $action));
    $this->dynamicSegmentFilterRepository->persist($filter);
    $this->dynamicSegmentFilterRepository->flush();
    return $filter;
  }
}
