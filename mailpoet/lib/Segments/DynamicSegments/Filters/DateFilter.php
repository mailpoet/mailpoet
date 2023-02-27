<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoetVendor\Carbon\CarbonImmutable;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

abstract class DateFilter implements Filter {

  const BEFORE = 'before';
  const AFTER = 'after';
  const ON = 'on';
  const NOT_ON = 'notOn';
  const IN_THE_LAST = 'inTheLast';
  const NOT_IN_THE_LAST = 'notInTheLast';

  abstract public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder;

  protected function getValidOperators(): array {
    return [
      self::BEFORE,
      self::AFTER,
      self::ON,
      self::NOT_ON,
      self::IN_THE_LAST,
      self::NOT_IN_THE_LAST,
    ];
  }

  protected function getDateOperators() {
    return [
      self::BEFORE,
      self::AFTER,
      self::ON,
      self::NOT_ON,
    ];
  }

  protected function getRelativeDateOperators() {
    return [
      self::IN_THE_LAST,
      self::NOT_IN_THE_LAST,
    ];
  }

  protected function getDateForOperator(string $operator, string $value): string {
    if (in_array($operator, $this->getDateOperators())) {
      $carbon = CarbonImmutable::createFromFormat('Y-m-d', $value);
      if (!$carbon instanceof CarbonImmutable) {
        throw new InvalidFilterException('Invalid date value', InvalidFilterException::INVALID_DATE_VALUE);
      }
    } else if (in_array($operator, $this->getRelativeDateOperators())) {
      $carbon = CarbonImmutable::now()->subDays(intval($value));
    } else {
      throw new InvalidFilterException('Incorrect value for operator', InvalidFilterException::MISSING_VALUE);
    }

    return $carbon->toDateString();
  }
}
