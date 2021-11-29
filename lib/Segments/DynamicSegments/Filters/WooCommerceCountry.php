<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

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
    $countryCode = (string)$filterData->getParam('country_code');
    $countryFilterParam = 'countryCode' . $filter->getId() ?? Security::generateRandomString();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $collation = $this->collationChecker->getCollateIfNeeded(
      $subscribersTable,
      'email',
      $wpdb->postmeta,
      'meta_value'
    );

    return $queryBuilder->innerJoin(
      $subscribersTable,
      $wpdb->postmeta,
      'postmeta',
      "postmeta.meta_key = '_billing_email' AND $subscribersTable.email = postmeta.meta_value $collation AND $subscribersTable.is_woocommerce_user = 1"
    )->innerJoin(
      'postmeta',
      $wpdb->posts,
      'posts',
      "postmeta.post_id = posts.id AND posts.post_status NOT IN ('wc-cancelled', 'wc-failed')"
    )->innerJoin(
      'postmeta',
      $wpdb->postmeta,
      'postmetaCountry',
      "postmeta.post_id = postmetaCountry.post_id AND postmetaCountry.meta_key = '_billing_country' AND postmetaCountry.meta_value = :$countryFilterParam"
    )->setParameter($countryFilterParam, $countryCode);
  }
}
