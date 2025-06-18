<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Util\DBCollationChecker;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooFilterHelper {
  /** @var DBCollationChecker */
  private $collationChecker;

  /** @var FilterHelper */
  private $filterHelper;

  public function __construct(
    DBCollationChecker $collationChecker,
    FilterHelper $filterHelper
  ) {
    $this->collationChecker = $collationChecker;
    $this->filterHelper = $filterHelper;
  }

  public function defaultExcludedStatuses(): array {
    return ['wc-pending', 'wc-on-hold', 'wc-cancelled', 'wc-refunded', 'wc-failed'];
  }

  /**
   * @param QueryBuilder $queryBuilder
   * @param string $customerAlias
   * @return string - The alias of the joined customer lookup table
   */
  public function applyCustomerLookupJoin(QueryBuilder $queryBuilder, string $customerAlias = 'customer'): string {
    $subscribersTable = $this->filterHelper->getSubscribersTable();

    $collation = $this->collationChecker->getCollateIfNeeded(
      $subscribersTable,
      'email',
      $this->customerLookupTable(),
      'email'
    );

    $queryBuilder->innerJoin(
      $subscribersTable,
      $this->customerLookupTable(),
      $customerAlias,
      "$subscribersTable.email = $customerAlias.email $collation"
    );

    return $customerAlias;
  }

  /**
   * @param QueryBuilder $queryBuilder
   * @param string $orderStatsAlias
   * @return string - The alias of the joined order stats table
   */
  public function applyCustomerOrderJoin(QueryBuilder $queryBuilder, string $orderStatsAlias = 'orderStats'): string {
    $customerAlias = $this->applyCustomerLookupJoin($queryBuilder);

    $queryBuilder->innerJoin(
      $customerAlias,
      $this->orderStatsTable(),
      $orderStatsAlias,
      "$customerAlias.customer_id = $orderStatsAlias.customer_id"
    );

    return $orderStatsAlias;
  }

  /**
   * @param QueryBuilder $queryBuilder
   * @param array|null $excludedStatuses
   * @return string - The alias of the joined order stats table
   */
  public function applyOrderStatusFilter(QueryBuilder $queryBuilder, ?array $excludedStatuses = null): string {
    if (is_null($excludedStatuses)) {
      $excludedStatuses = $this->defaultExcludedStatuses();
    }

    $orderStatsAlias = $this->applyCustomerOrderJoin($queryBuilder);

    if (!empty($excludedStatuses)) {
      $statusParam = $this->filterHelper->getUniqueParameterName('status');
      $queryBuilder->andWhere("$orderStatsAlias.status NOT IN (:$statusParam)");
      $queryBuilder->setParameter($statusParam, $excludedStatuses, ArrayParameterType::STRING);
    }

    return $orderStatsAlias;
  }

  private function customerLookupTable(): string {
    return $this->filterHelper->getPrefixedTable('wc_customer_lookup');
  }

  private function orderStatsTable(): string {
    return $this->filterHelper->getPrefixedTable('wc_order_stats');
  }
}
