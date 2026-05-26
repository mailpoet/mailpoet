<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Segments\DynamicSegments\FilterHandler;
use MailPoet\Segments\SegmentSubscribersRepository;
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
      $conditions = $this->getEngagementScoreConditions(is_array($engagementScoreInclude) ? $engagementScoreInclude : [$engagementScoreInclude]);
      if ($conditions) {
        $query->andWhere('(' . implode(' OR ', $conditions) . ')');
      }
    }

    $engagementScoreExclude = $filters['engagementScoreExclude'] ?? [];
    if (!empty($engagementScoreExclude)) {
      foreach (is_array($engagementScoreExclude) ? $engagementScoreExclude : [$engagementScoreExclude] as $score) {
        if ($score === self::ENGAGEMENT_SCORE_UNKNOWN) {
          $query->andWhere('s.engagement_score IS NOT NULL');
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
  private function getEngagementScoreConditions(array $scores): array {
    $conditions = [];
    if (in_array(self::ENGAGEMENT_SCORE_UNKNOWN, $scores, true)) {
      $conditions[] = '(s.engagement_score IS NULL)';
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

    if (!in_array($group, [SubscriberEntity::STATUS_SUBSCRIBED, SubscriberEntity::STATUS_UNSUBSCRIBED])) {
      $queryBuilder
        ->andWhere('s.status = :status')
        ->setParameter('status', $group);
      return;
    }

    $segment = $this->definition && array_key_exists('segment', $this->definition->getFilters()) ? $this->entityManager->find(SegmentEntity::class, (int)$this->definition->getFilters()['segment']) : null;
    if (!$segment instanceof SegmentEntity || !$segment->isStatic()) {
      $queryBuilder
        ->andWhere('s.status = :status')
        ->setParameter('status', $group);
      return;
    }

    $operator = $group === SubscriberEntity::STATUS_SUBSCRIBED ? 'AND' : 'OR';
    $queryBuilder
      ->andWhere('(s.status = :status ' . $operator . ' ss.status = :status)')
      ->setParameter('status', $group);
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
      $conditions = [];

      if (in_array(self::ENGAGEMENT_SCORE_UNKNOWN, $engagementScoreInclude, true)) {
        $conditions[] = '(s.engagementScore IS NULL)';
      }
      if (in_array(self::ENGAGEMENT_SCORE_LOW, $engagementScoreInclude, true)) {
        $conditions[] = sprintf(
          '(s.engagementScore < %d)',
          self::ENGAGEMENT_SCORE_LOW_MAX
        );
      }
      if (in_array(self::ENGAGEMENT_SCORE_GOOD, $engagementScoreInclude, true)) {
        $conditions[] = sprintf(
          '(s.engagementScore >= %d AND s.engagementScore < %d)',
          self::ENGAGEMENT_SCORE_GOOD_MIN,
          self::ENGAGEMENT_SCORE_GOOD_MAX
        );
      }
      if (in_array(self::ENGAGEMENT_SCORE_EXCELLENT, $engagementScoreInclude, true)) {
        $conditions[] = sprintf(
          '(s.engagementScore >= %d)',
          self::ENGAGEMENT_SCORE_EXCELLENT_MIN
        );
      }

      if (!empty($conditions)) {
        $queryBuilder->andWhere('(' . implode(' OR ', $conditions) . ')');
      }
    }

    // Filter by engagement score (exclude)
    $engagementScoreExclude = $filters['engagementScoreExclude'] ?? [];
    if (!empty($engagementScoreExclude)) {
      $engagementScoreExclude = is_array($engagementScoreExclude) ? $engagementScoreExclude : [$engagementScoreExclude];

      if (in_array(self::ENGAGEMENT_SCORE_UNKNOWN, $engagementScoreExclude, true)) {
        $queryBuilder->andWhere('s.engagementScore IS NOT NULL');
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
  }

  public function getGroups(ListingDefinition $definition): array {
    $queryBuilder = clone $this->queryBuilder;
    $this->applyFromClause($queryBuilder);

    $groupCounts = [
      SubscriberEntity::STATUS_SUBSCRIBED => 0,
      SubscriberEntity::STATUS_UNCONFIRMED => 0,
      SubscriberEntity::STATUS_UNSUBSCRIBED => 0,
      SubscriberEntity::STATUS_INACTIVE => 0,
      SubscriberEntity::STATUS_BOUNCED => 0,
      'trash' => 0,
    ];
    foreach (array_keys($groupCounts) as $group) {
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
      $groupCounts[$group] = $this->getCount($groupDefinition);
    }

    $trashedCount = $groupCounts['trash'];
    unset($groupCounts['trash']);
    $totalCount = (int)array_sum($groupCounts);

    return [
      [
        'name' => 'all',
        'label' => __('All', 'mailpoet'),
        'count' => $totalCount,
      ],
      [
        'name' => SubscriberEntity::STATUS_SUBSCRIBED,
        'label' => __('Subscribed', 'mailpoet'),
        'count' => $groupCounts[SubscriberEntity::STATUS_SUBSCRIBED],
      ],
      [
        'name' => SubscriberEntity::STATUS_UNCONFIRMED,
        'label' => __('Unconfirmed', 'mailpoet'),
        'count' => $groupCounts[SubscriberEntity::STATUS_UNCONFIRMED],
      ],
      [
        'name' => SubscriberEntity::STATUS_UNSUBSCRIBED,
        'label' => __('Unsubscribed', 'mailpoet'),
        'count' => $groupCounts[SubscriberEntity::STATUS_UNSUBSCRIBED],
      ],
      [
        'name' => SubscriberEntity::STATUS_INACTIVE,
        'label' => __('Inactive', 'mailpoet'),
        'count' => $groupCounts[SubscriberEntity::STATUS_INACTIVE],
      ],
      [
        'name' => SubscriberEntity::STATUS_BOUNCED,
        'label' => __('Bounced', 'mailpoet'),
        'count' => $groupCounts[SubscriberEntity::STATUS_BOUNCED],
      ],
      [
        'name' => 'trash',
        'label' => __('Trash', 'mailpoet'),
        'count' => $trashedCount,
      ],
    ];
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
