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
use MailPoet\Segments\DynamicSegments\Filters\SubscriberSubscribedViaForm;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberTag;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCountry;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceMembership;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceNumberOfOrders;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct;
use MailPoet\Segments\DynamicSegments\Filters\WooCommercePurchaseDate;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceSingleOrderValue;
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

  /** @var WooCommercePurchaseDate */
  private $wooCommercePurchaseDate;

  /** @var WooCommerceSingleOrderValue */
  private $wooCommerceSingleOrderValue;

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

  /** @var SubscriberTag */
  private $subscriberTag;

  /** @var EmailActionClickAny */
  private $emailActionClickAny;

  /** @var SubscriberSubscribedViaForm */
  private $subscribedViaForm;

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
    WooCommercePurchaseDate $wooCommercePurchaseDate,
    WooCommerceSubscription $wooCommerceSubscription,
    SubscriberSubscribedDate $subscriberSubscribedDate,
    SubscriberScore $subscriberScore,
    SubscriberTag $subscriberTag,
    SubscriberSegment $subscriberSegment,
    SubscriberSubscribedViaForm $subscribedViaForm,
    WooCommerceSingleOrderValue $wooCommerceSingleOrderValue
  ) {
    $this->emailAction = $emailAction;
    $this->userRole = $userRole;
    $this->wooCommerceProduct = $wooCommerceProduct;
    $this->wooCommerceCategory = $wooCommerceCategory;
    $this->wooCommerceCountry = $wooCommerceCountry;
    $this->wooCommerceNumberOfOrders = $wooCommerceNumberOfOrders;
    $this->wooCommerceMembership = $wooCommerceMembership;
    $this->wooCommercePurchaseDate = $wooCommercePurchaseDate;
    $this->wooCommerceSubscription = $wooCommerceSubscription;
    $this->emailOpensAbsoluteCount = $emailOpensAbsoluteCount;
    $this->wooCommerceTotalSpent = $wooCommerceTotalSpent;
    $this->subscriberSubscribedDate = $subscriberSubscribedDate;
    $this->subscriberScore = $subscriberScore;
    $this->subscriberTag = $subscriberTag;
    $this->mailPoetCustomFields = $mailPoetCustomFields;
    $this->subscriberSegment = $subscriberSegment;
    $this->emailActionClickAny = $emailActionClickAny;
    $this->wooCommerceSingleOrderValue = $wooCommerceSingleOrderValue;
    $this->subscribedViaForm = $subscribedViaForm;
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
   * @return MailPoetCustomFields|SubscriberScore|SubscriberSegment|SubscriberSubscribedDate|UserRole|SubscriberTag|SubscriberSubscribedViaForm
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
    } elseif ($action === SubscriberTag::TYPE) {
      return $this->subscriberTag;
    } elseif ($action === SubscriberSubscribedViaForm::TYPE) {
      return $this->subscribedViaForm;
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

  private function wooCommerceMembership(): WooCommerceMembership {
    return $this->wooCommerceMembership;
  }

  private function wooCommerceSubscription(): WooCommerceSubscription {
    return $this->wooCommerceSubscription;
  }

  /**
   * @param ?string $action
   * @return Filter
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
    } elseif ($action === WooCommerceSingleOrderValue::ACTION_SINGLE_ORDER_VALUE) {
      return $this->wooCommerceSingleOrderValue;
    } elseif ($action === WooCommercePurchaseDate::ACTION) {
      return $this->wooCommercePurchaseDate;
    }
    return $this->wooCommerceCategory;
  }
}
