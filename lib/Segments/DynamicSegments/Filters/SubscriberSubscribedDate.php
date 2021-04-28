<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoetVendor\Carbon\CarbonImmutable;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class SubscriberSubscribedDate implements Filter {
  const TYPE = 'subscribedDate';

  const BEFORE = 'before';
  const AFTER = 'after';
  const IN_THE_LAST = 'inTheLast';
  const NOT_IN_THE_LAST = 'notInTheLast';

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $value = $filterData->getParam('value');
    $operator = $filterData->getParam('operator');
    $parameter = 'date' . $filter->getId();

    if ($operator === self::BEFORE) {
      $queryBuilder->andWhere("last_subscribed_at < :$parameter");
    } elseif ($operator === self::AFTER) {
      $queryBuilder->andWhere("last_subscribed_at >= :$parameter");
    } elseif ($operator === self::IN_THE_LAST) {
      $queryBuilder->andWhere("last_subscribed_at >= :$parameter");
    } elseif ($operator === self::NOT_IN_THE_LAST) {
      $queryBuilder->andWhere("last_subscribed_at < :$parameter");
    } else {
      throw new InvalidFilterException('Incorrect value for operator', InvalidFilterException::MISSING_VALUE);
    }
    $queryBuilder->setParameter($parameter, $this->getDate($operator, $value));

    return $queryBuilder;
  }

  private function getDate(string $operator, string $value): \DateTimeInterface {
    if (($operator === self::BEFORE) || ($operator === self::AFTER)) {
      $carbon = CarbonImmutable::createFromFormat('Y-m-d', $value);
      if (!$carbon instanceof CarbonImmutable) throw new InvalidFilterException('Invalid date value', InvalidFilterException::INVALID_DATE_VALUE);
      if ($operator === self::BEFORE) return $carbon->startOfDay();
      if ($operator === self::AFTER) return $carbon->endOfDay();
    }
    $carbon = CarbonImmutable::now();
    return $carbon->subDays(intval($value) - 1)->startOfDay();
  }
}
