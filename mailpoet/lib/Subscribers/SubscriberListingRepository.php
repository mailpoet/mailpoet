<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Segments\DynamicSegments\FilterHandler;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Subscribers\Statistics\SubscriberStatisticsRepository;
use MailPoet\Util\Helpers;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Query\Expr\Join;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

class SubscriberListingRepository extends ListingRepository {
  public const FILTER_WITHOUT_LIST = 'without-list';

  const DEFAULT_SORT_BY = 'createdAt';

  private const ENGAGEMENT_SCORE_UNKNOWN = 'unknown';
  private const ENGAGEMENT_SCORE_DORMANT = 'dormant';
  private const ENGAGEMENT_SCORE_LOW = 'low';
  private const ENGAGEMENT_SCORE_GOOD = 'good';
  private const ENGAGEMENT_SCORE_EXCELLENT = 'excellent';
  private const ENGAGEMENT_SCORE_LOW_MAX = 20;
  private const ENGAGEMENT_SCORE_GOOD_MIN = 20;
  private const ENGAGEMENT_SCORE_GOOD_MAX = 50;
  private const ENGAGEMENT_SCORE_EXCELLENT_MIN = 50;
  private const BULK_RESEND_REASONS = [
    'batch_limit',
    'not_unconfirmed',
    'deleted',
    'max_confirmations_reached',
    'recently_sent',
    'too_old',
    'outside_scope',
    'not_found',
  ];

  private static $supportedStatuses = [
    SubscriberEntity::STATUS_SUBSCRIBED,
    SubscriberEntity::STATUS_UNSUBSCRIBED,
    SubscriberEntity::STATUS_INACTIVE,
    SubscriberEntity::STATUS_BOUNCED,
    SubscriberEntity::STATUS_UNCONFIRMED,
  ];

  /** @var FilterHandler */
  private $dynamicSegmentsFilter;

  /** @var EntityManager */
  private $entityManager;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

  /** @var SubscribersCountsController */
  private $subscribersCountsController;

  /** @var null | ListingDefinition */
  private $definition = null;

  public function __construct(
    EntityManager $entityManager,
    FilterHandler $dynamicSegmentsFilter,
    SegmentSubscribersRepository $segmentSubscribersRepository,
    SubscribersCountsController $subscribersCountsController
  ) {
    parent::__construct($entityManager);
    $this->dynamicSegmentsFilter = $dynamicSegmentsFilter;
    $this->entityManager = $entityManager;
    $this->segmentSubscribersRepository = $segmentSubscribersRepository;
    $this->subscribersCountsController = $subscribersCountsController;
  }

  public function getData(ListingDefinition $definition): array {
    $this->definition = $definition;
    $dynamicSegment = $this->getDynamicSegmentFromFilters($definition);
    if ($dynamicSegment === null) {
      return parent::getData($definition);
    }
    return $this->getDataForDynamicSegment($definition, $dynamicSegment);
  }

  public function getCount(ListingDefinition $definition): int {
    $this->definition = $definition;
    $dynamicSegment = $this->getDynamicSegmentFromFilters($definition);
    if ($dynamicSegment === null) {
      return parent::getCount($definition);
    }
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subscribersIdsQuery = $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("count(DISTINCT $subscribersTable.id)")
      ->from($subscribersTable);
    $subscribersIdsQuery = $this->applyConstraintsForDynamicSegment($subscribersIdsQuery, $definition, $dynamicSegment);
    return (int)$subscribersIdsQuery->execute()->fetchOne();
  }

  public function getActionableIds(ListingDefinition $definition): array {
    $this->definition = $definition;
    $ids = $definition->getSelection();
    if (!empty($ids)) {
      return $ids;
    }
    $dynamicSegment = $this->getDynamicSegmentFromFilters($definition);
    if ($dynamicSegment === null) {
      return parent::getActionableIds($definition);
    }
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subscribersIdsQuery = $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("DISTINCT $subscribersTable.id")
      ->from($subscribersTable);
    $subscribersIdsQuery = $this->applyConstraintsForDynamicSegment($subscribersIdsQuery, $definition, $dynamicSegment);
    $idsStatement = $subscribersIdsQuery->execute();
    $result = $idsStatement->fetchAll();
    return array_column($result, 'id');
  }

  /**
   * @return array{selected_count: int, eligible_count: int, queued_ids: int[], skipped_by_reason: array<string, int>}
   */
  public function getConfirmationEmailResendQueueData(
    ListingDefinition $definition,
    \DateTimeInterface $recentCutoff,
    \DateTimeInterface $oldestLifecycleDate,
    int $maxConfirmationEmails,
    int $limit,
    bool $hasExplicitSelection = false
  ): array {
    $selectedIds = $this->normalizeSelectedIds($definition->getSelection());
    $skippedByReason = array_fill_keys(self::BULK_RESEND_REASONS, 0);
    $base = $this->createBulkResendBaseQuery($definition);
    $idColumn = $base['id_column'];

    if ($hasExplicitSelection) {
      if (!$selectedIds) {
        $selectedCount = count($definition->getSelection());
        $skippedByReason['not_found'] = $selectedCount;
        return [
          'selected_count' => $selectedCount,
          'eligible_count' => 0,
          'queued_ids' => [],
          'skipped_by_reason' => $skippedByReason,
        ];
      }
      $selectedCount = count($selectedIds);
      $skippedByReason = $this->getExplicitSelectionScopeSkippedCounts($selectedIds, $skippedByReason);
      $scopeSkippedCount = $skippedByReason['deleted'] + $skippedByReason['not_unconfirmed'] + $skippedByReason['not_found'];
      $base['query']->andWhere("$idColumn IN (:selected_ids)")
        ->setParameter('selected_ids', $selectedIds, ArrayParameterType::INTEGER);
    } else {
      $selectedCount = 0;
      $scopeSkippedCount = 0;
    }

    $counts = $this->getBulkResendEligibilityCounts(clone $base['query'], $idColumn, $recentCutoff, $oldestLifecycleDate, $maxConfirmationEmails);
    $inScopeCount = $counts['in_scope_count'];
    if (!$hasExplicitSelection) {
      $selectedCount = $inScopeCount;
    }
    $skippedByReason['max_confirmations_reached'] = $counts['max_confirmations_reached'];
    $skippedByReason['recently_sent'] = $counts['recently_sent'];
    $skippedByReason['too_old'] = $counts['too_old'];
    $eligibleCount = $counts['eligible'];

    $eligibleQuery = $this->addEligiblePredicates(clone $base['query'], $idColumn, $recentCutoff, $oldestLifecycleDate, $maxConfirmationEmails);
    $queuedIds = $this->fetchBulkResendIds($eligibleQuery, $idColumn, $limit);
    $skippedByReason['batch_limit'] = max(0, $eligibleCount - count($queuedIds));

    if ($selectedIds) {
      $skippedByReason['outside_scope'] += max(0, $selectedCount - $inScopeCount - $scopeSkippedCount);
    }

    return [
      'selected_count' => $selectedCount,
      'eligible_count' => $eligibleCount,
      'queued_ids' => $queuedIds,
      'skipped_by_reason' => $skippedByReason,
    ];
  }

