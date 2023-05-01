<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\StatisticsFormEntity;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class SubscriberSubscribedViaForm implements Filter {
  const TYPE = 'subscribedViaForm';

  /** @var FilterHelper */
  private $filterHelper;

  public function __construct(
    FilterHelper $filterHelper
  ) {
    $this->filterHelper = $filterHelper;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $formIds = $filterData->getParam('form_ids');
    $operator = $filterData->getParam('operator');

    $subscribersTable = $this->filterHelper->getSubscribersTable();
    $formStatsTable = $this->filterHelper->getTableForEntity(StatisticsFormEntity::class);

    $formIdsParam = $this->filterHelper->getUniqueParameterName('formIds');

    if ($operator === DynamicSegmentFilterData::OPERATOR_ANY) {
      $queryBuilder->innerJoin(
        $subscribersTable,
        $formStatsTable,
        'statisticsForms',
        "$subscribersTable.id = statisticsForms.subscriber_id"
      );
      $queryBuilder->andWhere("statisticsForms.form_id IN (:$formIdsParam)");
    } elseif ($operator === DynamicSegmentFilterData::OPERATOR_NONE) {
      $queryBuilder->leftJoin(
        $subscribersTable,
        $formStatsTable,
        'statisticsForms',
        "$subscribersTable.id = statisticsForms.subscriber_id AND statisticsForms.form_id IN (:$formIdsParam)"
      );
      $queryBuilder->andWhere("statisticsForms.subscriber_id IS NULL");
    }

    $queryBuilder->setParameter($formIdsParam, $formIds, Connection::PARAM_INT_ARRAY);

    return $queryBuilder;
  }
}
