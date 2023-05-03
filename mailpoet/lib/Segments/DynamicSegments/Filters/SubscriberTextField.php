<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Util\Helpers;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class SubscriberTextField implements Filter {
  const IS = 'is';
  const IS_NOT = 'isNot';
  const CONTAINS = 'contains';
  const NOT_CONTAINS = 'notContains';
  const STARTS_WITH = 'startsWith';
  const NOT_STARTS_WITH = 'notStartsWith';
  const ENDS_WITH = 'endsWith';
  const NOT_ENDS_WITH = 'notEndsWith';

  const FIRST_NAME = 'subscriberFirstName';
  const LAST_NAME = 'subscriberLastName';
  const EMAIL = 'subscriberEmail';

  const TYPES = [self::FIRST_NAME, self::LAST_NAME, self::EMAIL];

  const OPERATORS = [
    self::IS,
    self::IS_NOT,
    self::CONTAINS,
    self::NOT_CONTAINS,
    self::STARTS_WITH,
    self::NOT_STARTS_WITH,
    self::ENDS_WITH,
    self::NOT_ENDS_WITH,
  ];

  /** @var FilterHelper */
  private $filterHelper;

  public function __construct(
    FilterHelper $filterHelper
  ) {
    $this->filterHelper = $filterHelper;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $action = $filterData->getParam('action');
    $value = $filterData->getParam('value');
    $operator = $filterData->getParam('operator');

    if (!is_string($action)) {
      throw new InvalidFilterException('Missing action', InvalidFilterException::MISSING_VALUE);
    }

    if (!is_string($value)) {
      throw new InvalidFilterException('Missing value', InvalidFilterException::MISSING_VALUE);
    }

    if (!is_string($operator)) {
      throw new InvalidFilterException('Missing operator', InvalidFilterException::MISSING_VALUE);
    }

    $columnName = $this->getColumnNameForAction($action);
    $parameter = $this->filterHelper->getUniqueParameterName('subscriberText');

    switch ($operator) {
      case self::IS:
        $queryBuilder->andWhere("$columnName = :$parameter");
        break;
      case self::IS_NOT:
        $queryBuilder->andWhere("$columnName != :$parameter");
        break;
      case self::CONTAINS:
        $queryBuilder->andWhere($queryBuilder->expr()->like($columnName, ":$parameter"));
        $value = '%' . Helpers::escapeSearch($value) . '%';
        break;
      case self::NOT_CONTAINS:
        $queryBuilder->andWhere($queryBuilder->expr()->notLike($columnName, ":$parameter"));
        $value = '%' . Helpers::escapeSearch($value) . '%';
        break;
      case self::STARTS_WITH:
        $queryBuilder->andWhere($queryBuilder->expr()->like($columnName, ":$parameter"));
        $value = Helpers::escapeSearch($value) . '%';
        break;
      case self::NOT_STARTS_WITH:
        $queryBuilder->andWhere($queryBuilder->expr()->notLike($columnName, ":$parameter"));
        $value = Helpers::escapeSearch($value) . '%';
        break;
      case self::ENDS_WITH:
        $queryBuilder->andWhere($queryBuilder->expr()->like($columnName, ":$parameter"));
        $value = '%' . Helpers::escapeSearch($value);
        break;
      case self::NOT_ENDS_WITH:
        $queryBuilder->andWhere($queryBuilder->expr()->notLike($columnName, ":$parameter"));
        $value = '%' . Helpers::escapeSearch($value);
        break;
      default:
        throw new InvalidFilterException('Invalid operator', InvalidFilterException::MISSING_OPERATOR);
    }

    $queryBuilder->setParameter($parameter, $value);

    return $queryBuilder;
  }

  private function getColumnNameForAction(string $field): string {
    switch ($field) {
      case self::FIRST_NAME:
        return 'first_name';
      case self::LAST_NAME:
        return 'last_name';
      case self::EMAIL:
        return 'email';
    }

    throw new InvalidFilterException('Invalid action');
  }
}