  /**
   * @param int[] $selectedIds
   * @param array<string, int> $skippedByReason
   * @return array<string, int>
   */
  private function getExplicitSelectionScopeSkippedCounts(array $selectedIds, array $skippedByReason): array {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $rows = $this->entityManager->getConnection()->executeQuery(
      "SELECT `id`, `status`, `deleted_at`
       FROM $subscribersTable
       WHERE `id` IN (:selected_ids)",
      ['selected_ids' => $selectedIds],
      ['selected_ids' => ArrayParameterType::INTEGER]
    )->fetchAllAssociative();

    $existingIds = [];
    foreach ($rows as $row) {
      $existingIds[] = $this->toInt($row['id'] ?? 0);
      if (!empty($row['deleted_at'])) {
        $skippedByReason['deleted']++;
      } elseif (($row['status'] ?? null) !== SubscriberEntity::STATUS_UNCONFIRMED) {
        $skippedByReason['not_unconfirmed']++;
      }
    }
    $skippedByReason['not_found'] = count(array_diff($selectedIds, $existingIds));

    return $skippedByReason;
  }

  /**
   * @return array{query: DBALQueryBuilder, id_column: string}
   */
  private function createBulkResendBaseQuery(ListingDefinition $definition): array {
    $dynamicSegment = $this->getDynamicSegmentFromFilters($definition);
    if ($dynamicSegment instanceof SegmentEntity) {
      $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
      $query = $this->entityManager->getConnection()->createQueryBuilder()
        ->select("DISTINCT $subscribersTable.id")
        ->from($subscribersTable);
      $query = $this->applyConstraintsForDynamicSegment($query, $definition, $dynamicSegment);
      return ['query' => $query, 'id_column' => "$subscribersTable.id"];
    }

    $query = $this->entityManager->getConnection()->createQueryBuilder();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $query->select('DISTINCT s.id')
      ->from($subscribersTable, 's');

    $this->applyBulkResendListingConstraints($query, $definition);
    return ['query' => $query, 'id_column' => 's.id'];
  }

  private function applyBulkResendListingConstraints(DBALQueryBuilder $query, ListingDefinition $definition): void {
    $group = $definition->getGroup();
    if ($group === 'trash') {
      $query->andWhere('s.deleted_at IS NOT NULL');
    } else {
      $query->andWhere('s.deleted_at IS NULL');
    }
    if ($group && in_array($group, self::$supportedStatuses, true)) {
      $query->andWhere('s.status = :listing_status')
        ->setParameter('listing_status', $group);
    }

    $search = $definition->getSearch();
    if ($search && strlen(trim($search)) > 0) {
      $search = Helpers::escapeSearch($search);
      $query
        ->andWhere('(s.email LIKE :search OR s.first_name LIKE :search OR s.last_name LIKE :search)')
        ->setParameter('search', "%$search%");
    }

    $filters = $definition->getFilters();
    if (isset($filters['segment'])) {
      if ($filters['segment'] === self::FILTER_WITHOUT_LIST) {
        $this->segmentSubscribersRepository->addConstraintsForSubscribersWithoutSegmentToDBAL($query);
      } else {
        $segment = $this->entityManager->find(SegmentEntity::class, (int)$filters['segment']);
        if ($segment instanceof SegmentEntity && $segment->isStatic()) {
          $subscriberSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
          $query->join('s', $subscriberSegmentsTable, 'ss', 'ss.subscriber_id = s.id AND ss.segment_id = :segment_id')
            ->setParameter('segment_id', $segment->getId(), ParameterType::INTEGER);
        }
      }
    }

    if (isset($filters['tag'])) {
      $tag = $this->entityManager->find(TagEntity::class, (int)$filters['tag']);
      if ($tag instanceof TagEntity) {
        $subscriberTagsTable = $this->entityManager->getClassMetadata(SubscriberTagEntity::class)->getTableName();
        $query->join('s', $subscriberTagsTable, 'st', 'st.subscriber_id = s.id AND st.tag_id = :tag_id')
          ->setParameter('tag_id', $tag->getId(), ParameterType::INTEGER);
      }
    }

    if (isset($filters['minUpdatedAt']) && $filters['minUpdatedAt'] instanceof \DateTimeInterface) {
      $query->andWhere('s.updated_at >= :updated_at')
        ->setParameter('updated_at', $filters['minUpdatedAt']->format('Y-m-d H:i:s'), ParameterType::STRING);
    }

    $statusInclude = $this->sanitizeStatusFilter($filters['statusInclude'] ?? []);
    if ($statusInclude) {
      $query->andWhere('s.status IN (:status_include)')
        ->setParameter('status_include', $statusInclude, ArrayParameterType::STRING);
    }

    $statusExclude = $this->sanitizeStatusFilter($filters['statusExclude'] ?? []);
    if ($statusExclude) {
      $query->andWhere('s.status NOT IN (:status_exclude)')
        ->setParameter('status_exclude', $statusExclude, ArrayParameterType::STRING);
    }

    $createdAtFrom = $filters['createdAtFrom'] ?? null;
    if ($createdAtFrom && is_string($createdAtFrom) && $this->isValidDateTime($createdAtFrom)) {
      $query->andWhere('s.created_at >= :created_at_from')
        ->setParameter('created_at_from', $createdAtFrom, ParameterType::STRING);
    }

    $createdAtTo = $filters['createdAtTo'] ?? null;
    if ($createdAtTo && is_string($createdAtTo) && $this->isValidDateTime($createdAtTo)) {
      $query->andWhere('s.created_at <= :created_at_to')
        ->setParameter('created_at_to', $createdAtTo, ParameterType::STRING);
    }

    $engagementScoreInclude = $filters['engagementScoreInclude'] ?? [];
    if (!empty($engagementScoreInclude)) {
      $engagementScoreInclude = is_array($engagementScoreInclude) ? $engagementScoreInclude : [$engagementScoreInclude];
      if (in_array(self::ENGAGEMENT_SCORE_DORMANT, $engagementScoreInclude, true)) {
        $query->setParameter('engagement_score_recent_cutoff', (new \DateTimeImmutable('-1 year'))->format('Y-m-d H:i:s'), ParameterType::STRING);
      }
      $conditions = $this->getDbalEngagementScoreConditions($engagementScoreInclude);
      if ($conditions) {
        $query->andWhere('(' . implode(' OR ', $conditions) . ')');
      }
    }

    $engagementScoreExclude = $filters['engagementScoreExclude'] ?? [];
    if (!empty($engagementScoreExclude)) {
      $engagementScoreExclude = is_array($engagementScoreExclude) ? $engagementScoreExclude : [$engagementScoreExclude];
      if (in_array(self::ENGAGEMENT_SCORE_DORMANT, $engagementScoreExclude, true)) {
        $query->setParameter('engagement_score_recent_cutoff', (new \DateTimeImmutable('-1 year'))->format('Y-m-d H:i:s'), ParameterType::STRING);
      }
      foreach ($engagementScoreExclude as $score) {
        if ($score === self::ENGAGEMENT_SCORE_UNKNOWN) {
          $query->andWhere('NOT ' . $this->getDbalUnknownEngagementScoreCondition());
        } elseif ($score === self::ENGAGEMENT_SCORE_DORMANT) {
          $query->andWhere('NOT ' . $this->getDbalDormantEngagementScoreCondition());
        } elseif ($score === self::ENGAGEMENT_SCORE_LOW) {
          $query->andWhere(sprintf('(s.engagement_score >= %d OR s.engagement_score IS NULL)', self::ENGAGEMENT_SCORE_LOW_MAX));
        } elseif ($score === self::ENGAGEMENT_SCORE_GOOD) {
          $query->andWhere(sprintf('(s.engagement_score < %d OR s.engagement_score >= %d OR s.engagement_score IS NULL)', self::ENGAGEMENT_SCORE_GOOD_MIN, self::ENGAGEMENT_SCORE_GOOD_MAX));
        } elseif ($score === self::ENGAGEMENT_SCORE_EXCELLENT) {
          $query->andWhere(sprintf('(s.engagement_score < %d OR s.engagement_score IS NULL)', self::ENGAGEMENT_SCORE_EXCELLENT_MIN));
        }
      }
    }
  }

