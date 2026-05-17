<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class EmailOpensAbsoluteCountAction implements Filter {
  const TYPE = 'opensAbsoluteCount';
  const MACHINE_TYPE = 'machineOpensAbsoluteCount';

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
    $operator = $filterData->getParam('operator');
    $action = $filterData->getAction();
    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();
    $statsTable = $this->entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $dateCondition = $this->filterHelper->getDatePeriodCondition($queryBuilder, 'opens.created_at', $filterData, true);
    $joinCondition = "{$subscribersTable}.id = opens.subscriber_id AND opens.user_agent_type = :userAgentType{$parameterSuffix}";
    if ($dateCondition !== null) {
      $joinCondition .= " AND $dateCondition";
    }

    $queryBuilder->leftJoin($subscribersTable, $statsTable, 'opens', $joinCondition);

    $queryBuilder->groupBy("$subscribersTable.id");
    if ($operator === 'equals') {
      $queryBuilder->having("count(opens.id) = :opens" . $parameterSuffix);
    } else if ($operator === 'not_equals') {
      $queryBuilder->having("count(opens.id) != :opens" . $parameterSuffix);
    } else if ($operator === 'less') {
      $queryBuilder->having("count(opens.id) < :opens" . $parameterSuffix);
    } else {
      $queryBuilder->having("count(opens.id) > :opens" . $parameterSuffix);
    }
    $queryBuilder->setParameter('opens' . $parameterSuffix, $filterData->getParam('opens'));

    if ($action === EmailOpensAbsoluteCountAction::TYPE) {
      $queryBuilder->setParameter('userAgentType' . $parameterSuffix, UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    } else {
      $queryBuilder->setParameter('userAgentType' . $parameterSuffix, UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    }

    return $queryBuilder;
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    return [];
  }
}
