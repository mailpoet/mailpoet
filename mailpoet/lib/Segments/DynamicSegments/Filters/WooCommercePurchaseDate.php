<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooCommercePurchaseDate implements Filter {
  const ACTION = 'purchaseDate';

  /** @var DateFilterHelper */
  private $dateFilterHelper;

  /** @var FilterHelper */
  private $filterHelper;

  /** @var WooFilterHelper */
  private $wooFilterHelper;

  public function __construct(
    DateFilterHelper $dateFilterHelper,
    FilterHelper $filterHelper,
    WooFilterHelper $wooFilterHelper
  ) {
    $this->dateFilterHelper = $dateFilterHelper;
    $this->filterHelper = $filterHelper;
    $this->wooFilterHelper = $wooFilterHelper;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $operator = $this->dateFilterHelper->getOperatorFromFilter($filter);
    $dateValue = $this->dateFilterHelper->getDateValueFromFilter($filter);
    $date = $this->dateFilterHelper->getDateStringForOperator($operator, $dateValue);
    $subQuery = $this->getSubQuery($operator, $date);
    $subscribersTable = $this->filterHelper->getSubscribersTable();

    if (in_array($operator, [DateFilterHelper::NOT_ON, DateFilterHelper::NOT_IN_THE_LAST])) {
      $queryBuilder->andWhere($queryBuilder->expr()->notIn("{$subscribersTable}.id", $subQuery->getSQL()));
    } else {
      $queryBuilder->andWhere($queryBuilder->expr()->in("{$subscribersTable}.id", $subQuery->getSQL()));
    }

    return $queryBuilder;
  }

  private function getSubQuery(string $operator, string $date): QueryBuilder {
    $queryBuilder = $this->filterHelper->getNewSubscribersQueryBuilder();
    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder);
    $quotedDate = $queryBuilder->expr()->literal($date);

    switch ($operator) {
      case DateFilterHelper::BEFORE:
        $queryBuilder->andWhere("DATE($orderStatsAlias.date_created) < $quotedDate");
        break;
      case DateFilterHelper::AFTER:
        $queryBuilder->andWhere("DATE($orderStatsAlias.date_created) > $quotedDate");
        break;
      case DateFilterHelper::IN_THE_LAST:
      case DateFilterHelper::NOT_IN_THE_LAST:
        $queryBuilder->andWhere("DATE($orderStatsAlias.date_created) >= $quotedDate");
        break;
      case DateFilterHelper::ON:
      case DateFilterHelper::NOT_ON:
        $queryBuilder->andWhere("DATE($orderStatsAlias.date_created) = $quotedDate");
        break;
      default:
        throw new InvalidFilterException('Incorrect value for operator', InvalidFilterException::MISSING_VALUE);
    }

    return $queryBuilder;
  }
}
