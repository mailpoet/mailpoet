<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class UserRole implements Filter {
  /** @var string */
  private $role;

  public function __construct(string $role) {
    $this->role = $role;
  }

  public function apply(QueryBuilder $queryBuilder): QueryBuilder {
    return $queryBuilder;
  }
}