  /**
   * @param mixed $statuses
   * @return string[]
   */
  private function sanitizeStatusFilter($statuses): array {
    $statuses = is_array($statuses) ? $statuses : [$statuses];
    $statuses = array_filter($statuses, function($status) {
      return is_string($status) && in_array($status, self::$supportedStatuses, true);
    });
    return array_values(array_unique($statuses));
  }

  /**
   * @param mixed[] $scores
   * @return string[]
   */
  private function getDbalEngagementScoreConditions(array $scores): array {
    $conditions = [];
    if (in_array(self::ENGAGEMENT_SCORE_UNKNOWN, $scores, true)) {
      $conditions[] = $this->getDbalUnknownEngagementScoreCondition();
    }
    if (in_array(self::ENGAGEMENT_SCORE_DORMANT, $scores, true)) {
      $conditions[] = $this->getDbalDormantEngagementScoreCondition();
    }
    if (in_array(self::ENGAGEMENT_SCORE_LOW, $scores, true)) {
      $conditions[] = sprintf('(s.engagement_score < %d)', self::ENGAGEMENT_SCORE_LOW_MAX);
    }
    if (in_array(self::ENGAGEMENT_SCORE_GOOD, $scores, true)) {
      $conditions[] = sprintf('(s.engagement_score >= %d AND s.engagement_score < %d)', self::ENGAGEMENT_SCORE_GOOD_MIN, self::ENGAGEMENT_SCORE_GOOD_MAX);
    }
    if (in_array(self::ENGAGEMENT_SCORE_EXCELLENT, $scores, true)) {
      $conditions[] = sprintf('(s.engagement_score >= %d)', self::ENGAGEMENT_SCORE_EXCELLENT_MIN);
    }
    return $conditions;
  }

  private function getDbalUnknownEngagementScoreCondition(): string {
    $lifetimeSentCount = $this->getDbalSentCountSubquery('s.id');
    return sprintf(
      '(s.engagement_score IS NULL AND %s < %d)',
      $lifetimeSentCount,
      SubscriberStatisticsRepository::MIN_SENT_EMAILS_FOR_ENGAGEMENT_SCORE
    );
  }

  private function getDbalDormantEngagementScoreCondition(): string {
    $lifetimeSentCount = $this->getDbalSentCountSubquery('s.id');
    $recentSentCount = $this->getDbalSentCountSubquery('s.id', 'engagement_score_recent_cutoff');
    return sprintf(
      '(s.engagement_score IS NULL AND %s >= %d AND %s < %d)',
      $lifetimeSentCount,
      SubscriberStatisticsRepository::MIN_SENT_EMAILS_FOR_ENGAGEMENT_SCORE,
      $recentSentCount,
      SubscriberStatisticsRepository::MIN_SENT_EMAILS_FOR_ENGAGEMENT_SCORE
    );
  }

  private function getDbalSentCountSubquery(string $subscriberIdColumn, ?string $sentAtParameter = null): string {
    $statisticsNewslettersTable = $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
    $sentAtCondition = $sentAtParameter ? " AND engagement_score_stats.sent_at >= :$sentAtParameter" : '';
    return sprintf(
      '(SELECT COUNT(DISTINCT engagement_score_stats.newsletter_id) FROM %s engagement_score_stats WHERE engagement_score_stats.subscriber_id = %s%s)',
      $statisticsNewslettersTable,
      $subscriberIdColumn,
      $sentAtCondition
    );
  }

  /**
   * @return array{in_scope_count: int, max_confirmations_reached: int, recently_sent: int, too_old: int, eligible: int}
   */
  private function getBulkResendEligibilityCounts(
    DBALQueryBuilder $query,
    string $idColumn,
    \DateTimeInterface $recentCutoff,
    \DateTimeInterface $oldestLifecycleDate,
    int $maxConfirmationEmails
  ): array {
    $countQuery = clone $query;
    $countConfirmationColumn = $this->column($idColumn, 'count_confirmations');
    $lastConfirmationEmailSentAtColumn = $this->column($idColumn, 'last_confirmation_email_sent_at');
    $lifecycleDateExpression = 'COALESCE(' . $this->column($idColumn, 'last_subscribed_at') . ', ' . $this->column($idColumn, 'created_at') . ')';
    $belowMaxConfirmations = "$countConfirmationColumn < :max_confirmation_emails";
    $maxConfirmationsReached = "$countConfirmationColumn >= :max_confirmation_emails";
    $recentlySent = "$lastConfirmationEmailSentAtColumn IS NOT NULL AND $lastConfirmationEmailSentAtColumn > :recent_cutoff";
    $notRecentlySent = "($lastConfirmationEmailSentAtColumn IS NULL OR $lastConfirmationEmailSentAtColumn <= :recent_cutoff)";
    $tooOld = "$lifecycleDateExpression < :oldest_lifecycle_date";
    $notTooOld = "$lifecycleDateExpression >= :oldest_lifecycle_date";

    $countQuery->select(implode(', ', [
      "COUNT(DISTINCT $idColumn) AS in_scope_count",
      "COUNT(DISTINCT CASE WHEN $maxConfirmationsReached THEN $idColumn END) AS max_confirmations_reached",
      "COUNT(DISTINCT CASE WHEN $belowMaxConfirmations AND $recentlySent THEN $idColumn END) AS recently_sent",
      "COUNT(DISTINCT CASE WHEN $belowMaxConfirmations AND $notRecentlySent AND $tooOld THEN $idColumn END) AS too_old",
      "COUNT(DISTINCT CASE WHEN $belowMaxConfirmations AND $notRecentlySent AND $notTooOld THEN $idColumn END) AS eligible",
    ]))
      ->setParameter('max_confirmation_emails', $maxConfirmationEmails, ParameterType::INTEGER)
      ->setParameter('recent_cutoff', $recentCutoff->format('Y-m-d H:i:s'), ParameterType::STRING)
      ->setParameter('oldest_lifecycle_date', $oldestLifecycleDate->format('Y-m-d H:i:s'), ParameterType::STRING);

    $row = $countQuery->executeQuery()->fetchAssociative() ?: [];
    return [
      'in_scope_count' => $this->toInt($row['in_scope_count'] ?? 0),
      'max_confirmations_reached' => $this->toInt($row['max_confirmations_reached'] ?? 0),
      'recently_sent' => $this->toInt($row['recently_sent'] ?? 0),
      'too_old' => $this->toInt($row['too_old'] ?? 0),
      'eligible' => $this->toInt($row['eligible'] ?? 0),
    ];
  }

