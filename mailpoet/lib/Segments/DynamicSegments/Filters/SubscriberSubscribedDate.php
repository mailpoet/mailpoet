<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Util\Security;
use MailPoetVendor\Carbon\CarbonImmutable;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class SubscriberSubscribedDate extends DateFilter {
  const TYPE = 'subscribedDate';

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $value = $filterData->getParam('value');
    $operator = $filterData->getParam('operator');
    $parameterSuffix = $filter->getId() ?: Security::generateRandomString();
    $parameter = 'date' . $parameterSuffix;
    $date = $this->getDate($operator, $value);

    if ($operator === self::BEFORE) {
      $queryBuilder->andWhere("last_subscribed_at < :$parameter");
    } elseif ($operator === self::AFTER) {
      $queryBuilder->andWhere("last_subscribed_at >= :$parameter");
    } elseif ($operator === self::ON) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) = :$parameter");
      $date = $date->toDateString();
    } elseif ($operator === self::NOT_ON) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) != :$parameter");
      $date = $date->toDateString();
    } elseif ($operator === self::IN_THE_LAST) {
      $queryBuilder->andWhere("last_subscribed_at >= :$parameter");
    } elseif ($operator === self::NOT_IN_THE_LAST) {
      $queryBuilder->andWhere("last_subscribed_at < :$parameter");
    } else {
      throw new InvalidFilterException('Incorrect value for operator', InvalidFilterException::MISSING_VALUE);
    }
    $queryBuilder->setParameter($parameter, $date);

    return $queryBuilder;
  }

  private function getDate(string $operator, string $value): CarbonImmutable {
    $dateFields = [self::BEFORE, self::AFTER, self::ON, self::NOT_ON];

    if (in_array($operator, $dateFields)) {
      $carbon = CarbonImmutable::createFromFormat('Y-m-d', $value);
      if (!$carbon instanceof CarbonImmutable) {
        throw new InvalidFilterException('Invalid date value', InvalidFilterException::INVALID_DATE_VALUE);
      }
      if ($operator === self::BEFORE) {
        return $carbon->startOfDay();
      }
      if ($operator === self::AFTER) {
        return $carbon->endOfDay();
      }
      if ($operator === self::ON || $operator === self::NOT_ON) {
        return $carbon;
      }
    }

    $carbon = CarbonImmutable::now();
    return $carbon->subDays(intval($value) - 1)->startOfDay();
  }
}
