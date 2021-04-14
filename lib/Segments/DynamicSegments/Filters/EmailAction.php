<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class EmailAction implements Filter {
  const ACTION_OPENED = 'opened';
  const ACTION_NOT_OPENED = 'notOpened';
  const ACTION_CLICKED = 'clicked';
  const ACTION_NOT_CLICKED = 'notClicked';

  const ALLOWED_ACTIONS = [
    self::ACTION_OPENED,
    self::ACTION_NOT_OPENED,
    self::ACTION_CLICKED,
    self::ACTION_NOT_CLICKED,
    EmailOpensAbsoluteCountAction::TYPE,
  ];

  /** @var EntityManager */
  private $entityManager;

  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $action = $filterData->getParam('action');
    $newsletterId = (int)$filterData->getParam('newsletter_id');
    $linkId = $filterData->getParam('link_id') ? (int)$filterData->getParam('link_id') : null;

    $statsSentTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    if (($action === self::ACTION_CLICKED) || ($action === self::ACTION_NOT_CLICKED)) {
      $statsTable = $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName();
    } else {
      $statsTable = $this->entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName();
    }

    $where = '1';

    if (($action === self::ACTION_NOT_CLICKED) || ($action === self::ACTION_NOT_OPENED)) {
      $queryBuilder = $queryBuilder->innerJoin(
        $subscribersTable,
        $statsSentTable,
        'statssent',
        "$subscribersTable.id = statssent.subscriber_id AND statssent.newsletter_id = :newsletter" . $filter->getId()
      )->leftJoin(
        'statssent',
        $statsTable,
        'stats',
        $this->createNotStatsJoinCondition($filter, $action, $linkId)
      );
      $where .= ' AND stats.id IS NULL';
    } else {
      $queryBuilder = $queryBuilder->innerJoin(
        $subscribersTable,
        $statsTable,
        'stats',
        "stats.subscriber_id = $subscribersTable.id AND stats.newsletter_id = :newsletter" . $filter->getId()
      );
    }
    if ($action === EmailAction::ACTION_CLICKED && $linkId) {
      $where .= ' AND stats.link_id = :link' . $filter->getId();
    }
    $queryBuilder = $queryBuilder
      ->andWhere($where)
      ->setParameter('newsletter' . $filter->getId(), $newsletterId);
    if (in_array($action, [EmailAction::ACTION_CLICKED, EmailAction::ACTION_NOT_CLICKED]) && $linkId) {
      $queryBuilder = $queryBuilder
        ->setParameter('link' . $filter->getId(), $linkId);
    }
    return $queryBuilder;
  }

  private function createNotStatsJoinCondition(DynamicSegmentFilterEntity $filter, string $action, int $linkId = null): string {
    $clause = "statssent.subscriber_id = stats.subscriber_id AND stats.newsletter_id = :newsletter" . $filter->getId();
    if ($action === EmailAction::ACTION_NOT_CLICKED && $linkId) {
      $clause .= ' AND stats.link_id = :link' . $filter->getId();
    }
    return $clause;
  }
}
