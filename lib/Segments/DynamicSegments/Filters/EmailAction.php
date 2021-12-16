<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class EmailAction implements Filter {
  const ACTION_OPENED = 'opened';
  const ACTION_MACHINE_OPENED = 'machineOpened';
  const ACTION_NOT_OPENED = 'notOpened';
  const ACTION_CLICKED = 'clicked';
  const ACTION_NOT_CLICKED = 'notClicked';

  const ALLOWED_ACTIONS = [
    self::ACTION_OPENED,
    self::ACTION_MACHINE_OPENED,
    self::ACTION_NOT_OPENED,
    self::ACTION_CLICKED,
    self::ACTION_NOT_CLICKED,
    EmailActionClickAny::TYPE,
    EmailOpensAbsoluteCountAction::TYPE,
    EmailOpensAbsoluteCountAction::MACHINE_TYPE,
  ];

  const CLICK_ACTIONS = [
    self::ACTION_CLICKED,
    self::ACTION_NOT_CLICKED,
  ];

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $operator = $filterData->getParam('operator');
    $action = $filterData->getAction();

    if ($action === self::ACTION_NOT_OPENED) {
      // for backward compatibility with old segments
      $action = self::ACTION_OPENED;
      $operator = DynamicSegmentFilterData::OPERATOR_NONE;
    }

    $newsletterId = $filterData->getParam('newsletter_id');
    if ($newsletterId) {
      // for backward compatibility with old segments
      $newsletters = [(int)$newsletterId];
    } else {
      $newsletters = $filterData->getParam('newsletters');
    }


    $linkId = $filterData->getParam('link_id') ? (int)$filterData->getParam('link_id') : null;
    $parameterSuffix = (string)($filter->getId() ?? Security::generateRandomString());

    $statsSentTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    if (in_array($action, self::CLICK_ACTIONS, true)) {
      $statsTable = $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName();
    } else {
      $statsTable = $this->entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName();
    }

    $where = '1';

    if (($action === self::ACTION_NOT_CLICKED)) {
      // TODO remove this if branch in MAILPOET-3951 and merge it with the next one for ACTION_OPENED
      $queryBuilder = $queryBuilder->innerJoin(
        $subscribersTable,
        $statsSentTable,
        'statssent',
        "$subscribersTable.id = statssent.subscriber_id AND statssent.newsletter_id = :newsletter" . $parameterSuffix
      )->leftJoin(
        'statssent',
        $statsTable,
        'stats',
        $this->createNotStatsJoinCondition($action, $parameterSuffix, $linkId)
      )->setParameter('newsletter' . $parameterSuffix, $newsletterId);
      $where .= ' AND stats.id IS NULL';
    } elseif (($action === EmailAction::ACTION_OPENED) && ($operator === DynamicSegmentFilterData::OPERATOR_NONE)) {
      $queryBuilder = $queryBuilder->innerJoin(
        $subscribersTable,
        $statsSentTable,
        'statssent',
        "$subscribersTable.id = statssent.subscriber_id AND statssent.newsletter_id IN (:newsletters" . $parameterSuffix . ')'
      )->leftJoin(
        'statssent',
        $statsTable,
        'stats',
        "statssent.subscriber_id = stats.subscriber_id AND stats.newsletter_id = (:newsletters" . $parameterSuffix . ')'
      )->setParameter('newsletters' . $parameterSuffix, $newsletters, Connection::PARAM_INT_ARRAY);
      $where .= ' AND stats.id IS NULL';
    } elseif ($action === EmailAction::ACTION_OPENED) {
      $queryBuilder = $queryBuilder->innerJoin(
        $subscribersTable,
        $statsTable,
        'stats',
        "stats.subscriber_id = $subscribersTable.id AND stats.newsletter_id IN (:newsletters" . $parameterSuffix . ')'
      )->setParameter('newsletters' . $parameterSuffix, $newsletters, Connection::PARAM_INT_ARRAY);

      if ($operator === DynamicSegmentFilterData::OPERATOR_ALL) {
        $queryBuilder->groupBy('subscriber_id');
        $queryBuilder->having('COUNT(1) = ' . count($newsletters));
      }

    } else {
      // TODO remove this branch in MAILPOET-3951
      $queryBuilder = $queryBuilder->innerJoin(
        $subscribersTable,
        $statsTable,
        'stats',
        "stats.subscriber_id = $subscribersTable.id AND stats.newsletter_id = :newsletter" . $parameterSuffix
      )->setParameter('newsletter' . $parameterSuffix, $newsletterId);
    }
    if (($action === EmailAction::ACTION_OPENED) && ($operator !== DynamicSegmentFilterData::OPERATOR_NONE)) {
      $queryBuilder->andWhere('stats.user_agent_type = :userAgentType')
        ->setParameter('userAgentType', UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    }
    if ($action === EmailAction::ACTION_MACHINE_OPENED) {
      $queryBuilder->andWhere('(stats.user_agent_type = :userAgentType)')
        ->setParameter('userAgentType', UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    }
    if ($action === EmailAction::ACTION_CLICKED && $linkId) {
      $where .= ' AND stats.link_id = :link' . $parameterSuffix;
    }
    $queryBuilder = $queryBuilder->andWhere($where);
    if (in_array($action, [EmailAction::ACTION_CLICKED, EmailAction::ACTION_NOT_CLICKED]) && $linkId) {
      $queryBuilder = $queryBuilder
        ->setParameter('link' . $parameterSuffix, $linkId);
    }
    return $queryBuilder;
  }

  private function createNotStatsJoinCondition(string $action, string $parameterSuffix, int $linkId = null): string {
    $clause = "statssent.subscriber_id = stats.subscriber_id AND stats.newsletter_id = :newsletter" . $parameterSuffix;
    if ($action === EmailAction::ACTION_NOT_CLICKED && $linkId) {
      $clause .= ' AND stats.link_id = :link' . $parameterSuffix;
    }
    return $clause;
  }
}
