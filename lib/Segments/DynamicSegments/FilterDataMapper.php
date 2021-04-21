<?php

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\EmailOpensAbsoluteCountAction;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceNumberOfOrders;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceSubscription;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceTotalSpent;

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
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION:
        return $this->createWooCommerceSubscription($data);
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
    if (!in_array($data['action'], EmailAction::ALLOWED_ACTIONS)) throw new InvalidFilterException('Invalid email action', InvalidFilterException::INVALID_EMAIL_ACTION);
    if ($data['action'] === EmailOpensAbsoluteCountAction::TYPE) return $this->createEmailOpensAbsoluteCount($data);
    if (empty($data['newsletter_id'])) throw new InvalidFilterException('Missing newsletter id', InvalidFilterException::MISSING_NEWSLETTER_ID);
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
  private function createEmailOpensAbsoluteCount(array $data): DynamicSegmentFilterData {
    if (empty($data['opens'])) throw new InvalidFilterException('Missing number of opens', InvalidFilterException::MISSING_VALUE);
    if (empty($data['days'])) throw new InvalidFilterException('Missing number of days', InvalidFilterException::MISSING_VALUE);
    $filterData = [
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => $data['action'],
      'opens' => $data['opens'],
      'days' => $data['days'],
      'operator' => $data['operator'] ?? 'more',
    ];
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
    } elseif ($data['action'] === WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS) {
      if (!isset($data['number_of_orders_type']) || !isset($data['number_of_orders_count']) || !isset($data['number_of_orders_days'])) {
        throw new InvalidFilterException('Missing required fields', InvalidFilterException::MISSING_NUMBER_OF_ORDERS_FIELDS);
      }
      $filterData['number_of_orders_type'] = $data['number_of_orders_type'];
      $filterData['number_of_orders_count'] = $data['number_of_orders_count'];
      $filterData['number_of_orders_days'] = $data['number_of_orders_days'];
    } elseif ($data['action'] === WooCommerceTotalSpent::ACTION_TOTAL_SPENT) {
      if (!isset($data['total_spent_type']) || !isset($data['total_spent_amount']) || !isset($data['total_spent_days'])) {
        throw new InvalidFilterException('Missing required fields', InvalidFilterException::MISSING_TOTAL_SPENT_FIELDS);
      }
      $filterData['total_spent_type'] = $data['total_spent_type'];
      $filterData['total_spent_amount'] = $data['total_spent_amount'];
      $filterData['total_spent_days'] = $data['total_spent_days'];
    } else {
      throw new InvalidFilterException("Unknown action " . $data['action'], InvalidFilterException::MISSING_ACTION);
    }
    return new DynamicSegmentFilterData($filterData);
  }

  /**
   * @throws InvalidFilterException
   */
  private function createWooCommerceSubscription(array $data): DynamicSegmentFilterData {
    if (empty($data['action'])) throw new InvalidFilterException('Missing action', InvalidFilterException::MISSING_ACTION);
    $filterData = [
      'segmentType' => DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION,
      'action' => $data['action'],
    ];
    if ($data['action'] === WooCommerceSubscription::ACTION_HAS_ACTIVE) {
      if (!isset($data['product_id'])) throw new InvalidFilterException('Missing product', InvalidFilterException::MISSING_PRODUCT_ID);
      $filterData['product_id'] = $data['product_id'];
    } else {
      throw new InvalidFilterException("Unknown action " . $data['action'], InvalidFilterException::MISSING_ACTION);
    }
    return new DynamicSegmentFilterData($filterData);
  }
}
