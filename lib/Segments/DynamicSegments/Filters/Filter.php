<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

interface Filter {
  public function apply(QueryBuilder $queryBuilder): QueryBuilder;
}