  /**
   * @return int[]
   */
  private function fetchBulkResendIds(DBALQueryBuilder $query, string $idColumn, int $limit): array {
    $query->select("DISTINCT $idColumn AS id")
      ->orderBy($idColumn, 'ASC')
      ->setMaxResults($limit);
    return array_map(function($id): int {
      return $this->toInt($id);
    }, $query->executeQuery()->fetchFirstColumn());
  }

  private function addEligiblePredicates(DBALQueryBuilder $query, string $idColumn, \DateTimeInterface $recentCutoff, \DateTimeInterface $oldestLifecycleDate, int $maxConfirmationEmails): DBALQueryBuilder {
    return $this->addNotTooOldPredicate(
      $this->addNotRecentPredicate(
        $this->addBelowMaxConfirmationPredicate($query, $idColumn, $maxConfirmationEmails),
        $idColumn,
        $recentCutoff
      ),
      $idColumn,
      $oldestLifecycleDate
    );
  }

  private function addBelowMaxConfirmationPredicate(DBALQueryBuilder $query, string $idColumn, int $maxConfirmationEmails): DBALQueryBuilder {
    $query->andWhere($this->column($idColumn, 'count_confirmations') . ' < :max_confirmation_emails')
      ->setParameter('max_confirmation_emails', $maxConfirmationEmails, ParameterType::INTEGER);
    return $query;
  }

  private function addNotRecentPredicate(DBALQueryBuilder $query, string $idColumn, \DateTimeInterface $recentCutoff): DBALQueryBuilder {
    $column = $this->column($idColumn, 'last_confirmation_email_sent_at');
    $query->andWhere("($column IS NULL OR $column <= :recent_cutoff)")
      ->setParameter('recent_cutoff', $recentCutoff->format('Y-m-d H:i:s'), ParameterType::STRING);
    return $query;
  }

  private function addNotTooOldPredicate(DBALQueryBuilder $query, string $idColumn, \DateTimeInterface $oldestLifecycleDate): DBALQueryBuilder {
    $query->andWhere('COALESCE(' . $this->column($idColumn, 'last_subscribed_at') . ', ' . $this->column($idColumn, 'created_at') . ') >= :oldest_lifecycle_date')
      ->setParameter('oldest_lifecycle_date', $oldestLifecycleDate->format('Y-m-d H:i:s'), ParameterType::STRING);
    return $query;
  }

  private function column(string $idColumn, string $column): string {
    if ($idColumn === 's.id') {
      return "s.$column";
    }
    $table = substr($idColumn, 0, -3);
    return "$table.$column";
  }

  /**
   * @param mixed[] $ids
   * @return int[]
   */
  private function normalizeSelectedIds(array $ids): array {
    $ids = array_map(function($id): int {
      return $this->toInt($id);
    }, $ids);
    $ids = array_filter($ids, static function(int $id): bool {
      return $id > 0;
    });
    return array_values(array_unique($ids));
  }

  private function toInt($value): int {
    if (is_int($value)) {
      return $value;
    }
    if (is_string($value) || is_float($value) || is_bool($value)) {
      return (int)$value;
    }
    return 0;
  }

  protected function applySelectClause(QueryBuilder $queryBuilder) {
    $queryBuilder->select("PARTIAL s.{id,email,firstName,lastName,status,createdAt,deletedAt,updatedAt,countConfirmations,wpUserId,isWoocommerceUser,engagementScore,lastSubscribedAt}");
  }

  protected function applyFromClause(QueryBuilder $queryBuilder) {
    $queryBuilder->from(SubscriberEntity::class, 's');
  }

  protected function applyGroup(QueryBuilder $queryBuilder, string $group) {
    // include/exclude deleted
    if ($group === 'trash') {
      $queryBuilder->andWhere('s.deletedAt IS NOT NULL');
    } else {
      $queryBuilder->andWhere('s.deletedAt IS NULL');
    }

    if (!in_array($group, self::$supportedStatuses)) {
      return;
    }

    $staticSegment = $this->getStaticSegmentFromDefinition();

    if (in_array($group, [SubscriberEntity::STATUS_SUBSCRIBED, SubscriberEntity::STATUS_UNSUBSCRIBED]) && $staticSegment) {
      $operator = $group === SubscriberEntity::STATUS_SUBSCRIBED ? 'AND' : 'OR';
      $queryBuilder
        ->andWhere('(s.status = :status ' . $operator . ' ss.status = :status)')
        ->setParameter('status', $group);
      return;
    }

    $queryBuilder
      ->andWhere('s.status = :status')
      ->setParameter('status', $group);

    // Under a static list, a member unsubscribed from THIS list belongs in the
    // unsubscribed tab — not in inactive/unconfirmed/bounced. Excluding them here
    // keeps the listed rows in step with the per-list statistics tab counts.
    if ($staticSegment) {
      $queryBuilder
        ->andWhere('ss.status != :ssNotUnsubscribed')
        ->setParameter('ssNotUnsubscribed', SubscriberEntity::STATUS_UNSUBSCRIBED);
    }
  }

  private function getStaticSegmentFromDefinition(): ?SegmentEntity {
    if (!$this->definition) {
      return null;
    }
    $filters = $this->definition->getFilters();
    if (empty($filters['segment']) || $filters['segment'] === self::FILTER_WITHOUT_LIST) {
      return null;
    }
    $segment = $this->entityManager->find(SegmentEntity::class, (int)$filters['segment']);
    return ($segment instanceof SegmentEntity && $segment->isStatic()) ? $segment : null;
  }

  protected function applySearch(QueryBuilder $queryBuilder, string $search, array $parameters = []) {
    $search = Helpers::escapeSearch($search);
    $queryBuilder
      ->andWhere('s.email LIKE :search or s.firstName LIKE :search or s.lastName LIKE :search')
      ->setParameter('search', "%$search%");
  }

