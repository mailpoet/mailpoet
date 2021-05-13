<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\EmailOpensAbsoluteCountAction;
use MailPoet\Segments\DynamicSegments\Filters\Filter;
use MailPoet\Segments\DynamicSegments\Filters\MailPoetCustomFields;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberSubscribedDate;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCountry;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceNumberOfOrders;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceSubscription;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceTotalSpent;

class FilterFactory {
  /** @var EmailAction */
  private $emailAction;

  /** @var UserRole */
  private $userRole;

  /** @var WooCommerceProduct */
  private $wooCommerceProduct;

  /** @var WooCommerceCategory */
  private $wooCommerceCategory;

  /** @var WooCommerceCountry */
  private $wooCommerceCountry;

  /** @var WooCommerceNumberOfOrders */
  private $wooCommerceNumberOfOrders;

  /** @var WooCommerceTotalSpent */
  private $wooCommerceTotalSpent;

  /** @var WooCommerceSubscription */
  private $wooCommerceSubscription;

  /** @var EmailOpensAbsoluteCountAction */
  private $emailOpensAbsoluteCount;

  /** @var SubscriberSubscribedDate */
  private $subscriberSubscribedDate;

  /** @var MailPoetCustomFields */
  private $mailPoetCustomFields;

  public function __construct(
    EmailAction $emailAction,
    UserRole $userRole,
    MailPoetCustomFields $mailPoetCustomFields,
    WooCommerceProduct $wooCommerceProduct,
    WooCommerceCategory $wooCommerceCategory,
    WooCommerceCountry $wooCommerceCountry,
    EmailOpensAbsoluteCountAction $emailOpensAbsoluteCount,
    WooCommerceNumberOfOrders $wooCommerceNumberOfOrders,
    WooCommerceTotalSpent $wooCommerceTotalSpent,
    WooCommerceSubscription $wooCommerceSubscription,
    SubscriberSubscribedDate $subscriberSubscribedDate
  ) {
    $this->emailAction = $emailAction;
    $this->userRole = $userRole;
    $this->wooCommerceProduct = $wooCommerceProduct;
    $this->wooCommerceCategory = $wooCommerceCategory;
    $this->wooCommerceCountry = $wooCommerceCountry;
    $this->wooCommerceNumberOfOrders = $wooCommerceNumberOfOrders;
    $this->wooCommerceSubscription = $wooCommerceSubscription;
    $this->emailOpensAbsoluteCount = $emailOpensAbsoluteCount;
    $this->wooCommerceTotalSpent = $wooCommerceTotalSpent;
    $this->subscriberSubscribedDate = $subscriberSubscribedDate;
    $this->mailPoetCustomFields = $mailPoetCustomFields;
  }

  public function getFilterForFilterEntity(DynamicSegmentFilterEntity $filter): Filter {
    $filterData = $filter->getFilterData();
    $filterType = $filterData->getFilterType();
    $action = $filterData->getParam('action');
    switch ($filterType) {
      case DynamicSegmentFilterData::TYPE_USER_ROLE:
        return $this->userRole($action);
      case DynamicSegmentFilterData::TYPE_EMAIL:
        return $this->email($action);
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION:
        return $this->wooCommerceSubscription();
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE:
        return $this->wooCommerce($action);
      default:
        throw new InvalidFilterException('Invalid type', InvalidFilterException::INVALID_TYPE);
    }
  }

  private function userRole($action) {
    if ($action === SubscriberSubscribedDate::TYPE) {
      return $this->subscriberSubscribedDate;
    } elseif($action === MailPoetCustomFields::TYPE) {
      return $this->mailPoetCustomFields;
    }
    return $this->userRole;
  }

  private function email($action) {
    if ($action === EmailOpensAbsoluteCountAction::TYPE) {
      return $this->emailOpensAbsoluteCount;
    }
    return $this->emailAction;
  }

  private function wooCommerceSubscription() {
    return $this->wooCommerceSubscription;
  }

  private function wooCommerce($action) {
    if ($action === WooCommerceProduct::ACTION_PRODUCT) {
      return $this->wooCommerceProduct;
    } elseif ($action === WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS) {
      return $this->wooCommerceNumberOfOrders;
    } elseif ($action === WooCommerceTotalSpent::ACTION_TOTAL_SPENT) {
      return $this->wooCommerceTotalSpent;
    } elseif ($action === WooCommerceCountry::ACTION_CUSTOMER_COUNTRY) {
      return $this->wooCommerceCountry;
    }
    return $this->wooCommerceCategory;
  }
}
