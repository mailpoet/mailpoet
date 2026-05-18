<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Cron\Workers\StatsNotifications\NewsletterLinkRepository;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class EmailAction implements Filter {
  const ACTION_OPENED = 'opened';
  const ACTION_MACHINE_OPENED = 'machineOpened';
  /** @deprecated */
  const ACTION_NOT_OPENED = 'notOpened';
  const ACTION_CLICKED = 'clicked';
  const ACTION_WAS_SENT = 'wasSent';
  /** @deprecated */
  const ACTION_NOT_CLICKED = 'notClicked';

  const ALLOWED_ACTIONS = [
    self::ACTION_OPENED,
    self::ACTION_MACHINE_OPENED,
    self::ACTION_CLICKED,
    self::ACTION_WAS_SENT,
    EmailActionClickAny::TYPE,
    EmailOpensAbsoluteCountAction::TYPE,
    EmailOpensAbsoluteCountAction::MACHINE_TYPE,
    EmailsReceived::ACTION,
    NumberOfClicks::ACTION,
  ];

  const AUTOMATION_EMAIL_TYPES = [
    NewsletterEntity::TYPE_AUTOMATION,
    NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL,
  ];

  /** @var EntityManager */
  private $entityManager;
  /** @var FilterHelper */
  private $filterHelper;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterLinkRepository */
  private $newsletterLinkRepository;

  public function __construct(
    EntityManager $entityManager,
    FilterHelper $filterHelper,
    NewslettersRepository $newslettersRepository,
    NewsletterLinkRepository $newsletterLinkRepository
  ) {
    $this->entityManager = $entityManager;
    $this->filterHelper = $filterHelper;
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterLinkRepository = $newsletterLinkRepository;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    $filterData = $filter->getFilterData();
    $action = $filterData->getAction();
    $parameterSuffix = (string)($filter->getId() ?? Security::generateRandomString());

    if ($action === self::ACTION_CLICKED) {
      return $this->applyForClickedActions($queryBuilder, $filterData, $parameterSuffix);
    } elseif ($action === self::ACTION_WAS_SENT) {
      return $this->applyForWasSentAction($queryBuilder, $filterData, $parameterSuffix);
    } else {
      return $this->applyForOpenedActions($queryBuilder, $filterData, $parameterSuffix);
    }
  }

  private function applyForClickedActions(QueryBuilder $queryBuilder, DynamicSegmentFilterData $filterData, string $parameterSuffix): QueryBuilder {
    $operator = $filterData->getParam('operator') ?? DynamicSegmentFilterData::OPERATOR_ANY;
    $action = $filterData->getAction();
    $newsletterId = $this->normalizeNewsletterId($filterData->getParam('newsletter_id'));
    $rawLinkIds = $filterData->getParam('link_ids');
    if (!is_array($rawLinkIds)) {
      $rawLinkIds = [];
    }
    $linkFilter = $this->buildEmailLinkFilter($newsletterId, $rawLinkIds, $parameterSuffix);
    $isAllOperator = $operator === DynamicSegmentFilterData::OPERATOR_ALL;

    $linksTable = $this->entityManager->getClassMetadata(NewsletterLinkEntity::class)->getTableName();
    $where = '1';

    $isNoneOperator = ($action === self::ACTION_NOT_CLICKED) || ($operator === DynamicSegmentFilterData::OPERATOR_NONE);
    if ($isNoneOperator) {
      $queryBuilder = $this->joinStatsForNoneOperator($queryBuilder, $linkFilter, $newsletterId, $parameterSuffix);
      $where .= ' AND stats.id IS NULL';
    } else {
      $queryBuilder = $this->joinStatsForAnyOrAllOperator($queryBuilder, $linkFilter, $newsletterId, $linksTable, $parameterSuffix, $isAllOperator);
    }

    if (!$isNoneOperator && $linkFilter->hasSpecificLinks()) {
      $where .= ' AND ' . $this->buildSelectedLinkWhere($linkFilter, $parameterSuffix);
    }
    if ($isAllOperator) {
      $queryBuilder->groupBy('subscriber_id');
      $queryBuilder->having($this->buildClickedAllHavingClause($linkFilter, $newsletterId, $linksTable));
    }
    $queryBuilder = $queryBuilder->andWhere($where);
    $this->bindLinkFilterParameters($queryBuilder, $linkFilter, $parameterSuffix);
    return $queryBuilder;
  }

  private function joinStatsForNoneOperator(QueryBuilder $queryBuilder, EmailLinkFilter $linkFilter, int $newsletterId, string $parameterSuffix): QueryBuilder {
    $statsSentTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $statsTable = $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName();

    return $queryBuilder->innerJoin(
      $subscribersTable,
      $statsSentTable,
      'statssent',
      "$subscribersTable.id = statssent.subscriber_id AND statssent.newsletter_id = :newsletter" . $parameterSuffix
    )->leftJoin(
      'statssent',
      $statsTable,
      'stats',
      $this->createNotStatsJoinCondition($parameterSuffix, $linkFilter->getLinkIds(), $linkFilter->getLinkUrls())
    )->setParameter('newsletter' . $parameterSuffix, $newsletterId);
  }

  private function joinStatsForAnyOrAllOperator(
    QueryBuilder $queryBuilder,
    EmailLinkFilter $linkFilter,
    int $newsletterId,
    string $linksTable,
    string $parameterSuffix,
    bool $isAllOperator
  ): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $statsTable = $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName();

    $queryBuilder->innerJoin(
      $subscribersTable,
      $statsTable,
      'stats',
      "stats.subscriber_id = $subscribersTable.id AND stats.newsletter_id = :newsletter" . $parameterSuffix
    )->setParameter('newsletter' . $parameterSuffix, $newsletterId);

    if ($linkFilter->needsLinkJoin($isAllOperator)) {
      $alias = $linkFilter->getStatsLinkAlias();
      $queryBuilder->innerJoin('stats', $linksTable, $alias, "stats.link_id = $alias.id");
    }
    return $queryBuilder;
  }

  private function buildSelectedLinkWhere(EmailLinkFilter $linkFilter, string $parameterSuffix): string {
    if ($linkFilter->matchesByUrl()) {
      $alias = $linkFilter->getStatsLinkAlias();
      return "LOWER($alias.url) IN (:linkUrls$parameterSuffix)";
    }
    return 'stats.link_id IN (:links' . $parameterSuffix . ')';
  }

  private function buildClickedAllHavingClause(EmailLinkFilter $linkFilter, int $newsletterId, string $linksTable): string {
    if ($linkFilter->matchesByUrl()) {
      $alias = $linkFilter->getStatsLinkAlias();
      return "COUNT(DISTINCT LOWER($alias.url)) = " . count($linkFilter->getLinkUrls());
    }
    if ($linkFilter->getLinkIds()) {
      return 'COUNT(DISTINCT stats.link_id) = ' . count($linkFilter->getLinkIds());
    }
    // User selected "all of" but no specific links — they need to have clicked every link of the newsletter.
    $alias = $linkFilter->getStatsLinkAlias();
    $totalLinkCount = $this->countNewsletterLinks($linksTable, $newsletterId, $linkFilter->isAutomationNewsletter());
    $clickCountSelect = $linkFilter->aggregatesAllLinksByUrl() ? "COUNT(DISTINCT LOWER($alias.url))" : 'COUNT(1)';
    return $clickCountSelect . ' = ' . $totalLinkCount;
  }

  private function countNewsletterLinks(string $linksTable, int $newsletterId, bool $isAutomationNewsletter): int {
    $linksQueryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
    $linkCountSelect = $isAutomationNewsletter ? 'COUNT(DISTINCT LOWER(url))' : 'COUNT(id)';
    $linkCount = $linksQueryBuilder->select($linkCountSelect)
      ->from($linksTable)
      ->where('newsletter_id = :newsletter_id')
      ->setParameter('newsletter_id', $newsletterId)
      ->execute()
      ->fetchOne();
    return is_scalar($linkCount) ? (int)$linkCount : 0;
  }

  private function bindLinkFilterParameters(QueryBuilder $queryBuilder, EmailLinkFilter $linkFilter, string $parameterSuffix): void {
    if ($linkFilter->getLinkIds()) {
      $queryBuilder->setParameter('links' . $parameterSuffix, $linkFilter->getLinkIds(), ArrayParameterType::INTEGER);
    }
    if ($linkFilter->getLinkUrls()) {
      $queryBuilder->setParameter('linkUrls' . $parameterSuffix, $linkFilter->getLinkUrls(), ArrayParameterType::STRING);
    }
  }

  private function applyForOpenedActions(QueryBuilder $queryBuilder, DynamicSegmentFilterData $filterData, string $parameterSuffix): QueryBuilder {
    $operator = $filterData->getParam('operator') ?? DynamicSegmentFilterData::OPERATOR_ANY;
    $action = $filterData->getAction();
    $newsletters = $filterData->getParam('newsletters');
    $newsletters = is_array($newsletters) ? $newsletters : [];

    $statsSentTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $statsTable = $this->entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName();

    $where = '1';

    if ($operator === DynamicSegmentFilterData::OPERATOR_NONE) {
      $queryBuilder = $queryBuilder->innerJoin(
        $subscribersTable,
        $statsSentTable,
        'statssent',
        "$subscribersTable.id = statssent.subscriber_id AND statssent.newsletter_id IN (:newsletters" . $parameterSuffix . ')'
      )->leftJoin(
        'statssent',
        $statsTable,
        'stats',
        "statssent.subscriber_id = stats.subscriber_id AND stats.newsletter_id IN (:newsletters" . $parameterSuffix . ')'
      )->setParameter('newsletters' . $parameterSuffix, $newsletters, ArrayParameterType::INTEGER);
      $where .= ' AND stats.id IS NULL';
    } else {
      $queryBuilder = $queryBuilder->innerJoin(
        $subscribersTable,
        $statsTable,
        'stats',
        "stats.subscriber_id = $subscribersTable.id AND stats.newsletter_id IN (:newsletters" . $parameterSuffix . ')'
      )->setParameter('newsletters' . $parameterSuffix, $newsletters, ArrayParameterType::INTEGER);

      if ($operator === DynamicSegmentFilterData::OPERATOR_ALL) {
        $queryBuilder->groupBy('subscriber_id');
        $queryBuilder->having('COUNT(DISTINCT stats.newsletter_id) = ' . count($newsletters));
      }
    }
    if (($action === EmailAction::ACTION_OPENED) && ($operator !== DynamicSegmentFilterData::OPERATOR_NONE)) {
      $queryBuilder->andWhere('stats.user_agent_type = :userAgentType')
        ->setParameter('userAgentType', UserAgentEntity::USER_AGENT_TYPE_HUMAN);
    }
    if ($action === EmailAction::ACTION_MACHINE_OPENED) {
      $queryBuilder->andWhere('(stats.user_agent_type = :userAgentType)')
        ->setParameter('userAgentType', UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    }
    $queryBuilder = $queryBuilder->andWhere($where);
    return $queryBuilder;
  }

  private function createNotStatsJoinCondition(string $parameterSuffix, array $linkIds = [], array $linkUrls = []): string {
    $clause = "statssent.subscriber_id = stats.subscriber_id AND stats.newsletter_id = :newsletter" . $parameterSuffix;
    if ($linkUrls) {
      $linksTable = $this->entityManager->getClassMetadata(NewsletterLinkEntity::class)->getTableName();
      $statsLinkAlias = 'notstatslinks' . $parameterSuffix;
      $clause .= " AND stats.link_id IN (SELECT $statsLinkAlias.id FROM $linksTable $statsLinkAlias";
      $clause .= " WHERE $statsLinkAlias.newsletter_id = :newsletter" . $parameterSuffix;
      $clause .= ' AND LOWER(' . $statsLinkAlias . '.url) IN (:linkUrls' . $parameterSuffix . '))';
    } elseif ($linkIds) {
      $clause .= ' AND stats.link_id IN (:links' . $parameterSuffix . ')';
    }
    return $clause;
  }

  private function applyForWasSentAction(QueryBuilder $queryBuilder, DynamicSegmentFilterData $filterData, string $parameterSuffix): QueryBuilder {
    $newsletters = (array)$filterData->getParam('newsletters');
    $operator = $filterData->getParam('operator') ?? DynamicSegmentFilterData::OPERATOR_ANY;
    $subscribersTable = $this->filterHelper->getSubscribersTable();
    $statisticsNewslettersTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();

    if ($operator === DynamicSegmentFilterData::OPERATOR_NONE) {
      $queryBuilder->leftJoin(
        $this->filterHelper->getSubscribersTable(),
        $statisticsNewslettersTable,
        'statisticsNewsletter',
        "$subscribersTable.id = statisticsNewsletter.subscriber_id AND statisticsNewsletter.newsletter_id IN (:newsletters" . $parameterSuffix . ')'
      )
        ->setParameter('newsletters' . $parameterSuffix, $newsletters, ArrayParameterType::INTEGER)
        ->andWhere('statisticsNewsletter.subscriber_id IS NULL');
    } else {
      $queryBuilder->innerJoin(
        $subscribersTable,
        $statisticsNewslettersTable,
        'statisticsNewsletter',
        "statisticsNewsletter.subscriber_id = $subscribersTable.id AND statisticsNewsletter.newsletter_id IN (:newsletters" . $parameterSuffix . ')'
      )->setParameter('newsletters' . $parameterSuffix, $newsletters, ArrayParameterType::INTEGER);

      if ($operator === DynamicSegmentFilterData::OPERATOR_ALL) {
        $queryBuilder->groupBy('subscriber_id');
        $queryBuilder->having('COUNT(DISTINCT statisticsNewsletter.newsletter_id) = ' . count($newsletters));
      }
    }

    return $queryBuilder;
  }

  public function getLookupData(DynamicSegmentFilterData $filterData): array {
    $lookupData = [
      'newsletters' => [],
      'links' => [],
    ];
    $newsletterIds = $filterData->getParam('newsletters');
    if (!is_array($newsletterIds)) {
      $newsletterIds = [];
    }

    // Clicked action only supports single newsletter ID
    $singularNewsletterId = $filterData->getParam('newsletter_id');
    if (!is_null($singularNewsletterId)) {
      $newsletterIds[] = $singularNewsletterId;
    }

    $linkIds = $filterData->getParam('link_ids');
    if (!is_array($linkIds)) {
      $linkIds = [];
    }

    foreach ($newsletterIds as $newsletterId) {
      if (!is_numeric($newsletterId)) {
        continue;
      }
      $newsletterIdInt = (int)$newsletterId;
      $newsletter = $this->newslettersRepository->findOneById($newsletterIdInt);
      if ($newsletter instanceof NewsletterEntity) {
        $lookupData['newsletters'][$newsletterIdInt] = $newsletter->getSubject();
      }
    }

    foreach ($linkIds as $linkId) {
      if (!is_numeric($linkId)) {
        if (is_string($linkId) && trim($linkId) !== '') {
          $linkUrl = trim($linkId);
          $lookupData['links'][$linkUrl] = $linkUrl;
        }
        continue;
      }
      $linkIdInt = (int)$linkId;
      $link = $this->newsletterLinkRepository->findOneById($linkIdInt);
      if ($link instanceof NewsletterLinkEntity) {
        $lookupData['links'][$linkIdInt] = $link->getUrl();
      }
    }

    return $lookupData;
  }

  /** @param mixed $newsletterId */
  private function normalizeNewsletterId($newsletterId): int {
    if (is_int($newsletterId)) {
      return $newsletterId;
    }
    if (is_float($newsletterId) && floor($newsletterId) === $newsletterId) {
      return (int)$newsletterId;
    }
    if (is_string($newsletterId)) {
      $newsletterId = trim($newsletterId);
      if (ctype_digit($newsletterId)) {
        return (int)$newsletterId;
      }
    }
    return 0;
  }

  /** @param mixed[] $rawLinkIds */
  private function buildEmailLinkFilter(int $newsletterId, array $rawLinkIds, string $parameterSuffix): EmailLinkFilter {
    $isAutomationNewsletter = $this->isAutomationNewsletter($newsletterId);
    $matchByUrl = $isAutomationNewsletter || $this->hasUrlLinkIds($rawLinkIds);
    $selectedLinkIds = [];
    $selectedLinkUrls = [];
    foreach ($rawLinkIds as $linkId) {
      if ($this->isNumericLinkId($linkId)) {
        if ($matchByUrl) {
          $link = $this->newsletterLinkRepository->findOneById((int)$linkId);
          if ($link instanceof NewsletterLinkEntity) {
            $selectedLinkUrls[] = $this->normalizeUrlForMatching($link->getUrl());
          }
        } else {
          $selectedLinkIds[] = (int)$linkId;
        }
        continue;
      }
      if (is_string($linkId) && trim($linkId) !== '') {
        $selectedLinkUrls[] = $this->normalizeUrlForMatching($linkId);
      }
    }
    return new EmailLinkFilter(
      $isAutomationNewsletter,
      array_values(array_unique($selectedLinkIds)),
      array_values(array_unique($selectedLinkUrls)),
      'statslinks' . $parameterSuffix
    );
  }

  private function normalizeUrlForMatching(string $url): string {
    return strtolower(trim($url));
  }

  /**
   * @param mixed $linkId
   * @phpstan-assert-if-true int|float|numeric-string $linkId
   */
  private function isNumericLinkId($linkId): bool {
    return is_int($linkId)
      || (is_float($linkId) && floor($linkId) === $linkId)
      || (is_string($linkId) && ctype_digit($linkId));
  }

  private function isAutomationNewsletter(int $newsletterId): bool {
    $newsletter = $this->newslettersRepository->findOneById($newsletterId);
    return $newsletter instanceof NewsletterEntity
      && in_array($newsletter->getType(), self::AUTOMATION_EMAIL_TYPES, true);
  }

  /** @param mixed[] $linkIds */
  private function hasUrlLinkIds(array $linkIds): bool {
    foreach ($linkIds as $linkId) {
      if (is_string($linkId) && !$this->isNumericLinkId($linkId) && trim($linkId) !== '') {
        return true;
      }
    }
    return false;
  }
}