  protected function applyFilters(QueryBuilder $queryBuilder, array $filters) {
    if (isset($filters['segment'])) {
      if ($filters['segment'] === self::FILTER_WITHOUT_LIST) {
        $this->segmentSubscribersRepository->addConstraintsForSubscribersWithoutSegment($queryBuilder);
      } else {
        $segment = $this->entityManager->find(SegmentEntity::class, (int)$filters['segment']);
        if ($segment instanceof SegmentEntity && $segment->isStatic()) {
          $queryBuilder->join('s.subscriberSegments', 'ss', Join::WITH, 'ss.segment = :ssSegment')
            ->setParameter('ssSegment', $segment->getId());
        }
      }
    }

    // filtering by minimal updated at
    if (isset($filters['minUpdatedAt']) && $filters['minUpdatedAt'] instanceof \DateTimeInterface) {
      $queryBuilder->andWhere('s.updatedAt >= :updatedAt')
        ->setParameter('updatedAt', $filters['minUpdatedAt']);
    }

    if (isset($filters['tag'])) {
      $tag = $this->entityManager->find(TagEntity::class, (int)$filters['tag']);
      if ($tag) {
        $queryBuilder->join('s.subscriberTags', 'st', Join::WITH, 'st.tag = :stTag')
          ->setParameter('stTag', $tag);
      }
    }

    // Status inclusion filter
    $statusInclude = $filters['statusInclude'] ?? [];
    if (!empty($statusInclude)) {
      $statusInclude = is_array($statusInclude) ? $statusInclude : [$statusInclude];
      // Sanitize: only allow valid status values
      $statusInclude = array_filter($statusInclude, function($status) {
        return is_string($status) && in_array($status, self::$supportedStatuses, true);
      });
      if (!empty($statusInclude)) {
        $queryBuilder->andWhere('s.status IN (:statusInclude)')
          ->setParameter('statusInclude', $statusInclude);
      }
    }

    // Status exclusion filter
    $statusExclude = $filters['statusExclude'] ?? [];
    if (!empty($statusExclude)) {
      $statusExclude = is_array($statusExclude) ? $statusExclude : [$statusExclude];
      // Sanitize: only allow valid status values
      $statusExclude = array_filter($statusExclude, function($status) {
        return is_string($status) && in_array($status, self::$supportedStatuses, true);
      });
      if (!empty($statusExclude)) {
        $queryBuilder->andWhere('s.status NOT IN (:statusExclude)')
          ->setParameter('statusExclude', $statusExclude);
      }
    }

    // Filter by created_at date
    $createdAtFrom = $filters['createdAtFrom'] ?? null;
    if ($createdAtFrom && is_string($createdAtFrom) && $this->isValidDateTime($createdAtFrom)) {
      $queryBuilder
        ->andWhere('s.createdAt >= :createdAtFrom')
        ->setParameter('createdAtFrom', $createdAtFrom);
    }

    $createdAtTo = $filters['createdAtTo'] ?? null;
    if ($createdAtTo && is_string($createdAtTo) && $this->isValidDateTime($createdAtTo)) {
      $queryBuilder
        ->andWhere('s.createdAt <= :createdAtTo')
        ->setParameter('createdAtTo', $createdAtTo);
    }

    // Filter by engagement score (include)
    $engagementScoreInclude = $filters['engagementScoreInclude'] ?? [];
    if (!empty($engagementScoreInclude)) {
      $engagementScoreInclude = is_array($engagementScoreInclude) ? $engagementScoreInclude : [$engagementScoreInclude];
      if (in_array(self::ENGAGEMENT_SCORE_DORMANT, $engagementScoreInclude, true)) {
        $queryBuilder->setParameter('engagementScoreRecentCutoff', new \DateTimeImmutable('-1 year'));
      }
      $conditions = $this->getDqlEngagementScoreConditions($engagementScoreInclude, 'engagementScoreInclude');

      if (!empty($conditions)) {
        $queryBuilder->andWhere('(' . implode(' OR ', $conditions) . ')');
      }
    }

    // Filter by engagement score (exclude)
    $engagementScoreExclude = $filters['engagementScoreExclude'] ?? [];
    if (!empty($engagementScoreExclude)) {
      $engagementScoreExclude = is_array($engagementScoreExclude) ? $engagementScoreExclude : [$engagementScoreExclude];
      if (in_array(self::ENGAGEMENT_SCORE_DORMANT, $engagementScoreExclude, true)) {
        $queryBuilder->setParameter('engagementScoreRecentCutoff', new \DateTimeImmutable('-1 year'));
      }

      if (in_array(self::ENGAGEMENT_SCORE_UNKNOWN, $engagementScoreExclude, true)) {
        $queryBuilder->andWhere('NOT ' . $this->getDqlUnknownEngagementScoreCondition('engagementScoreExclude'));
      }
      if (in_array(self::ENGAGEMENT_SCORE_DORMANT, $engagementScoreExclude, true)) {
        $queryBuilder->andWhere('NOT ' . $this->getDqlDormantEngagementScoreCondition('engagementScoreExclude'));
      }
      if (in_array(self::ENGAGEMENT_SCORE_LOW, $engagementScoreExclude, true)) {
        $queryBuilder->andWhere(sprintf(
          '(s.engagementScore >= %d OR s.engagementScore IS NULL)',
          self::ENGAGEMENT_SCORE_LOW_MAX
        ));
      }
      if (in_array(self::ENGAGEMENT_SCORE_GOOD, $engagementScoreExclude, true)) {
        $queryBuilder->andWhere(sprintf(
          '(s.engagementScore < %d OR s.engagementScore >= %d OR s.engagementScore IS NULL)',
          self::ENGAGEMENT_SCORE_GOOD_MIN,
          self::ENGAGEMENT_SCORE_GOOD_MAX
        ));
      }
      if (in_array(self::ENGAGEMENT_SCORE_EXCELLENT, $engagementScoreExclude, true)) {
        $queryBuilder->andWhere(sprintf(
          '(s.engagementScore < %d OR s.engagementScore IS NULL)',
          self::ENGAGEMENT_SCORE_EXCELLENT_MIN
        ));
      }
    }
  }

  /**
   * @param mixed[] $scores
   * @return string[]
   */
  private function getDqlEngagementScoreConditions(array $scores, string $aliasPrefix): array {
    $conditions = [];
    if (in_array(self::ENGAGEMENT_SCORE_UNKNOWN, $scores, true)) {
      $conditions[] = $this->getDqlUnknownEngagementScoreCondition($aliasPrefix);
    }
    if (in_array(self::ENGAGEMENT_SCORE_DORMANT, $scores, true)) {
      $conditions[] = $this->getDqlDormantEngagementScoreCondition($aliasPrefix);
    }
    if (in_array(self::ENGAGEMENT_SCORE_LOW, $scores, true)) {
      $conditions[] = sprintf(
        '(s.engagementScore < %d)',
        self::ENGAGEMENT_SCORE_LOW_MAX
      );
    }
    if (in_array(self::ENGAGEMENT_SCORE_GOOD, $scores, true)) {
      $conditions[] = sprintf(
        '(s.engagementScore >= %d AND s.engagementScore < %d)',
        self::ENGAGEMENT_SCORE_GOOD_MIN,
        self::ENGAGEMENT_SCORE_GOOD_MAX
      );
    }
    if (in_array(self::ENGAGEMENT_SCORE_EXCELLENT, $scores, true)) {
      $conditions[] = sprintf(
        '(s.engagementScore >= %d)',
        self::ENGAGEMENT_SCORE_EXCELLENT_MIN
      );
    }
    return $conditions;
  }

