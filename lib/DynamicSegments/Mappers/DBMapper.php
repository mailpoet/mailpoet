<?php

namespace MailPoet\DynamicSegments\Mappers;

use MailPoet\DynamicSegments\Exceptions\InvalidSegmentTypeException;
use MailPoet\DynamicSegments\Filters\EmailAction;
use MailPoet\DynamicSegments\Filters\Filter;
use MailPoet\DynamicSegments\Filters\UserRole;
use MailPoet\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\DynamicSegments\Filters\WooCommerceProduct;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\DynamicSegmentFilter;

class DBMapper {

  /**
 * @param DynamicSegment $segmentData
 * @param DynamicSegmentFilter[] $filtersData
 *
 * @return DynamicSegment
 */
  public function mapSegment(DynamicSegment $segmentData, array $filtersData) {
    $filters = $this->getFilters($segmentData->id, $filtersData);
    $segmentData->setFilters($filters);
    return $segmentData;
  }

  /**
   * @param DynamicSegment[] $segmentsData
   * @param DynamicSegmentFilter[] $filtersData
   *
   * @return DynamicSegment[]
   */
  public function mapSegments(array $segmentsData, array $filtersData) {
    $result = [];
    foreach ($segmentsData as $segmentData) {
      $result[] = $this->mapSegment($segmentData, $filtersData);
    }
    return $result;
  }

  private function getFilters($segmentId, $allFilters) {
    $result = [];
    foreach ($allFilters as $filter) {
      if ($filter->segmentId === $segmentId) {
        $result[] = $this->createFilter($filter->filterData);
      }
    }
    return $result;
  }

  /**
   * @param array $data
   * @return Filter
   * @throws InvalidSegmentTypeException
   */
  private function createFilter(array $data) {
    switch ($this->getSegmentType($data)) {
      case 'userRole':
        if (!$data['wordpressRole']) throw new InvalidSegmentTypeException('Missing role');
        return new UserRole($data['wordpressRole'], 'and');
      case 'email':
        return new EmailAction($data['action'], $data['newsletter_id'], $data['link_id']);
      case 'woocommerce':
        if ($data['action'] === WooCommerceProduct::ACTION_PRODUCT) {
          return new WooCommerceProduct($data['product_id']);
        }
        return new WooCommerceCategory($data['category_id']);
      default:
        throw new InvalidSegmentTypeException('Invalid type');
    }
  }

  /**
   * @param array $data
   * @return string
   * @throws InvalidSegmentTypeException
   */
  private function getSegmentType(array $data) {
    if (!isset($data['segmentType'])) {
      throw new InvalidSegmentTypeException('Segment type is not set');
    }
    return $data['segmentType'];
  }

}
