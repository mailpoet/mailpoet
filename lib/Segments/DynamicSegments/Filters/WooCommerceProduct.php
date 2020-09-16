<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooCommerceProduct implements Filter {
  const ACTION_PRODUCT = 'purchasedProduct';

  /** @var int */
  private $productId;

  public function __construct(int $productId) {
    $this->productId = $productId;
  }

  public function apply(QueryBuilder $queryBuilder): QueryBuilder {
    return $queryBuilder;
  }
}
