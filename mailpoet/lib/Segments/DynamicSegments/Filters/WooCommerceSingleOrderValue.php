<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\DBCollationChecker;
use MailPoet\Util\Security;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommerceSingleOrderValue implements Filter {
  const ACTION_SINGLE_ORDER_VALUE = 'singleOrderValue';

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
    global $wpdb;
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $filterData = $filter->getFilterData();
    $type = $filterData->getParam('single_order_value_type');
    $amount = $filterData->getParam('single_order_value_amount');
    $days = $filterData->getParam('single_order_value_days');

    if (!is_string($days)) {
      $days = '1'; // Default to last day
    }

    $date = Carbon::now()->subDays((int)$days);
    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();
    $collation = $this->collationChecker->getCollateIfNeeded(
      $subscribersTable,
      'email',
      $wpdb->prefix . 'wc_customer_lookup',
      'email'
    );

    $queryBuilder->innerJoin(
      $subscribersTable,
      $wpdb->prefix . 'wc_customer_lookup',
      'customer',
      "$subscribersTable.email = customer.email $collation"
    )->leftJoin(
      'customer',
      $wpdb->prefix . 'wc_order_stats',
      'orderStats',
      'customer.customer_id = orderStats.customer_id AND orderStats.date_created >= :date' . $parameterSuffix
    )->andWhere(
      'orderStats.status NOT IN ("wc-cancelled", "wc-failed")'
    )->setParameter(
      'date' . $parameterSuffix, $date->toDateTimeString()
    );

    if ($type === '=') {
      $queryBuilder->andWhere('orderStats.total_sales = :amount' . $parameterSuffix);
    } elseif ($type === '!=') {
      $queryBuilder->andWhere('orderStats.total_sales != :amount' . $parameterSuffix);
    } elseif ($type === '>') {
      $queryBuilder->andWhere('orderStats.total_sales > :amount' . $parameterSuffix);
    } elseif ($type === '>=') {
      $queryBuilder->andWhere('orderStats.total_sales >= :amount' . $parameterSuffix);
    } elseif ($type === '<') {
      $queryBuilder->andWhere('orderStats.total_sales < :amount' . $parameterSuffix);
    } elseif ($type === '<=') {
      $queryBuilder->andWhere('orderStats.total_sales <= :amount' . $parameterSuffix);
    }

    $queryBuilder->setParameter('amount' . $parameterSuffix, $amount);

    return $queryBuilder;
  }
}
