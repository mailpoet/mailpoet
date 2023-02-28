<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Util\DBCollationChecker;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommercePurchaseDate implements Filter {
  const ACTION = 'purchaseDate';

  /** @var EntityManager */
  private $entityManager;

  /** @var DBCollationChecker */
  private $collationChecker;

  /** @var DateFilterHelper */
  private $dateFilterHelper;

  public function __construct(
    EntityManager $entityManager,
    DBCollationChecker $collationChecker,
    DateFilterHelper $dateFilterHelper
  ) {
    $this->entityManager = $entityManager;
    $this->collationChecker = $collationChecker;
    $this->dateFilterHelper = $dateFilterHelper;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $operator = $this->dateFilterHelper->getOperatorFromFilter($filter);
    $dateValue = $this->dateFilterHelper->getDateValueFromFilter($filter);
    $date = $this->dateFilterHelper->getDateStringForOperator($operator, $dateValue);
    $subQuery = $this->getSubQuery($operator, $date);

    if (in_array($operator, [DateFilterHelper::NOT_ON, DateFilterHelper::NOT_IN_THE_LAST])) {
      $queryBuilder->andWhere($queryBuilder->expr()->notIn("{$this->getSubscribersTable()}.id", $subQuery->getSQL()));
    } else {
      $queryBuilder->andWhere($queryBuilder->expr()->in("{$this->getSubscribersTable()}.id", $subQuery->getSQL()));
    }

    return $queryBuilder;
  }

  private function getSubQuery(string $operator, string $date): QueryBuilder {
    $queryBuilder = $this->getNewSubscribersQueryBuilder();
    $this->applyCustomerLookupJoin($queryBuilder);
    $this->applyCustomerOrderJoin($queryBuilder);
    $this->applyOrderStatusFilter($queryBuilder, ['wc-processing', 'wc-completed']);
    $quotedDate = $queryBuilder->expr()->literal($date);

    switch ($operator) {
      case DateFilterHelper::BEFORE:
        $queryBuilder->andWhere("DATE(orderStats.date_created) < $quotedDate");
        break;
      case DateFilterHelper::AFTER:
        $queryBuilder->andWhere("DATE(orderStats.date_created) > $quotedDate");
        break;
      case DateFilterHelper::IN_THE_LAST:
      case DateFilterHelper::NOT_IN_THE_LAST:
        $queryBuilder->andWhere("DATE(orderStats.date_created) >= $quotedDate");
        break;
      case DateFilterHelper::ON:
      case DateFilterHelper::NOT_ON:
        $queryBuilder->andWhere("DATE(orderStats.date_created) = $quotedDate");
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
