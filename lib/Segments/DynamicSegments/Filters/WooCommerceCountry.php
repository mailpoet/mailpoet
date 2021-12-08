<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\DBCollationChecker;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommerceCountry implements Filter {
  const ACTION_CUSTOMER_COUNTRY = 'customerInCountry';

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
    $filterData = $filter->getFilterData();
    $countryCode = $filterData->getParam('country_code');
    if (!is_array($countryCode)) {
      $countryCode = [(string)$countryCode];
    }
    $operator = $filterData->getParam('operator');
    if (!$operator) {
      $operator = DynamicSegmentFilterData::OPERATOR_ANY;
    }
    $countryFilterParam = 'countryCode' . $filter->getId() ?? Security::generateRandomString();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $collation = $this->collationChecker->getCollateIfNeeded(
      $subscribersTable,
      'email',
      $wpdb->prefix . 'wc_customer_lookup',
      'email'
    );
    return $queryBuilder->innerJoin(
      $subscribersTable,
      $wpdb->prefix . 'wc_customer_lookup',
      'customer',
      "$subscribersTable.email = customer.email $collation"
    )->innerJoin(
      'customer',
      $wpdb->prefix . 'wc_order_stats',
      'orderStats',
      'customer.customer_id = orderStats.customer_id'
    )->where("customer.country = :$countryFilterParam")
      ->andWhere('orderStats.status NOT IN ("wc-cancelled", "wc-failed")')
      ->setParameter($countryFilterParam, $countryCode[0]);
  }
}
