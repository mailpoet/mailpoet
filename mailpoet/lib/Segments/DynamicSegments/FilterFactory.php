<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\EmailActionClickAny;
use MailPoet\Segments\DynamicSegments\Filters\EmailOpensAbsoluteCountAction;
use MailPoet\Segments\DynamicSegments\Filters\Filter;
use MailPoet\Segments\DynamicSegments\Filters\MailPoetCustomFields;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberScore;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberSegment;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberSubscribedDate;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCountry;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceMembership;
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

  /** @var WooCommerceMembership */
  private $wooCommerceMembership;

  /** @var WooCommerceSubscription */
  private $wooCommerceSubscription;

  /** @var EmailOpensAbsoluteCountAction */
  private $emailOpensAbsoluteCount;

  /** @var SubscriberSubscribedDate */
  private $subscriberSubscribedDate;

  /** @var SubscriberScore */
  private $subscriberScore;

  /** @var MailPoetCustomFields */
  private $mailPoetCustomFields;

  /** @var SubscriberSegment */
  private $subscriberSegment;

  /** @var EmailActionClickAny */
  private $emailActionClickAny;

  public function __construct(
    EmailAction $emailAction,
    EmailActionClickAny $emailActionClickAny,
    UserRole $userRole,
    MailPoetCustomFields $mailPoetCustomFields,
    WooCommerceProduct $wooCommerceProduct,
    WooCommerceCategory $wooCommerceCategory,
    WooCommerceCountry $wooCommerceCountry,
    EmailOpensAbsoluteCountAction $emailOpensAbsoluteCount,
    WooCommerceNumberOfOrders $wooCommerceNumberOfOrders,
    WooCommerceTotalSpent $wooCommerceTotalSpent,
    WooCommerceMembership $wooCommerceMembership,
    WooCommerceSubscription $wooCommerceSubscription,
    SubscriberSubscribedDate $subscriberSubscribedDate,
    SubscriberScore $subscriberScore,
    SubscriberSegment $subscriberSegment
  ) {
    $this->emailAction = $emailAction;
    $this->userRole = $userRole;
    $this->wooCommerceProduct = $wooCommerceProduct;
    $this->wooCommerceCategory = $wooCommerceCategory;
    $this->wooCommerceCountry = $wooCommerceCountry;
    $this->wooCommerceNumberOfOrders = $wooCommerceNumberOfOrders;
    $this->wooCommerceMembership = $wooCommerceMembership;
    $this->wooCommerceSubscription = $wooCommerceSubscription;
    $this->emailOpensAbsoluteCount = $emailOpensAbsoluteCount;
    $this->wooCommerceTotalSpent = $wooCommerceTotalSpent;
    $this->subscriberSubscribedDate = $subscriberSubscribedDate;
    $this->subscriberScore = $subscriberScore;
    $this->mailPoetCustomFields = $mailPoetCustomFields;
    $this->subscriberSegment = $subscriberSegment;
    $this->emailActionClickAny = $emailActionClickAny;
  }

  public function getFilterForFilterEntity(DynamicSegmentFilterEntity $filter): Filter {
    $filterData = $filter->getFilterData();
    $filterType = $filterData->getFilterType();
    $action = $filterData->getAction();
    switch ($filterType) {
      case DynamicSegmentFilterData::TYPE_USER_ROLE:
        return $this->userRole($action);
      case DynamicSegmentFilterData::TYPE_EMAIL:
        return $this->email($action);
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE_MEMBERSHIP:
        return $this->wooCommerceMembership();
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION:
        return $this->wooCommerceSubscription();
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE:
        return $this->wooCommerce($action);
      default:
        throw new InvalidFilterException('Invalid type', InvalidFilterException::INVALID_TYPE);
    }
  }

  /**
   * @param ?string $action
   * @return MailPoetCustomFields|SubscriberScore|SubscriberSegment|SubscriberSubscribedDate|UserRole
   */
  private function userRole(?string $action) {
    if ($action === SubscriberSubscribedDate::TYPE) {
      return $this->subscriberSubscribedDate;
    } elseif ($action === SubscriberScore::TYPE) {
      return $this->subscriberScore;
    } elseif ($action === MailPoetCustomFields::TYPE) {
      return $this->mailPoetCustomFields;
    } elseif ($action === SubscriberSegment::TYPE) {
      return $this->subscriberSegment;
    }
    return $this->userRole;
  }

  /**
   * @param ?string $action
   * @return EmailAction|EmailActionClickAny|EmailOpensAbsoluteCountAction
   */
  private function email(?string $action) {
    $countActions = [EmailOpensAbsoluteCountAction::TYPE, EmailOpensAbsoluteCountAction::MACHINE_TYPE];
    if (in_array($action, $countActions)) {
      return $this->emailOpensAbsoluteCount;
    } elseif ($action === EmailActionClickAny::TYPE) {
      return $this->emailActionClickAny;
    }
    return $this->emailAction;
  }

  /**
   * @return WooCommerceMembership
   */
  private function wooCommerceMembership() {
    return $this->wooCommerceMembership;
  }

  /**
   * @return WooCommerceSubscription
   */
  private function wooCommerceSubscription() {
    return $this->wooCommerceSubscription;
  }

  /**
   * @param ?string $action
   * @return WooCommerceCategory|WooCommerceCountry|WooCommerceNumberOfOrders|WooCommerceProduct|WooCommerceTotalSpent
   */
  private function wooCommerce(?string $action) {
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
