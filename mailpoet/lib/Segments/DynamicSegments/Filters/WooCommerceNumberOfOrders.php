<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\DBCollationChecker;
use MailPoet\Util\Security;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommerceNumberOfOrders implements Filter {
  const ACTION_NUMBER_OF_ORDERS = 'numberOfOrders';

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
    $type = strval($filterData->getParam('number_of_orders_type'));
    $count = intval($filterData->getParam('number_of_orders_count'));
    $days = $filterData->getParam('number_of_orders_days');
    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();
    $collation = $this->collationChecker->getCollateIfNeeded(
      $subscribersTable,
      'email',
      $wpdb->prefix . 'wc_customer_lookup',
      'email'
    );

    $date = Carbon::now()->subDays($days);

    $subQuery = $this->entityManager->getConnection()
      ->createQueryBuilder()
      ->from($wpdb->prefix . 'wc_customer_lookup', "customer")
      ->select("customer.email $collation as email")
      ->addSelect("orderStats.order_id as oder_stats_id")
      ->leftJoin(
        'customer',
        $wpdb->prefix . 'wc_order_stats',
        'orderStats',
        'customer.customer_id = orderStats.customer_id AND orderStats.date_created >= :date' . $parameterSuffix . ' AND orderStats.status NOT IN ("wc-cancelled", "wc-failed")'
      );

    $queryBuilder->add('join', [
      $subscribersTable => [
        /**
         * Based the combination of $type and $count we may need to include none-customer subscribers
         * in this case we'll need to leftJoin subscribers table to result of the sub-query defined above,
         * in all other cases innerJoin gets us the expected records.
         */
        'joinType' => $this-> shouldIncludeNoneCustomerSubscribers($type, $count) ? 'left' : 'inner',
        'joinTable' => "({$subQuery->getSQL()})",
        'joinAlias' => 'selectedCustomers',
        'joinCondition' => "$subscribersTable.email = selectedCustomers.email $collation",
      ],
    ], \true)
      ->setParameter('date' . $parameterSuffix, $date->toDateTimeString())
      ->groupBy('inner_subscriber_id');

    if ($type === '=') {
      $queryBuilder->having('COUNT(oder_stats_id) = :count' . $parameterSuffix);
    } elseif ($type === '!=') {
      $queryBuilder->having('COUNT(oder_stats_id) != :count' . $parameterSuffix);
    } elseif ($type === '>') {
      $queryBuilder->having('COUNT(oder_stats_id) > :count' . $parameterSuffix);
    } elseif ($type === '<') {
      $queryBuilder->having('COUNT(oder_stats_id) < :count' . $parameterSuffix);
    }

    $queryBuilder->setParameter('count' . $parameterSuffix, $count, 'integer');

    return $queryBuilder;
  }

  private function shouldIncludeNoneCustomerSubscribers(string $type, int $count): bool {
    if ($type === '=') {
      return $count === 0;
    } elseif ($type === '!=') {
      return true;
    } elseif ($type === '>') {
      return $count < 0;
    } elseif ($type === '<') {
      return true;
    }

    return false;
  }
}
