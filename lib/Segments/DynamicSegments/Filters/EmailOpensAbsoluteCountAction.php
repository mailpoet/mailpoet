<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\Security;
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
    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();
    $statsTable = $this->entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $queryBuilder->leftJoin(
      $subscribersTable,
      $statsTable,
      'opens',
      "$subscribersTable.id = opens.subscriber_id AND opens.created_at > :newer" . $parameterSuffix
    );
    $queryBuilder->setParameter('newer' . $parameterSuffix, CarbonImmutable::now()->subDays($days)->startOfDay());
    $queryBuilder->groupBy("$subscribersTable.id");
    if ($operator === 'less') {
      $queryBuilder->having("count(opens.id) < :opens" . $parameterSuffix);
    } else {
      $queryBuilder->having("count(opens.id) > :opens" . $parameterSuffix);
    }
    $queryBuilder->setParameter('opens' . $parameterSuffix, $filterData->getParam('opens'));
    return $queryBuilder;
  }
}
