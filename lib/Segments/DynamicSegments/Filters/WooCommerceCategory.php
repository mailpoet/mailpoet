<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooCommerceCategory implements Filter {
  const ACTION_CATEGORY = 'purchasedCategory';

  /** @var int */
  private $categoryId;

  public function __construct(int $categoryId) {
    $this->categoryId = $categoryId;
  }

  public function apply(QueryBuilder $queryBuilder): QueryBuilder {
    return $queryBuilder;
  }
}