  private function getDqlUnknownEngagementScoreCondition(string $aliasPrefix): string {
    $lifetimeSentCount = $this->getDqlSentCountSubquery($aliasPrefix . 'TotalStats');
    return sprintf(
      '(s.engagementScore IS NULL AND %s < %d)',
      $lifetimeSentCount,
      SubscriberStatisticsRepository::MIN_SENT_EMAILS_FOR_ENGAGEMENT_SCORE
    );
  }

  private function getDqlDormantEngagementScoreCondition(string $aliasPrefix): string {
    $lifetimeSentCount = $this->getDqlSentCountSubquery($aliasPrefix . 'LifetimeStats');
    $recentSentCount = $this->getDqlSentCountSubquery($aliasPrefix . 'RecentStats', 'engagementScoreRecentCutoff');
    return sprintf(
      '(s.engagementScore IS NULL AND %s >= %d AND %s < %d)',
      $lifetimeSentCount,
      SubscriberStatisticsRepository::MIN_SENT_EMAILS_FOR_ENGAGEMENT_SCORE,
      $recentSentCount,
      SubscriberStatisticsRepository::MIN_SENT_EMAILS_FOR_ENGAGEMENT_SCORE
    );
  }

  private function getDqlSentCountSubquery(string $alias, ?string $sentAtParameter = null): string {
    $sentAtCondition = $sentAtParameter ? " AND $alias.sentAt >= :$sentAtParameter" : '';
    return sprintf(
      '(SELECT COUNT(DISTINCT %s.newsletter) FROM %s %s WHERE %s.subscriber = s%s)',
      $alias,
      StatisticsNewsletterEntity::class,
      $alias,
      $alias,
      $sentAtCondition
    );
  }

  private function isValidDateTime(string $dateTime): bool {
    try {
      new \DateTime($dateTime);
      return true;
    } catch (\Exception $e) {
      return false;
    }
  }

  protected function applyParameters(QueryBuilder $queryBuilder, array $parameters) {
    // nothing to do here
  }

  protected function applySorting(QueryBuilder $queryBuilder, string $sortBy, string $sortOrder) {
    if (!$sortBy) {
      $sortBy = self::DEFAULT_SORT_BY;
    }
    $queryBuilder->addOrderBy("s.$sortBy", $sortOrder);
    if ($sortBy !== 'id') {
      // Deterministic tiebreaker so pagination stays stable when the sorted
      // column has duplicate values. created_at has per-second granularity, so
      // large or imported lists tie often; pairing it with id also matches the
      // deleted_at_created index (deleted_at, created_at + implicit id), which
      // serves the default listing with the WHERE pinning deleted_at, keeping
      // the sort index-backed.
      $queryBuilder->addOrderBy('s.id', $sortOrder);
    }
  }

  public function getGroups(ListingDefinition $definition): array {
    return $this->formatGroups($this->getGroupCountsForDefinition($definition)['counts']);
  }

  /**
   * Count and status groups from a single computation, so the listing endpoint
   * can drop the separate getCount() query: on the non-segment path both come
   * from one grouped scan, and the current group's count is read straight from
   * the buckets.
   *
   * @return array{count: int, groups: array<int, array<string, mixed>>}
   */
  public function getCountAndGroups(ListingDefinition $definition): array {
    $computed = $this->getGroupCountsForDefinition($definition);
    return [
      'count' => $this->countForCurrentGroup($definition, $computed['counts'], $computed['consolidated']),
      'groups' => $this->formatGroups($computed['counts']),
    ];
  }

  /**
   * A static segment redefines the subscribed/unsubscribed buckets in terms of
   * the per-list status (s.status OR/AND ss.status), and a dynamic segment
   * routes counts through an id subquery — neither can be expressed as a single
   * GROUP BY over s.status, so they fall back to one count per group. Both are
   * scoped to a single segment, so that loop runs over a far smaller set than
   * the full list.
   *
   * @return array{counts: array<string, int>, consolidated: bool}
   */
  private function getGroupCountsForDefinition(ListingDefinition $definition): array {
    if (!$this->hasSegmentListFilter($definition)) {
      // The unfiltered "All Lists" view maps to the cron-warmed global status
      // counts — the default, most-loaded view, served from cache instead of a
      // grouped scan over every subscriber on each page load.
      if ($this->canUseGlobalStatisticsCache($definition)) {
        return ['counts' => $this->globalStatisticsToCounts(), 'consolidated' => true];
      }
      return ['counts' => $this->getGroupCounts($definition), 'consolidated' => true];
    }

    // A plain list filter (no search, no other filter) maps exactly to the
    // cron-warmed per-segment statistics — single source of truth, served from
    // cache instead of recomputing a count per status tab on every page load.
    if ($this->canUseSegmentStatisticsCache($definition)) {
      $segment = $this->getSegmentFromDefinition($definition);
      if ($segment instanceof SegmentEntity) {
        return ['counts' => $this->segmentStatisticsToCounts($segment), 'consolidated' => false];
      }
    }

    // Search or an extra filter narrows the list beyond plain membership, so the
    // cache can't answer it — compute live for that ad-hoc query.
    return ['counts' => $this->getGroupCountsPerStatus($definition), 'consolidated' => false];
  }

  /**
   * The per-segment statistics cache reflects plain list membership and status.
   * It can stand in for the tab counts only when nothing else narrows the set —
   * no search and no other active filter (tag, status, dates, engagement).
   */
  private function canUseSegmentStatisticsCache(ListingDefinition $definition): bool {
    if ($this->isSearchActive($definition)) {
      return false;
    }
    $filters = $definition->getFilters() ?: [];
    unset($filters['segment']);
    foreach ($filters as $value) {
      if (!empty($value)) {
        return false;
      }
    }
    return true;
  }

  /**
   * Map the cached per-segment statistics onto the listing's status-keyed counts
   * array. "all" is read in formatGroups/countForCurrentGroup as the sum of the
   * status buckets, which equals the cached "all" because the buckets partition
   * the non-deleted members exactly.
   *
   * @return array<string, int>
   */
  private function segmentStatisticsToCounts(SegmentEntity $segment): array {
    $stats = $this->subscribersCountsController->getSegmentStatisticsCount($segment);
    $counts = array_fill_keys(self::$supportedStatuses, 0);
    $counts['trash'] = 0;
    foreach (self::$supportedStatuses as $status) {
      $counts[$status] = (int)($stats[$status] ?? 0);
    }
    $counts['trash'] = (int)($stats['trash'] ?? 0);
    return $counts;
  }

  private function getSegmentFromDefinition(ListingDefinition $definition): ?SegmentEntity {
    $filters = $definition->getFilters();
    if (empty($filters['segment']) || $filters['segment'] === self::FILTER_WITHOUT_LIST) {
      return null;
    }
    $segment = $this->entityManager->find(SegmentEntity::class, (int)$filters['segment']);
    return $segment instanceof SegmentEntity ? $segment : null;
  }

  private function isSearchActive(ListingDefinition $definition): bool {
    $search = $definition->getSearch();
    return $search !== null && strlen(trim($search)) > 0;
  }

