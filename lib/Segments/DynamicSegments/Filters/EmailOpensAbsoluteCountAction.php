<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\CarbonImmutable;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class EmailOpensAbsoluteCountAction implements Filter {
  const TYPE = 'opensAbsoluteCount';

  /** @var EntityManager */
  private $entityManager;

  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $days = $filterData->getParam('days');
    $operator = $filterData->getParam('operator');
    $statsTable = $this->entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $queryBuilder->addSelect("count(opens.id) as oc");
    $queryBuilder->innerJoin(
      $subscribersTable,
      $statsTable,
      'opens',
      "$subscribersTable.id = opens.subscriber_id AND opens.created_at > :newer" . $filter->getId()
    );
    $queryBuilder->setParameter('newer' . $filter->getId(), CarbonImmutable::now()->subDays($days)->startOfDay());
    $queryBuilder->groupBy("$subscribersTable.id");
    if ($operator === 'less') {
      $queryBuilder->having("oc < :opens" . $filter->getId());
    } else {
      $queryBuilder->having("oc > :opens" . $filter->getId());
    }
    $queryBuilder->setParameter('opens' . $filter->getId(), $filterData->getParam('opens'));
    return $queryBuilder;
  }
}
