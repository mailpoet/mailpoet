<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\DBCollationChecker;
use MailPoet\Util\Security;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommerceTotalSpent implements Filter {
  const ACTION_TOTAL_SPENT = 'totalSpent';

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
    $type = $filterData->getParam('total_spent_type');
    $amount = $filterData->getParam('total_spent_amount');
    $days = $filterData->getParam('total_spent_days');

    $date = Carbon::now()->subDays($days);
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
    )->andWhere('orderStats.status NOT IN ("wc-cancelled", "wc-failed")')
      ->setParameter('date' . $parameterSuffix, $date->toDateTimeString())
      ->groupBy('inner_subscriber_id');

    if ($type === '=') {
      $queryBuilder->having('SUM(orderStats.total_sales) = :amount' . $parameterSuffix);
    } elseif ($type === '!=') {
      $queryBuilder->having('SUM(orderStats.total_sales) != :amount' . $parameterSuffix);
    } elseif ($type === '>') {
      $queryBuilder->having('SUM(orderStats.total_sales) > :amount' . $parameterSuffix);
    } elseif ($type === '<') {
      $queryBuilder->having('SUM(orderStats.total_sales) < :amount' . $parameterSuffix);
    }

    $queryBuilder->setParameter('amount' . $parameterSuffix, $amount);

    return $queryBuilder;
  }
}
