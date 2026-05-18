<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class EmailsReceived implements Filter {
  const ACTION = 'numberReceived';

  /** @var EntityManager */
  private $entityManager;

  /** @var FilterHelper */
  private $filterHelper;

  public function __construct(
    EntityManager $entityManager,
    FilterHelper $filterHelper
  ) {
    $this->entityManager = $entityManager;
    $this->filterHelper = $filterHelper;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $emailCount = $filterData->getIntParam('emails');
    $operator = $filterData->getStringParam('operator');
    $statsTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $subscribersTable = $this->filterHelper->getSubscribersTable();
    $dateCondition = $this->filterHelper->getDatePeriodCondition($queryBuilder, 'emails.sent_at', $filterData, true);
    $joinCondition = "{$subscribersTable}.id = emails.subscriber_id";
    if ($dateCondition !== null) {
      $joinCondition .= " AND $dateCondition";
    }

    $queryBuilder->leftJoin($subscribersTable, $statsTable, 'emails', $joinCondition);

    $queryBuilder->groupBy("$subscribersTable.id");
    $emailCountParam = $this->filterHelper->getUniqueParameterName('emails');

    if ($operator === 'equals') {
      $queryBuilder->having("count(emails.id) = :$emailCountParam");
    } else if ($operator === 'not_equals') {
      $queryBuilder->having("count(emails.id) != :$emailCountParam");
    } else if ($operator === 'less') {
      $queryBuilder->having("count(emails.id) < :$emailCountParam");
    } else {
      $queryBuilder->having("count(emails.id) > :$emailCountParam");
    }
    $queryBuilder->setParameter($emailCountParam, $emailCount);

    return $queryBuilder;
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    return [];
  }
}
