<?php

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct;

class FilterDataMapper {
  public function map(array $data = []): DynamicSegmentFilterData {
    switch ($this->getSegmentType($data)) {
      case DynamicSegmentFilterData::TYPE_USER_ROLE:
        if (empty($data['wordpressRole'])) throw new InvalidFilterException('Missing role', InvalidFilterException::MISSING_ROLE);
        return new DynamicSegmentFilterData([
          'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
          'wordpressRole' => $data['wordpressRole'],
        ]);
      case DynamicSegmentFilterData::TYPE_EMAIL:
        return $this->createEmail($data);
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE:
        return $this->createWooCommerce($data);
      default:
        throw new InvalidFilterException('Invalid type', InvalidFilterException::INVALID_TYPE);
    }
  }

  /**
   * @throws InvalidFilterException
   */
  private function getSegmentType(array $data): string {
    if (!isset($data['segmentType'])) {
      throw new InvalidFilterException('Segment type is not set', InvalidFilterException::MISSING_TYPE);
    }
    return $data['segmentType'];
  }

  /**
   * @throws InvalidFilterException
   */
  private function createEmail(array $data): DynamicSegmentFilterData {
    if (empty($data['action'])) throw new InvalidFilterException('Missing action', InvalidFilterException::MISSING_ACTION);
    if (empty($data['newsletter_id'])) throw new InvalidFilterException('Missing newsletter id', InvalidFilterException::MISSING_NEWSLETTER_ID);
    if (!in_array($data['action'], EmailAction::ALLOWED_ACTIONS)) throw new InvalidFilterException('Invalid email action', InvalidFilterException::INVALID_EMAIL_ACTION);
    $filterData = [
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => $data['action'],
      'newsletter_id' => $data['newsletter_id'],
    ];
    if (isset($data['link_id'])) {
      $filterData['link_id'] = $data['link_id'];
    }
    return new DynamicSegmentFilterData($filterData);
  }

  /**
   * @throws InvalidFilterException
   */
  private function createWooCommerce(array $data): DynamicSegmentFilterData {
    if (empty($data['action'])) throw new InvalidFilterException('Missing action', InvalidFilterException::MISSING_ACTION);
    $filterData = [
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      'action' => $data['action'],
    ];
    if ($data['action'] === WooCommerceCategory::ACTION_CATEGORY) {
      if (!isset($data['category_id'])) throw new InvalidFilterException('Missing category', InvalidFilterException::MISSING_CATEGORY_ID);
      $filterData['category_id'] = $data['category_id'];
    } elseif ($data['action'] === WooCommerceProduct::ACTION_PRODUCT) {
      if (!isset($data['product_id'])) throw new InvalidFilterException('Missing product', InvalidFilterException::MISSING_PRODUCT_ID);
      $filterData['product_id'] = $data['product_id'];
    } else {
      throw new InvalidFilterException("Unknown action " . $data['action'], InvalidFilterException::MISSING_ACTION);
    }
    return new DynamicSegmentFilterData($filterData);
  }
}
