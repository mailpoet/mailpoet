<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\EmailOpensAbsoluteCountAction;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceNumberOfOrders;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceProduct;
use MailPoet\Segments\SegmentDependencyValidator;
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

  /** @var WooCommerceNumberOfOrders */
  private $wooCommerceNumberOfOrders;

  /** @var EntityManager */
  private $entityManager;

  /** @var SegmentDependencyValidator */
  private $segmentDependencyValidator;

  /** @var EmailOpensAbsoluteCountAction */
  private $emailOpensAbsoluteCount;

  public function __construct(
    EntityManager $entityManager,
    EmailAction $emailAction,
    UserRole $userRole,
    WooCommerceProduct $wooCommerceProduct,
    WooCommerceCategory $wooCommerceCategory,
    EmailOpensAbsoluteCountAction $emailOpensAbsoluteCount,
    SegmentDependencyValidator $segmentDependencyValidator,
    WooCommerceNumberOfOrders $wooCommerceNumberOfOrders
  ) {
    $this->emailAction = $emailAction;
    $this->userRole = $userRole;
    $this->wooCommerceProduct = $wooCommerceProduct;
    $this->wooCommerceCategory = $wooCommerceCategory;
    $this->wooCommerceNumberOfOrders = $wooCommerceNumberOfOrders;
    $this->entityManager = $entityManager;
    $this->segmentDependencyValidator = $segmentDependencyValidator;
    $this->emailOpensAbsoluteCount = $emailOpensAbsoluteCount;
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
      // When a required plugin is missing we want to return empty result
      if ($this->segmentDependencyValidator->getMissingPluginByFilter($filter)) {
        $subscribersIdsQuery->andWhere('1 = 0');
      } else {
        $this->applyFilter($subscribersIdsQuery, $filter);
      }
      $filterSelects[] = $subscribersIdsQuery->getSQL();
      $queryBuilder->setParameters(array_merge(
        $subscribersIdsQuery->getParameters(),
        $queryBuilder->getParameters()
      ));
    }
    $this->joinSubqueries($queryBuilder, $segment, $filterSelects);
    return $queryBuilder;
  }

  private function joinSubqueries(QueryBuilder $queryBuilder, SegmentEntity $segment, array $subQueries): QueryBuilder {
    $filter = $segment->getDynamicFilters()->first();
    if (!$filter) return $queryBuilder;
    $filterData = $filter->getFilterData();
    $data = $filterData->getData();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    if (!isset($data['connect']) || $data['connect'] === 'or') {
      // the final query: SELECT * FROM subscribers INNER JOIN (filter_select1 UNION filter_select2) filtered_subscribers ON filtered_subscribers.inner_subscriber_id = id
      $queryBuilder->innerJoin(
        $subscribersTable,
        sprintf('(%s)', join(' UNION ', $subQueries)),
        'filtered_subscribers',
        "filtered_subscribers.inner_subscriber_id = $subscribersTable.id"
      );
      return $queryBuilder;
    }

    foreach ($subQueries as $key => $subQuery) {
      // we need a unique name for each subquery so that we can join them together in the sql query - just make sure the identifier starts with a letter, not a number
      $subqueryName = 'a' . $key;
      $queryBuilder->innerJoin(
        $subscribersTable,
        "($subQuery)",
        $subqueryName,
        "$subqueryName.inner_subscriber_id = $subscribersTable.id");
    }
    return $queryBuilder;
  }

  private function applyFilter(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    switch ($filterData->getFilterType()) {
      case DynamicSegmentFilterData::TYPE_USER_ROLE:
        return $this->userRole->apply($queryBuilder, $filter);
      case DynamicSegmentFilterData::TYPE_EMAIL:
        $action = $filterData->getParam('action');
        if ($action === EmailOpensAbsoluteCountAction::TYPE) {
          return $this->emailOpensAbsoluteCount->apply($queryBuilder, $filter);
        }
        return $this->emailAction->apply($queryBuilder, $filter);
      case DynamicSegmentFilterData::TYPE_WOOCOMMERCE:
        $action = $filterData->getParam('action');
        if ($action === WooCommerceProduct::ACTION_PRODUCT) {
          return $this->wooCommerceProduct->apply($queryBuilder, $filter);
        } elseif ($action === WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS) {
          return $this->wooCommerceNumberOfOrders->apply($queryBuilder, $filter);
        }
        return $this->wooCommerceCategory->apply($queryBuilder, $filter);
      default:
        throw new InvalidFilterException('Invalid type', InvalidFilterException::INVALID_TYPE);
    }
  }
}
