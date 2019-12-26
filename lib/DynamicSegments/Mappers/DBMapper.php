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
 * @param DynamicSegment $segment_data
 * @param DynamicSegmentFilter[] $filters_data
 *
 * @return DynamicSegment
 */
  public function mapSegment(DynamicSegment $segment_data, array $filters_data) {
    $filters = $this->getFilters($segment_data->id, $filters_data);
    $segment_data->setFilters($filters);
    return $segment_data;
  }

  /**
   * @param DynamicSegment[] $segments_data
   * @param DynamicSegmentFilter[] $filters_data
   *
   * @return DynamicSegment[]
   */
  public function mapSegments(array $segments_data, array $filters_data) {
    $result = [];
    foreach ($segments_data as $segment_data) {
      $result[] = $this->mapSegment($segment_data, $filters_data);
    }
    return $result;
  }

  private function getFilters($segment_id, $all_filters) {
    $result = [];
    foreach ($all_filters as $filter) {
      if ($filter->segment_id === $segment_id) {
        $result[] = $this->createFilter($filter->filter_data);
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
