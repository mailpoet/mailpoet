<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class AutomationsEvents implements Filter {

  const SUPPORTED_ACTIONS = [
    self::ENTERED_ACTION,
    self::EXITED_ACTION,
  ];

  const ENTERED_ACTION = 'enteredAutomation';
  const EXITED_ACTION = 'exitedAutomation';

  /** @var FilterHelper */
  private $filterHelper;

  public function __construct(
    FilterHelper $filterHelper
  ) {
    $this->filterHelper = $filterHelper;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $action = $filterData->getParam('action');
    $operator = $filterData->getParam('operator');
    $automationIds = $filterData->getParam('automation_ids');

    switch ($operator) {
      case DynamicSegmentFilterData::OPERATOR_ANY:
        $this->applyForAnyOperator($queryBuilder, $action, $automationIds);
        break;
      case DynamicSegmentFilterData::OPERATOR_ALL:
        $this->applyForAllOperator($queryBuilder, $action, $automationIds);
        break;
      case DynamicSegmentFilterData::OPERATOR_NONE:
        $subscribersTable = $this->filterHelper->getSubscribersTable();
        $subQuery = $this->filterHelper->getNewSubscribersQueryBuilder();
        $this->applyForAnyOperator($subQuery, $action, $automationIds);
        $queryBuilder->andWhere($queryBuilder->expr()->notIn("$subscribersTable.id", $this->filterHelper->getInterpolatedSQL($subQuery)));
        break;
    }
    return $queryBuilder;
  }

  private function applyForAnyOperator(QueryBuilder $queryBuilder, $action, $automationIds) {
    $subscribersTable = $this->filterHelper->getSubscribersTable();
    $automationsTable = $this->filterHelper->getPrefixedTable('mailpoet_automations');
    $automationRunsTable = $this->filterHelper->getPrefixedTable('mailpoet_automation_runs');
    $automationRunSubjectsTable = $this->filterHelper->getPrefixedTable('mailpoet_automation_run_subjects');
    $automationIdsParam = $this->filterHelper->getUniqueParameterName('automationIds');

    $queryBuilder
      ->innerJoin(
        $subscribersTable,
        $automationRunSubjectsTable,
        'subjects',
        "subjects.key = 'mailpoet:subscriber' AND subjects.args = CONCAT('{\"subscriber_id\":', $subscribersTable.id, '}')")
      ->innerJoin(
        'subjects',
        $automationRunsTable,
        'runs',
        'subjects.automation_run_id = runs.id')
      ->innerJoin(
        'runs',
        $automationsTable,
        'automations',
        'automations.id = runs.automation_id')
      ->andWhere("automations.id IN (:$automationIdsParam)")
      ->setParameter($automationIdsParam, $automationIds, Connection::PARAM_STR_ARRAY);

    if ($action === self::EXITED_ACTION) {
      $statusParam = $this->filterHelper->getUniqueParameterName('status');
      $queryBuilder
        ->andWhere("runs.status != :$statusParam")
        ->setParameter($statusParam, AutomationRun::STATUS_RUNNING);
    }
  }

  private function applyForAllOperator(QueryBuilder $queryBuilder, $action, $automationIds) {
    $this->applyForAnyOperator($queryBuilder, $action, $automationIds);
    $queryBuilder
      ->groupBy('inner_subscriber_id')
      ->having("COUNT(DISTINCT automations.id) = " . count($automationIds));
  }
}
