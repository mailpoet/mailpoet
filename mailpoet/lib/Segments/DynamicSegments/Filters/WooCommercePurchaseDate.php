<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Util\DBCollationChecker;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommercePurchaseDate extends DateFilter {
  const ACTION = 'purchaseDate';

  /** @var EntityManager */
  private $entityManager;

  /** @var DBCollationChecker */
  private $collationChecker;

  public function __construct(
    EntityManager $entityManager,
    DBCollationChecker $collationChecker
  ) {
    $this->entityManager = $entityManager;
    $this->collationChecker = $collationChecker;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $operator = $filterData->getParam('operator');

    if (!is_string($operator) || !in_array($operator, $this->getValidOperators())) {
      throw new InvalidFilterException('Incorrect value for operator', InvalidFilterException::MISSING_VALUE);
    }

    $dateValue = $filterData->getParam('value');
    if (!is_string($dateValue)) {
      throw new InvalidFilterException('Incorrect value for date', InvalidFilterException::INVALID_DATE_VALUE);
    }
    $date = $this->getDateForOperator($operator, $dateValue);
    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();
    $dateParameter = sprintf('date_%s', $parameterSuffix);

    $subQuery = $this->getSubQuery($operator, $dateParameter);

    $isNegatedOperator = in_array($operator, [self::NOT_ON, self::NOT_IN_THE_LAST]);
    $subQueryOperator = $isNegatedOperator ? 'NOT IN' : 'IN';

    $queryBuilder->andWhere("{$this->getSubscribersTable()}.id {$subQueryOperator} ({$subQuery->getSQL()})");
    $queryBuilder->setParameter($dateParameter, $date);

    return $queryBuilder;
  }

  private function getSubQuery(string $operator, string $dateParameter): QueryBuilder {
    $queryBuilder = $this->getNewSubscribersQueryBuilder();
    $this->applyCustomerLookupJoin($queryBuilder);
    $this->applyCustomerOrderJoin($queryBuilder);
    $this->applyOrderStatusFilter($queryBuilder, ['wc-processing', 'wc-completed']);

    switch ($operator) {
      case self::BEFORE:
        $queryBuilder->andWhere("DATE(orderStats.date_created) < :$dateParameter");
        break;
      case self::AFTER:
        $queryBuilder->andWhere("DATE(orderStats.date_created) > :$dateParameter");
        break;
      case self::IN_THE_LAST:
      case self::NOT_IN_THE_LAST:
        $queryBuilder->andWhere("DATE(orderStats.date_created) >= :$dateParameter");
        break;
      case self::ON:
      case self::NOT_ON:
        $queryBuilder->andWhere("DATE(orderStats.date_created) = :$dateParameter");
        break;
      default:
        throw new InvalidFilterException('Incorrect value for operator', InvalidFilterException::MISSING_VALUE);
    }

    return $queryBuilder;
  }

  private function applyOrderStatusFilter(QueryBuilder $queryBuilder, array $allowedStatuses, string $orderStatsAlias = 'orderStats') {
    $quotedStatus = array_map([$queryBuilder->expr(), 'literal'], $allowedStatuses);
    $queryBuilder->andWhere($queryBuilder->expr()->in("$orderStatsAlias.status", $quotedStatus));
  }

  private function applyCustomerOrderJoin(QueryBuilder $queryBuilder, $fromAlias = 'customer', $toAlias = 'orderStats'): QueryBuilder {
    global $wpdb;
    $queryBuilder->innerJoin(
      $fromAlias,
      $wpdb->prefix . 'wc_order_stats',
      $toAlias,
      "$fromAlias.customer_id = $toAlias.customer_id");

    return $queryBuilder;
  }

  private function applyCustomerLookupJoin(QueryBuilder $queryBuilder, string $alias = 'customer'): QueryBuilder {
    global $wpdb;
    $subscribersTable = $this->getSubscribersTable();

    $collation = $this->collationChecker->getCollateIfNeeded(
      $subscribersTable,
      'email',
      $wpdb->prefix . 'wc_customer_lookup',
      'email'
    );

    $queryBuilder->innerJoin(
      $subscribersTable,
      $wpdb->prefix . 'wc_customer_lookup',
      $alias,
      "$subscribersTable.email = $alias.email $collation"
    );

    return $queryBuilder;
  }

  private function getNewSubscribersQueryBuilder(): QueryBuilder {
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select('id')
      ->from($this->getSubscribersTable());
  }

  private function getSubscribersTable(): string {
    return $this->entityManager
      ->getClassMetadata(SubscriberEntity::class)
      ->getTableName();
  }
}
