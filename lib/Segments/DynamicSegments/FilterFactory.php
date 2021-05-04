<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\EmailOpensAbsoluteCountAction;
use MailPoet\Segments\DynamicSegments\Filters\Filter;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberSubscribedDate;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
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

  public function __construct(
    EmailAction $emailAction,
    UserRole $userRole,
    WooCommerceProduct $wooCommerceProduct,
    WooCommerceCategory $wooCommerceCategory,
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
    $this->wooCommerceNumberOfOrders = $wooCommerceNumberOfOrders;
    $this->wooCommerceSubscription = $wooCommerceSubscription;
    $this->emailOpensAbsoluteCount = $emailOpensAbsoluteCount;
    $this->wooCommerceTotalSpent = $wooCommerceTotalSpent;
    $this->subscriberSubscribedDate = $subscriberSubscribedDate;
  }

  public function getFilterForFilterEntity(DynamicSegmentFilterEntity $filter): Filter {
    $filterData = $filter->getFilterData();
    $filterType = $filterData->getFilterType();
    $action = $filterData->getParam('action');
    switch ($filterType) {
      case DynamicSegmentFilterData::TYPE_USER_ROLE:
        if ($action === SubscriberSubscribedDate::TYPE) {
          return $this->subscriberSubscribedDate;
        }
        return $this->userRole;
      case DynamicSegmentFilterData::TYPE_EMAIL:
        if ($action === EmailOpensAbsoluteCountAction::TYPE) {
          return $this->emailOpensAbsoluteCount;
        }
        return $this->emailAction;
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION:
        return $this->wooCommerceSubscription;
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE:
        if ($action === WooCommerceProduct::ACTION_PRODUCT) {
          return $this->wooCommerceProduct;
        } elseif ($action === WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS) {
          return $this->wooCommerceNumberOfOrders;
        } elseif ($action === WooCommerceTotalSpent::ACTION_TOTAL_SPENT) {
          return $this->wooCommerceTotalSpent;
        }
        return $this->wooCommerceCategory;
      default:
        throw new InvalidFilterException('Invalid type', InvalidFilterException::INVALID_TYPE);
    }
  }
}
