<?php

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\EmailActionClickAny;
use MailPoet\Segments\DynamicSegments\Filters\EmailOpensAbsoluteCountAction;
use MailPoet\Segments\DynamicSegments\Filters\MailPoetCustomFields;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberScore;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberSegment;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberSubscribedDate;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCountry;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceNumberOfOrders;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceSubscription;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceTotalSpent;
use MailPoet\WP\Functions as WPFunctions;

class FilterDataMapper {
  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp = null
  ) {
    if (!$wp) {
      $wp = WPFunctions::get();
    }
    $this->wp = $wp;
  }

  /**
   * @param array $data
   * @return DynamicSegmentFilterData[]
   */
  public function map(array $data = []): array {
    $filters = [];
    if (!isset($data['filters']) || count($data['filters'] ?? []) < 1) {
      throw new InvalidFilterException('Filters are missing', InvalidFilterException::MISSING_FILTER);
    }
    $processFilter = function ($filter, $data) {
      $filter['connect'] = $data['filters_connect'] ?? DynamicSegmentFilterData::CONNECT_TYPE_AND;
      return $this->createFilter($filter);
    };
    $wpFilterName = 'mailpoet_dynamic_segments_filters_map';
    if ($this->wp->hasFilter($wpFilterName)) {
      return $this->wp->applyFilters($wpFilterName, $data, $processFilter);
    }
    $filter = reset($data['filters']);
    return [$processFilter($filter, $data)];
  }

  private function createFilter(array $filterData): DynamicSegmentFilterData {
    switch ($this->getSegmentType($filterData)) {
      case DynamicSegmentFilterData::TYPE_USER_ROLE:
        return $this->createSubscriber($filterData);
      case DynamicSegmentFilterData::TYPE_EMAIL:
        return $this->createEmail($filterData);
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE:
        return $this->createWooCommerce($filterData);
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION:
        return $this->createWooCommerceSubscription($filterData);
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

  private function createSubscriber(array $data): DynamicSegmentFilterData {
    if (empty($data['action'])) {
      $data['action'] = DynamicSegmentFilterData::TYPE_USER_ROLE;
    }
    if ($data['action'] === SubscriberSubscribedDate::TYPE) {
      if (empty($data['value'])) throw new InvalidFilterException('Missing number of days', InvalidFilterException::MISSING_VALUE);
      return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, $data['action'], [
        'value' => $data['value'],
        'operator' => $data['operator'] ?? SubscriberSubscribedDate::BEFORE,
        'connect' => $data['connect'],
      ]);
    }
    if ($data['action'] === SubscriberScore::TYPE) {
      if (!isset($data['value'])) throw new InvalidFilterException('Missing engagement score value', InvalidFilterException::MISSING_VALUE);
      return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, $data['action'], [
        'value' => $data['value'],
        'operator' => $data['operator'] ?? SubscriberScore::HIGHER_THAN,
        'connect' => $data['connect'],
      ]);
    }
    if ($data['action'] === SubscriberSegment::TYPE) {
      if (empty($data['segments'])) throw new InvalidFilterException('Missing segments', InvalidFilterException::MISSING_VALUE);
      return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, $data['action'], [
        'segments' => array_map(function ($segmentId) {
          return intval($segmentId);
        }, $data['segments']),
        'operator' => $data['operator'] ?? DynamicSegmentFilterData::OPERATOR_ANY,
        'connect' => $data['connect'],
      ]);
    }
    if ($data['action'] === MailPoetCustomFields::TYPE) {
      if (empty($data['custom_field_id'])) throw new InvalidFilterException('Missing custom field id', InvalidFilterException::MISSING_VALUE);
      if (empty($data['custom_field_type'])) throw new InvalidFilterException('Missing custom field type', InvalidFilterException::MISSING_VALUE);
      if (!isset($data['value'])) throw new InvalidFilterException('Missing value', InvalidFilterException::MISSING_VALUE);
      $filterData = [
        'value' => $data['value'],
        'custom_field_id' => $data['custom_field_id'],
        'custom_field_type' => $data['custom_field_type'],
        'connect' => $data['connect'],
      ];
      if (!empty($data['date_type'])) $filterData['date_type'] = $data['date_type'];
      if (!empty($data['operator'])) $filterData['operator'] = $data['operator'];
      return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, $data['action'], $filterData);
    }
    if (empty($data['wordpressRole'])) throw new InvalidFilterException('Missing role', InvalidFilterException::MISSING_ROLE);
    return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, $data['action'], [
      'wordpressRole' => $data['wordpressRole'],
      'operator' => $data['operator'] ?? DynamicSegmentFilterData::OPERATOR_ANY,
      'connect' => $data['connect'],
    ]);
  }

  /**
   * @throws InvalidFilterException
   */
  private function createEmail(array $data): DynamicSegmentFilterData {
    if (empty($data['action'])) throw new InvalidFilterException('Missing action', InvalidFilterException::MISSING_ACTION);
    if (!in_array($data['action'], EmailAction::ALLOWED_ACTIONS)) throw new InvalidFilterException('Invalid email action', InvalidFilterException::INVALID_EMAIL_ACTION);
    if (
      ($data['action'] === EmailOpensAbsoluteCountAction::TYPE)
      || ($data['action'] === EmailOpensAbsoluteCountAction::MACHINE_TYPE)
    ) {
      return $this->createEmailOpensAbsoluteCount($data);
    }
    if ($data['action'] === EmailActionClickAny::TYPE) {
        return new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_EMAIL, $data['action'], [
          'connect' => $data['connect'],
        ]);
    }
    if (empty($data['newsletters']) || !is_array($data['newsletters'])) throw new InvalidFilterException('Missing newsletter', InvalidFilterException::MISSING_NEWSLETTER_ID);
    $filterData = [
      'newsletters' => array_map(function ($segmentId) {
        return intval($segmentId);
      }, $data['newsletters']),
      'connect' => $data['connect'],
      'operator' => $data['operator'] ?? DynamicSegmentFilterData::OPERATOR_ANY,
    ];
    $filterType = DynamicSegmentFilterData::TYPE_EMAIL;
    $action = $data['action'];
    if (isset($data['link_id'])) {
      $filterData['link_id'] = $data['link_id'];
    }
    return new DynamicSegmentFilterData($filterType, $action, $filterData);
  }

  /**
   * @throws InvalidFilterException
   */
  private function createEmailOpensAbsoluteCount(array $data): DynamicSegmentFilterData {
    if (!isset($data['opens'])) throw new InvalidFilterException('Missing number of opens', InvalidFilterException::MISSING_VALUE);
    if (empty($data['days'])) throw new InvalidFilterException('Missing number of days', InvalidFilterException::MISSING_VALUE);
    $filterData = [
      'opens' => $data['opens'],
      'days' => $data['days'],
      'operator' => $data['operator'] ?? 'more',
      'connect' => $data['connect'],
    ];
    $filterType = DynamicSegmentFilterData::TYPE_EMAIL;
    $action = $data['action'];
    return new DynamicSegmentFilterData($filterType, $action, $filterData);
  }

  /**
   * @throws InvalidFilterException
   */
  private function createWooCommerce(array $data): DynamicSegmentFilterData {
    if (empty($data['action'])) throw new InvalidFilterException('Missing action', InvalidFilterException::MISSING_ACTION);
    $filterData = [
      'connect' => $data['connect'],
    ];
    $filterType = DynamicSegmentFilterData::TYPE_WOOCOMMERCE;
    $action = $data['action'];
    if ($data['action'] === WooCommerceCategory::ACTION_CATEGORY) {
      if (!isset($data['category_ids'])) throw new InvalidFilterException('Missing category', InvalidFilterException::MISSING_CATEGORY_ID);
      if (!isset($data['operator'])) throw new InvalidFilterException('Missing operator', InvalidFilterException::MISSING_OPERATOR);
      $filterData['operator'] = $data['operator'];
      $filterData['category_ids'] = $data['category_ids'];
    } elseif ($data['action'] === WooCommerceProduct::ACTION_PRODUCT) {
      if (!isset($data['product_ids'])) throw new InvalidFilterException('Missing product', InvalidFilterException::MISSING_PRODUCT_ID);
      if (!isset($data['operator'])) throw new InvalidFilterException('Missing operator', InvalidFilterException::MISSING_OPERATOR);
      $filterData['operator'] = $data['operator'];
      $filterData['product_ids'] = $data['product_ids'];
    } elseif ($data['action'] === WooCommerceCountry::ACTION_CUSTOMER_COUNTRY) {
      if (!isset($data['country_code'])) throw new InvalidFilterException('Missing country', InvalidFilterException::MISSING_COUNTRY);
      $filterData['country_code'] = $data['country_code'];
      $filterData['operator'] = $data['operator'] ?? DynamicSegmentFilterData::OPERATOR_ANY;
    } elseif ($data['action'] === WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS) {
      if (!isset($data['number_of_orders_type'])
        || !isset($data['number_of_orders_count']) || $data['number_of_orders_count'] < 0
        || !isset($data['number_of_orders_days']) || $data['number_of_orders_days'] < 1
      ) {
        throw new InvalidFilterException('Missing required fields', InvalidFilterException::MISSING_NUMBER_OF_ORDERS_FIELDS);
      }
      $filterData['number_of_orders_type'] = $data['number_of_orders_type'];
      $filterData['number_of_orders_count'] = $data['number_of_orders_count'];
      $filterData['number_of_orders_days'] = $data['number_of_orders_days'];
    } elseif ($data['action'] === WooCommerceTotalSpent::ACTION_TOTAL_SPENT) {
      if (!isset($data['total_spent_type'])
        || !isset($data['total_spent_amount']) || $data['total_spent_amount'] < 0
        || !isset($data['total_spent_days']) || $data['total_spent_days'] < 1
      ) {
        throw new InvalidFilterException('Missing required fields', InvalidFilterException::MISSING_TOTAL_SPENT_FIELDS);
      }
      $filterData['total_spent_type'] = $data['total_spent_type'];
      $filterData['total_spent_amount'] = $data['total_spent_amount'];
      $filterData['total_spent_days'] = $data['total_spent_days'];
    } else {
      throw new InvalidFilterException("Unknown action " . $data['action'], InvalidFilterException::MISSING_ACTION);
    }
    return new DynamicSegmentFilterData($filterType, $action, $filterData);
  }

  /**
   * @throws InvalidFilterException
   */
  private function createWooCommerceSubscription(array $data): DynamicSegmentFilterData {
    if (empty($data['action'])) throw new InvalidFilterException('Missing action', InvalidFilterException::MISSING_ACTION);
    $filterData = [
      'connect' => $data['connect'],
    ];
    $filterType = DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION;
    $action = $data['action'];
    if ($data['action'] === WooCommerceSubscription::ACTION_HAS_ACTIVE) {
      if (!isset($data['product_ids']) || !is_array($data['product_ids'])) throw new InvalidFilterException('Missing product', InvalidFilterException::MISSING_PRODUCT_ID);
      if (!isset($data['operator'])) throw new InvalidFilterException('Missing operator', InvalidFilterException::MISSING_OPERATOR);
      $filterData['operator'] = $data['operator'];
      $filterData['product_ids'] = $data['product_ids'];
    } else {
      throw new InvalidFilterException("Unknown action " . $data['action'], InvalidFilterException::MISSING_ACTION);
    }
    return new DynamicSegmentFilterData($filterType, $action, $filterData);
  }
}
