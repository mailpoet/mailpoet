<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class FilterHandler {
  /** @var EmailAction */
  private $emailAction;

  /** @var UserRole */
  private $userRole;

  /** @var WooCommerceProduct */
  private $wooCommerceProduct;

  /** @var WooCommerceCategory */
  private $wooCommerceCategory;

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager,
    EmailAction $emailAction,
    UserRole $userRole,
    WooCommerceProduct $wooCommerceProduct,
    WooCommerceCategory $wooCommerceCategory
  ) {
    $this->emailAction = $emailAction;
    $this->userRole = $userRole;
    $this->wooCommerceProduct = $wooCommerceProduct;
    $this->wooCommerceCategory = $wooCommerceCategory;
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, SegmentEntity $segment): QueryBuilder {
    $filters = $segment->getDynamicFilters();
    $filterSelects = [];
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    foreach ($filters as $filter) {
      $subscribersIdsQuery = $this->entityManager
        ->getConnection()
        ->createQueryBuilder()
        ->select("DISTINCT $subscribersTable.id as inner_subscriber_id")
        ->from($subscribersTable);
      $this->applyFilter($subscribersIdsQuery, $filter->getFilterData());
      $filterSelects[] = $subscribersIdsQuery->getSQL();
      $queryBuilder->setParameters(array_merge(
        $subscribersIdsQuery->getParameters(),
        $queryBuilder->getParameters()
      ));
    }
    $queryBuilder->innerJoin($subscribersTable, sprintf('(%s)', join(' UNION ', $filterSelects)), 'filtered_subscribers', 'filtered_subscribers.inner_subscriber_id = id');
    return $queryBuilder;
  }

  private function applyFilter(QueryBuilder $queryBuilder, DynamicSegmentFilterData $filter): QueryBuilder {
    switch ($filter->getFilterType()) {
      case DynamicSegmentFilterData::TYPE_USER_ROLE:
        return $this->userRole->apply($queryBuilder, $filter);
      case DynamicSegmentFilterData::TYPE_EMAIL:
        return $this->emailAction->apply($queryBuilder, $filter);
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE:
        $action = $filter->getParam('action');
        if ($action === WooCommerceProduct::ACTION_PRODUCT) {
          return $this->wooCommerceProduct->apply($queryBuilder, $filter);
        }
        return $this->wooCommerceCategory->apply($queryBuilder, $filter);
      default:
        throw new InvalidFilterException('Invalid type', InvalidFilterException::INVALID_TYPE);
    }
  }
}
