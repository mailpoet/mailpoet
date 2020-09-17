<?php

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\DynamicSegments\Exceptions\InvalidSegmentTypeException;
use MailPoet\Entities\DynamicSegmentFilterEntity;
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

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filterEntity): QueryBuilder {
    switch ($filterEntity->getSegmentType()) {
      case DynamicSegmentFilterEntity::TYPE_USER_ROLE:
        return $this->userRole->apply($queryBuilder, $filterEntity);
      case DynamicSegmentFilterEntity::TYPE_EMAIL:
        return $this->emailAction->apply($queryBuilder, $filterEntity);
      case DynamicSegmentFilterEntity::TYPE_WOOCOMMERCE:
        $action = $filterEntity->getFilterDataParam('action');
        if ($action === WooCommerceProduct::ACTION_PRODUCT) {
          return $this->wooCommerceProduct->apply($queryBuilder, $filterEntity);
        }
        return $this->wooCommerceCategory->apply($queryBuilder, $filterEntity);
      default:
        throw new InvalidSegmentTypeException('Invalid type', InvalidSegmentTypeException::INVALID_TYPE);
    }
  }
}
