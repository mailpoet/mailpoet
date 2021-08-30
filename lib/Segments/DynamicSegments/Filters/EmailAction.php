<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class EmailAction implements Filter {
  const ACTION_OPENED = 'opened';
  const ACTION_MACHINE_OPENED = 'machineOpened';
  const ACTION_NOT_OPENED = 'notOpened';
  const ACTION_CLICKED = 'clicked';
  const ACTION_CLICKED_ANY = 'clickedAny';
  const ACTION_NOT_CLICKED = 'notClicked';

  const ALLOWED_ACTIONS = [
    self::ACTION_OPENED,
    self::ACTION_MACHINE_OPENED,
    self::ACTION_NOT_OPENED,
    self::ACTION_CLICKED,
    self::ACTION_NOT_CLICKED,
    self::ACTION_CLICKED_ANY,
    EmailOpensAbsoluteCountAction::TYPE,
  ];

  const CLICK_ACTIONS = [
    self::ACTION_CLICKED,
    self::ACTION_NOT_CLICKED,
    self::ACTION_CLICKED_ANY,
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
    $parameterSuffix = (string)$filter->getId() ?? Security::generateRandomString();

    $statsSentTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $newsletterLinksTable = $this->entityManager->getClassMetadata(NewsletterLinkEntity::class)->getTableName();
    if (in_array($action, self::CLICK_ACTIONS, true)) {
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
        "$subscribersTable.id = statssent.subscriber_id AND statssent.newsletter_id = :newsletter" . $parameterSuffix
      )->leftJoin(
        'statssent',
        $statsTable,
        'stats',
        $this->createNotStatsJoinCondition($filter, $action, $linkId, $parameterSuffix)
      )->setParameter('newsletter' . $parameterSuffix, $newsletterId);
      $where .= ' AND stats.id IS NULL';
    } else if ($action === self::ACTION_CLICKED_ANY) {
      $excludedLinks = [
        '[link:subscription_unsubscribe_url]',
        '[link:subscription_instant_unsubscribe_url]',
        '[link:newsletter_view_in_browser_url]',
        '[link:subscription_manage_url]',
      ];
      $queryBuilder = $queryBuilder->innerJoin(
        $subscribersTable,
        $statsTable,
        'stats',
        "stats.subscriber_id = $subscribersTable.id"
      )->innerJoin(
        'stats',
        $newsletterLinksTable,
        'newsletterLinks',
        "stats.link_id = newsletterLinks.id AND newsletterLinks.URL NOT IN ('" . join("', '", $excludedLinks) . "')"
      );
    } else {
      $queryBuilder = $queryBuilder->innerJoin(
        $subscribersTable,
        $statsTable,
        'stats',
        "stats.subscriber_id = $subscribersTable.id AND stats.newsletter_id = :newsletter" . $parameterSuffix
      )->setParameter('newsletter' . $parameterSuffix, $newsletterId);
    }
    if ($action === EmailAction::ACTION_OPENED) {
      $queryBuilder->andWhere('(stats.user_agent_type = :userAgentType) OR (stats.user_agent_type IS NULL)')
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

  private function createNotStatsJoinCondition(DynamicSegmentFilterEntity $filter, string $action, int $linkId = null, string $parameterSuffix): string {
    $clause = "statssent.subscriber_id = stats.subscriber_id AND stats.newsletter_id = :newsletter" . $parameterSuffix;
    if ($action === EmailAction::ACTION_NOT_CLICKED && $linkId) {
      $clause .= ' AND stats.link_id = :link' . $parameterSuffix;
    }
    return $clause;
  }
}