  /**
   * The global status counts cover the unfiltered listing only — no search and
   * no active filter at all. Any filter (tag, status, dates, engagement) narrows
   * the set below "all subscribers", so the cache no longer matches.
   */
  private function canUseGlobalStatisticsCache(ListingDefinition $definition): bool {
    $search = $definition->getSearch();
    if ($search !== null && strlen(trim($search)) > 0) {
      return false;
    }
    $filters = $definition->getFilters() ?: [];
    foreach ($filters as $value) {
      if (!empty($value)) {
        return false;
      }
    }
    return true;
  }

  /**
   * @return array<string, int>
   */
  private function globalStatisticsToCounts(): array {
    $stats = $this->subscribersCountsController->getGlobalStatusStatisticsCount();
    $counts = array_fill_keys(self::$supportedStatuses, 0);
    $counts['trash'] = 0;
    foreach (self::$supportedStatuses as $status) {
      $counts[$status] = (int)($stats[$status] ?? 0);
    }
    $counts['trash'] = (int)($stats['trash'] ?? 0);
    return $counts;
  }

  /**
   * The current group's count read from the precomputed buckets — identical to
   * getCount($definition). The one case the buckets can't reproduce exactly is
   * the "all" tab under a segment filter, where members pending in the list
   * fall into no status bucket, so that defers to getCount.
   *
   * @param array<string, int> $counts
   */
  private function countForCurrentGroup(ListingDefinition $definition, array $counts, bool $consolidated): int {
    $group = $definition->getGroup() ?: 'all';
    if ($group === 'all') {
      // When the counts come from the consolidated scan or the per-segment
      // statistics cache, the status buckets partition the non-deleted members
      // exactly, so their sum is the "all" total — no extra query needed.
      if ($consolidated || $this->canUseSegmentStatisticsCache($definition)) {
        $total = 0;
        foreach (self::$supportedStatuses as $status) {
          $total += $counts[$status];
        }
        return $total;
      }
      return $this->getCount($definition);
    }
    return array_key_exists($group, $counts) ? $counts[$group] : $this->getCount($definition);
  }

  /**
   * @param array<string, int> $counts
   * @return array<int, array<string, mixed>>
   */
  private function formatGroups(array $counts): array {
    $totalCount = 0;
    foreach (self::$supportedStatuses as $status) {
      $totalCount += $counts[$status];
    }

    return [
      [
        'name' => 'all',
        'label' => __('All', 'mailpoet'),
        'count' => $totalCount,
      ],
      [
        'name' => SubscriberEntity::STATUS_SUBSCRIBED,
        'label' => __('Subscribed', 'mailpoet'),
        'count' => $counts[SubscriberEntity::STATUS_SUBSCRIBED],
      ],
      [
        'name' => SubscriberEntity::STATUS_UNCONFIRMED,
        'label' => __('Unconfirmed', 'mailpoet'),
        'count' => $counts[SubscriberEntity::STATUS_UNCONFIRMED],
      ],
      [
        'name' => SubscriberEntity::STATUS_UNSUBSCRIBED,
        'label' => __('Unsubscribed', 'mailpoet'),
        'count' => $counts[SubscriberEntity::STATUS_UNSUBSCRIBED],
      ],
      [
        'name' => SubscriberEntity::STATUS_INACTIVE,
        'label' => __('Inactive', 'mailpoet'),
        'count' => $counts[SubscriberEntity::STATUS_INACTIVE],
      ],
      [
        'name' => SubscriberEntity::STATUS_BOUNCED,
        'label' => __('Bounced', 'mailpoet'),
        'count' => $counts[SubscriberEntity::STATUS_BOUNCED],
      ],
      [
        'name' => 'trash',
        'label' => __('Trash', 'mailpoet'),
        'count' => $counts['trash'],
      ],
    ];
  }

  /**
   * Count every status tab in a single grouped scan plus one trash count,
   * instead of a separate COUNT per group. Valid whenever each group's
   * predicate is a plain `s.status` (no static/dynamic segment list filter).
   *
   * @return array<string, int>
   */
  private function getGroupCounts(ListingDefinition $definition): array {
    $counts = array_fill_keys(self::$supportedStatuses, 0);
    $counts['trash'] = 0;

    $statusQuery = $this->createGroupCountQueryBuilder($definition);
    $statusQuery
      ->andWhere('s.deletedAt IS NULL')
      ->select('s.status AS status, COUNT(DISTINCT s.id) AS subscribersCount')
      ->groupBy('s.status');
    foreach ($statusQuery->getQuery()->getResult() as $row) {
      if (array_key_exists($row['status'], $counts)) {
        $counts[$row['status']] = (int)$row['subscribersCount'];
      }
    }

    $trashQuery = $this->createGroupCountQueryBuilder($definition);
    $trashQuery
      ->andWhere('s.deletedAt IS NOT NULL')
      ->select('COUNT(DISTINCT s.id)');
    $counts['trash'] = (int)$trashQuery->getQuery()->getSingleScalarResult();

    return $counts;
  }

  /**
   * Shared FROM + active filters/search for the group counts. Deliberately
   * skips applyGroup so a single query can bucket every status at once.
   */
  private function createGroupCountQueryBuilder(ListingDefinition $definition): QueryBuilder {
    $queryBuilder = clone $this->queryBuilder;
    $this->applyFromClause($queryBuilder);

    $search = $definition->getSearch();
    if ($search !== null && strlen(trim($search)) > 0) {
      $this->applySearch($queryBuilder, $search, $definition->getParameters() ?: []);
    }

    $filters = $definition->getFilters();
    if ($filters) {
      $this->applyFilters($queryBuilder, $filters);
    }

    $parameters = $definition->getParameters();
    if ($parameters) {
      $this->applyParameters($queryBuilder, $parameters);
    }

    return $queryBuilder;
  }

  /**
   * Fallback for static/dynamic segment filters, where a group's predicate is
   * more than a plain `s.status`. One count per group, scoped to the segment.
   *
   * @return array<string, int>
   */
  private function getGroupCountsPerStatus(ListingDefinition $definition): array {
    $counts = array_fill_keys(self::$supportedStatuses, 0);
    $counts['trash'] = 0;
    foreach (array_keys($counts) as $group) {
      $groupDefinition = $group === $definition->getGroup() ? $definition : new ListingDefinition(
        $group,
        $definition->getFilters(),
        $definition->getSearch(),
        $definition->getParameters(),
        $definition->getSortBy(),
        $definition->getSortOrder(),
        $definition->getOffset(),
        $definition->getLimit(),
        $definition->getSelection()
      );
      $counts[$group] = $this->getCount($groupDefinition);
    }
    return $counts;
  }

  private function hasSegmentListFilter(ListingDefinition $definition): bool {
    $filters = $definition->getFilters();
    if (empty($filters['segment']) || $filters['segment'] === self::FILTER_WITHOUT_LIST) {
      return false;
    }
    return $this->entityManager->find(SegmentEntity::class, (int)$filters['segment']) instanceof SegmentEntity;
  }

  public function getFilters(ListingDefinition $definition): array {
    return [
      'segment' => $this->getSegmentFilter($definition),
      'tag' => $this->getTagsFilter($definition),
    ];
  }

