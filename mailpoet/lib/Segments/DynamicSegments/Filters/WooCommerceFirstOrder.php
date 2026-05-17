<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooCommerceFirstOrder implements Filter {
  const ACTION = 'firstOrder';

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
    $date2 = $operator === DateFilterHelper::BETWEEN
      ? $this->dateFilterHelper->getDateStringForOperator($operator, $this->dateFilterHelper->getSecondDateValueFromFilter($filter))
      : null;
    $subscribersTable = $this->filterHelper->getSubscribersTable();

    if (in_array($operator, [DateFilterHelper::NOT_ON, DateFilterHelper::NOT_IN_THE_LAST])) {
      $subQuery = $this->filterHelper->getNewSubscribersQueryBuilder();
      $this->applyConditionsToQueryBuilder($operator, $date, $subQuery, $date2);
      $queryBuilder->andWhere($queryBuilder->expr()->notIn("{$subscribersTable}.id", $this->filterHelper->getInterpolatedSQL($subQuery)));
    } else {
      $this->applyConditionsToQueryBuilder($operator, $date, $queryBuilder, $date2);
    }

    return $queryBuilder;
  }

  private function applyConditionsToQueryBuilder(string $operator, string $date, QueryBuilder $queryBuilder, ?string $date2): QueryBuilder {
    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder);
    $dateParam = $this->filterHelper->getUniqueParameterName('date');
    $subscribersTable = $this->filterHelper->getSubscribersTable();

    $queryBuilder->groupBy("$subscribersTable.id");

    switch ($operator) {
      case DateFilterHelper::BEFORE:
        $queryBuilder->andHaving("MIN($orderStatsAlias.date_created) < :$dateParam");
        break;
      case DateFilterHelper::AFTER:
        $queryBuilder->andHaving("MIN($orderStatsAlias.date_created) > :$dateParam");
        break;
      case DateFilterHelper::IN_THE_LAST:
      case DateFilterHelper::NOT_IN_THE_LAST:
      case DateFilterHelper::ON_OR_AFTER:
        $queryBuilder->andHaving("MIN($orderStatsAlias.date_created) >= :$dateParam");
        break;
      case DateFilterHelper::ON:
      case DateFilterHelper::NOT_ON:
        $queryBuilder->andHaving("MIN($orderStatsAlias.date_created) = :$dateParam");
        break;
      case DateFilterHelper::ON_OR_BEFORE:
        $queryBuilder->andHaving("MIN($orderStatsAlias.date_created) <= :$dateParam");
        break;
      case DateFilterHelper::BETWEEN:
        if ($date2 === null) {
          throw new InvalidFilterException('Incorrect value for date', InvalidFilterException::INVALID_DATE_VALUE);
        }
        $date2Param = $this->filterHelper->getUniqueParameterName('date');
        $queryBuilder->andHaving("MIN($orderStatsAlias.date_created) >= :$dateParam AND MIN($orderStatsAlias.date_created) < :$date2Param");
        $queryBuilder->setParameter($dateParam, $date . ' 00:00:00');
        $queryBuilder->setParameter($date2Param, $this->dateFilterHelper->getNextDayStart($date2));
        return $queryBuilder;
      default:
        throw new InvalidFilterException('Incorrect value for operator', InvalidFilterException::MISSING_VALUE);
    }
    $queryBuilder->setParameter($dateParam, $date);

    return $queryBuilder;
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    return [];
  }
}
