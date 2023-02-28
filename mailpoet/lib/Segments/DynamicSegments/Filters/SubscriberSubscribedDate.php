<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class SubscriberSubscribedDate implements Filter {
  const TYPE = 'subscribedDate';

  /** @var DateFilterHelper */
  private $dateFilterHelper;

  public function __construct(
    DateFilterHelper $dateFilterHelper
  ) {
    $this->dateFilterHelper = $dateFilterHelper;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $operator = $this->dateFilterHelper->getOperatorFromFilter($filter);
    $value = $this->dateFilterHelper->getDateValueFromFilter($filter);
    $parameterSuffix = $filter->getId() ?: Security::generateRandomString();
    $parameter = 'date' . $parameterSuffix;
    $date = $this->dateFilterHelper->getDateStringForOperator($operator, $value);

    if ($operator === DateFilterHelper::BEFORE) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) < :$parameter");
    } elseif ($operator === DateFilterHelper::AFTER) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) > :$parameter");
    } elseif ($operator === DateFilterHelper::ON) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) = :$parameter");
    } elseif ($operator === DateFilterHelper::NOT_ON) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) != :$parameter");
    } elseif ($operator === DateFilterHelper::IN_THE_LAST) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) >= :$parameter");
    } elseif ($operator === DateFilterHelper::NOT_IN_THE_LAST) {
      $queryBuilder->andWhere("DATE(last_subscribed_at) < :$parameter");
    } else {
      throw new InvalidFilterException('Incorrect value for operator', InvalidFilterException::MISSING_VALUE);
    }
    $queryBuilder->setParameter($parameter, $date);

    return $queryBuilder;
  }
}
