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

    switch ($operator) {
      case DateFilterHelper::BEFORE:
      case DateFilterHelper::NOT_IN_THE_LAST:
        $queryBuilder->andWhere("DATE(last_subscribed_at) < :$parameter");
        break;
      case DateFilterHelper::AFTER:
        $queryBuilder->andWhere("DATE(last_subscribed_at) > :$parameter");
        break;
      case DateFilterHelper::ON:
        $queryBuilder->andWhere("DATE(last_subscribed_at) = :$parameter");
        break;
      case DateFilterHelper::ON_OR_BEFORE:
        $queryBuilder->andWhere("DATE(last_subscribed_at) <= :$parameter");
        break;
      case DateFilterHelper::NOT_ON:
        $queryBuilder->andWhere("DATE(last_subscribed_at) != :$parameter");
        break;
      case DateFilterHelper::IN_THE_LAST:
      case DateFilterHelper::ON_OR_AFTER:
        $queryBuilder->andWhere("DATE(last_subscribed_at) >= :$parameter");
        break;
      default:
        throw new InvalidFilterException('Incorrect value for operator', InvalidFilterException::MISSING_VALUE);
    }
    $queryBuilder->setParameter($parameter, $date);

    return $queryBuilder;
  }
}
