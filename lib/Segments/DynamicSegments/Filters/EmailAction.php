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

  /** @var EntityManager */
  private $entityManager;

  public function __construct(EntityManager $entityManager) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filterEntity): QueryBuilder {
    $action = $filterEntity->getFilterDataParam('action');
    $newsletterId = (int)$filterEntity->getFilterDataParam('newsletter_id');
    $linkId = $filterEntity->getFilterDataParam('link_id') ? (int)$filterEntity->getFilterDataParam('link_id') : null;

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
        "$subscribersTable.id = statssent.subscriber_id AND statssent.newsletter_id = :newsletter"
      )->leftJoin(
        'statssent',
        $statsTable,
        'stats',
        $this->createNotStatsJoinCondition($action, $linkId)
      );
      $where .= ' AND stats.id IS NULL';
    } else {
      $queryBuilder = $queryBuilder->innerJoin(
        $subscribersTable,
        $statsTable,
        'stats',
        "stats.subscriber_id = $subscribersTable.id AND stats.newsletter_id = :newsletter"
      );
    }
    if ($action === EmailAction::ACTION_CLICKED && $linkId) {
      $where .= ' AND stats.link_id = :link';
    }
    $queryBuilder = $queryBuilder
      ->andWhere($where)
      ->setParameter('newsletter', $newsletterId);
    if (in_array($action, [EmailAction::ACTION_CLICKED, EmailAction::ACTION_NOT_CLICKED]) && $linkId) {
      $queryBuilder = $queryBuilder
        ->setParameter('link', $linkId);
    }
    return $queryBuilder;
  }

  private function createNotStatsJoinCondition(string $action, int $linkId = null): string {
    $clause = "statssent.subscriber_id = stats.subscriber_id AND stats.newsletter_id = :newsletter";
    if ($action === EmailAction::ACTION_NOT_CLICKED && $linkId) {
      $clause .= ' AND stats.link_id = :link';
    }
    return $clause;
  }
}