  /**
   * @return array<array{label: string, value: string|int}>
   */
  private function getSegmentFilter(ListingDefinition $definition): array {
    $group = $definition->getGroup();

    $subscribersWithoutSegmentStats = $this->subscribersCountsController->getSubscribersWithoutSegmentStatisticsCount();
    $key = $group ?: 'all';
    $subscribersWithoutSegmentCount = $subscribersWithoutSegmentStats[$key];

    $subscribersWithoutSegmentLabel = sprintf(
      // translators: %s is the number of subscribers without a list.
      __('Subscribers without a list (%s)', 'mailpoet'),
      number_format((float)$subscribersWithoutSegmentCount)
    );

    $queryBuilder = clone $this->queryBuilder;
    $queryBuilder
      ->select('s')
      ->from(SegmentEntity::class, 's');
    if ($group !== 'trash') {
      $queryBuilder->andWhere('s.deletedAt IS NULL');
    }

    // format segment list
    $allSubscribersList = [
      'label' => __('All Lists', 'mailpoet'),
      'value' => '',
    ];

    $withoutSegmentList = [
      'label' => $subscribersWithoutSegmentLabel,
      'value' => self::FILTER_WITHOUT_LIST,
    ];

    $segmentList = [];
    foreach ($queryBuilder->getQuery()->getResult() as $segment) {
      $key = $group ?: 'all';
      $count = $this->subscribersCountsController->getSegmentStatisticsCount($segment);
      $subscribersCount = (float)$count[$key];
      // filter segments without subscribers
      if (!$subscribersCount) {
        continue;
      }
      $segmentList[] = [
        'label' => sprintf('%s (%s)', $segment->getName(), number_format($subscribersCount)),
        'value' => $segment->getId(),
      ];
    }

    usort($segmentList, function($a, $b) {
      return strcasecmp($a['label'], $b['label']);
    });

    array_unshift($segmentList, $allSubscribersList, $withoutSegmentList);
    return $segmentList;
  }

  /**
   * @return array<int, array{label: string, value: string|int}>
   */
  private function getTagsFilter(ListingDefinition $definition): array {
    $group = $definition->getGroup();

    $allTagsList = [
      'label' => __('All Tags', 'mailpoet'),
      'value' => '',
    ];

    $status = in_array($group, ['all', 'trash']) ? null : $group;
    $isDeleted = $group === 'trash';
    $tagsStatistics = $this->subscribersCountsController->getTagsStatisticsCount($status, $isDeleted);

    $tagsList = [];
    foreach ($tagsStatistics as $tagStatistics) {
      $tagsList[] = [
        'label' => sprintf('%s (%s)', $tagStatistics['name'], number_format((float)$tagStatistics['subscribersCount'])),
        'value' => $tagStatistics['id'],
      ];
    }

    array_unshift($tagsList, $allTagsList);
    return $tagsList;
  }

  private function getDataForDynamicSegment(ListingDefinition $definition, SegmentEntity $segment) {
    $queryBuilder = clone $this->queryBuilder;
    $sortBy = Helpers::underscoreToCamelCase($definition->getSortBy()) ?: self::DEFAULT_SORT_BY;
    $sortBy = $this->getDynamicSegmentSortBy($sortBy);
    $sortOrder = $this->normalizeSortOrder($definition->getSortOrder());
    $this->applySelectClause($queryBuilder);
    $this->applyFromClause($queryBuilder);

    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subscribersIdsQuery = $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("DISTINCT $subscribersTable.id")
      ->from($subscribersTable);
    $subscribersIdsQuery = $this->applyConstraintsForDynamicSegment($subscribersIdsQuery, $definition, $segment);
    $subscribersIdsQuery->orderBy($this->getDynamicSegmentSortColumn($sortBy, $subscribersTable), $sortOrder);
    if ($sortBy !== 'id') {
      // The page boundary is cut here, so this query needs the same id
      // tiebreaker as applySorting() for pagination to stay stable.
      $subscribersIdsQuery->addOrderBy($this->getDynamicSegmentSortColumn('id', $subscribersTable), $sortOrder);
    }
    $subscribersIdsQuery->setFirstResult($definition->getOffset());
    $subscribersIdsQuery->setMaxResults($definition->getLimit());

    $idsStatement = $subscribersIdsQuery->executeQuery();
    $result = $idsStatement->fetchAll();
    $ids = array_column($result, 'id');
    if (count($ids)) {
      $queryBuilder->andWhere('s.id IN (:subscriberIds)')
        ->setParameter('subscriberIds', $ids);
    } else {
      $queryBuilder->andWhere('0 = 1'); // Don't return any subscribers if no ids found
    }
    $this->applySorting($queryBuilder, $sortBy, $sortOrder);
    return $queryBuilder->getQuery()->getResult();
  }

  private function getDynamicSegmentSortBy(string $sortBy): string {
    $metadata = $this->entityManager->getClassMetadata(SubscriberEntity::class);
    return $metadata->hasField($sortBy) ? $sortBy : self::DEFAULT_SORT_BY;
  }

  private function getDynamicSegmentSortColumn(string $sortBy, string $subscribersTable): string {
    $metadata = $this->entityManager->getClassMetadata(SubscriberEntity::class);
    $column = $metadata->getColumnName($sortBy);
    $connection = $this->entityManager->getConnection();
    return sprintf(
      '%s.%s',
      $connection->quoteIdentifier($subscribersTable),
      $connection->quoteIdentifier($column)
    );
  }

  private function applyConstraintsForDynamicSegment(
    DBALQueryBuilder $subscribersQuery,
    ListingDefinition $definition,
    SegmentEntity $segment
  ) {
    // Apply dynamic segments filters
    $subscribersQuery = $this->dynamicSegmentsFilter->apply($subscribersQuery, $segment);
    // Apply group, search to fetch only necessary ids
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    if ($definition->getSearch()) {
      $search = Helpers::escapeSearch((string)$definition->getSearch());
      $subscribersQuery
        ->andWhere("$subscribersTable.email LIKE :search or $subscribersTable.first_name LIKE :search or $subscribersTable.last_name LIKE :search")
        ->setParameter('search', "%$search%");
    }
    if ($definition->getGroup()) {
      if ($definition->getGroup() === 'trash') {
        $subscribersQuery->andWhere("$subscribersTable.deleted_at IS NOT NULL");
      } else {
        $subscribersQuery->andWhere("$subscribersTable.deleted_at IS NULL");
      }
      if (in_array($definition->getGroup(), self::$supportedStatuses)) {
        $subscribersQuery
          ->andWhere("$subscribersTable.status = :status")
          ->setParameter('status', $definition->getGroup());
      }
    }
    return $subscribersQuery;
  }

  private function getDynamicSegmentFromFilters(ListingDefinition $definition): ?SegmentEntity {
    $filters = $definition->getFilters();
    if (!$filters || !isset($filters['segment'])) {
      return null;
    }
    if ($filters['segment'] === self::FILTER_WITHOUT_LIST) {
      return null;
    }
    $segment = $this->entityManager->find(SegmentEntity::class, (int)$filters['segment']);
    if (!$segment instanceof SegmentEntity) {
      return null;
    }
    return $segment->isStatic() ? null : $segment;
  }
}
