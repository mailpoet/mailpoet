<?php

namespace MailPoet\Premium\DynamicSegments\Mappers;

use MailPoet\Premium\DynamicSegments\Exceptions\InvalidSegmentTypeException;
use MailPoet\Premium\DynamicSegments\Filters\EmailAction;
use MailPoet\Premium\DynamicSegments\Filters\Filter;
use MailPoet\Premium\DynamicSegments\Filters\UserRole;
use MailPoet\Premium\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Premium\DynamicSegments\Filters\WooCommerceProduct;
use MailPoet\Premium\Models\DynamicSegment;
use MailPoet\Premium\Models\DynamicSegmentFilter;

class DBMapper {

  /**
 * @param \MailPoet\Premium\Models\DynamicSegment $segment_data
 * @param DynamicSegmentFilter[] $filters_data
 *
 * @return DynamicSegment
 */
  function mapSegment(DynamicSegment $segment_data, array $filters_data) {
    $filters = $this->getFilters($segment_data->id, $filters_data);
    $segment_data->setFilters($filters);
    return $segment_data;
  }

  /**
   * @param \MailPoet\Premium\Models\DynamicSegment[] $segments_data
   * @param DynamicSegmentFilter[] $filters_data
   *
   * @return DynamicSegment[]
   */
  function mapSegments(array $segments_data, array $filters_data) {
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
