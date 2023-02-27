<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class SubscriberSubscribedDate extends DateFilter {
  const TYPE = 'subscribedDate';

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $operator = $this->getOperatorFromFilter($filter);
    $value = $this->getDateValueFromFilter($filter);
    $parameterSuffix = $filter->getId() ?: Security::generateRandomString();
    $parameter = 'date' . $parameterSuffix;
    $date = $this->getDateForOperator($operator, $value);

    if ($operator === self::BEFORE) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) < :$parameter");
    } elseif ($operator === self::AFTER) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) > :$parameter");
    } elseif ($operator === self::ON) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) = :$parameter");
    } elseif ($operator === self::NOT_ON) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) != :$parameter");
    } elseif ($operator === self::IN_THE_LAST) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) >= :$parameter");
    } elseif ($operator === self::NOT_IN_THE_LAST) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) < :$parameter");
    } else {
      throw new InvalidFilterException('Incorrect value for operator', InvalidFilterException::MISSING_VALUE);
    }
    $queryBuilder->setParameter($parameter, $date);

    return $queryBuilder;
  }
}
