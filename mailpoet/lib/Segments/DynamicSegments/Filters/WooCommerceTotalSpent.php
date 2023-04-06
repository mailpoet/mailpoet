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

  /** @var WooFilterHelper */
  private $wooFilterHelper;

  public function __construct(
    EntityManager $entityManager,
    DBCollationChecker $collationChecker,
    WooFilterHelper $wooFilterHelper
  ) {
    $this->entityManager = $entityManager;
    $this->collationChecker = $collationChecker;
    $this->wooFilterHelper = $wooFilterHelper;
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

    $orderStatsAlias = $this->wooFilterHelper->applyOrderStatusFilter($queryBuilder);
    $dateParam = "date_$parameterSuffix";

    $queryBuilder->andWhere("$orderStatsAlias.date_created >= :$dateParam")
      ->setParameter($dateParam, $date->toDateTimeString())
      ->groupBy('inner_subscriber_id');

    if ($type === '=') {
      $queryBuilder->having("SUM($orderStatsAlias.total_sales) = :amount" . $parameterSuffix);
    } elseif ($type === '!=') {
      $queryBuilder->having("SUM($orderStatsAlias.total_sales) != :amount" . $parameterSuffix);
    } elseif ($type === '>') {
      $queryBuilder->having("SUM($orderStatsAlias.total_sales) > :amount" . $parameterSuffix);
    } elseif ($type === '<') {
      $queryBuilder->having("SUM($orderStatsAlias.total_sales) < :amount" . $parameterSuffix);
    }

    $queryBuilder->setParameter('amount' . $parameterSuffix, $amount);

    return $queryBuilder;
  }
}
