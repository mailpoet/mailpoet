<?php

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class FilterHandler {
  /** @var EmailAction */
  private $emailAction;

  /** @var UserRole */
  private $userRole;

  /** @var WooCommerceProduct */
  private $wooCommerceProduct;

  /** @var WooCommerceCategory */
  private $wooCommerceCategory;

  public function __construct(
    EmailAction $emailAction,
    UserRole $userRole,
    WooCommerceProduct $wooCommerceProduct,
    WooCommerceCategory $wooCommerceCategory
  ) {
    $this->emailAction = $emailAction;
    $this->userRole = $userRole;
    $this->wooCommerceProduct = $wooCommerceProduct;
    $this->wooCommerceCategory = $wooCommerceCategory;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterData $filter): QueryBuilder {
    switch ($filter->getFilterType()) {
      case DynamicSegmentFilterData::TYPE_USER_ROLE:
        return $this->userRole->apply($queryBuilder, $filter);
      case DynamicSegmentFilterData::TYPE_EMAIL:
        return $this->emailAction->apply($queryBuilder, $filter);
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE:
        $action = $filter->getParam('action');
        if ($action === WooCommerceProduct::ACTION_PRODUCT) {
          return $this->wooCommerceProduct->apply($queryBuilder, $filter);
        }
        return $this->wooCommerceCategory->apply($queryBuilder, $filter);
      default:
        throw new InvalidFilterException('Invalid type', InvalidFilterException::INVALID_TYPE);
    }
  }
}
