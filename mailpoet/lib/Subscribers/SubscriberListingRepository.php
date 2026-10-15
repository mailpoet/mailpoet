<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Listing\ListingDateRangeFilterTrait;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Segments\DynamicSegments\FilterHandler;
use MailPoet\Segments\SegmentDependencyValidator;
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
  use ListingDateRangeFilterTrait;

  public const FILTER_WITHOUT_LIST = 'without-list';

  const DEFAULT_SORT_BY = 'createdAt';

  public const ENGAGEMENT_SCORE_UNKNOWN = 'unknown';
  public const ENGAGEMENT_SCORE_DORMANT = 'dormant';
  public const ENGAGEMENT_SCORE_LOW = 'low';
  public const ENGAGEMENT_SCORE_GOOD = 'good';
  public const ENGAGEMENT_SCORE_EXCELLENT = 'excellent';
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

  private static $supportedSources = [
    Source::FORM,
    Source::IMPORTED,
    Source::ADMINISTRATOR,
    Source::API,
    Source::WORDPRESS_USER,
    Source::WORDPRESS_USER_DELETED,
    Source::WOOCOMMERCE_USER,
    Source::WOOCOMMERCE_CHECKOUT,
    Source::UNKNOWN,
  ];

  /** @var FilterHandler */
  private $dynamicSegmentsFilter;

  /** @var EntityManager */
  private $entityManager;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

  /** @var SubscribersCountsController */
  private $subscribersCountsController;

  /** @var SegmentDependencyValidator */
  private $segmentDependencyValidator;

  /** @var null | ListingDefinition */
  private $definition = null;

  public function __construct(
    EntityManager $entityManager,
    FilterHandler $dynamicSegmentsFilter,
    SegmentSubscribersRepository $segmentSubscribersRepository,
    SubscribersCountsController $subscribersCountsController,
    SegmentDependencyValidator $segmentDependencyValidator
  ) {
    parent::__construct($entityManager);
    $this->dynamicSegmentsFilter = $dynamicSegmentsFilter;
    $this->entityManager = $entityManager;
    $this->segmentSubscribersRepository = $segmentSubscribersRepository;
    $this->subscribersCountsController = $subscribersCountsController;
    $this->segmentDependencyValidator = $segmentDependencyValidator;
  }

  public function getData(ListingDefinition $definition): array {
    $this->definition = $definition;
    if (!$this->hasDynamicSegmentFilter($definition)) {
      return parent::getData($definition);
    }
    return $this->getDataForDynamicSegment($definition);
  }

  public function getCount(ListingDefinition $definition): int {
    $this->definition = $definition;
    if (!$this->hasDynamicSegmentFilter($definition)) {
      return parent::getCount($definition);
    }
    $query = $this->createDbalSubscribersQuery('COUNT(DISTINCT s.id)');
    $this->applyDbalListingConstraints($query, $definition, false);
    return $this->toInt($query->executeQuery()->fetchOne());
  }

  public function getActionableIds(ListingDefinition $definition): array {
    $this->definition = $definition;
    $ids = $definition->getSelection();
    if (!empty($ids)) {
      return $ids;
    }
    if (!$this->hasDynamicSegmentFilter($definition)) {
      return parent::getActionableIds($definition);
    }
    $query = $this->createDbalSubscribersQuery('DISTINCT s.id');
    $this->applyDbalListingConstraints($query, $definition, false);
    return $query->executeQuery()->fetchFirstColumn();
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
    $query = $this->createDbalSubscribersQuery('DISTINCT s.id');
    // A dynamic list never excluded trashed subscribers when no group was given; keep that.
    $this->applyDbalListingConstraints($query, $definition, !$this->hasDynamicSegmentFilter($definition));
    // Whatever the group or filters say, a confirmation email never goes to a confirmed subscriber.
    $query->andWhere(sprintf("s.status = '%s'", SubscriberEntity::STATUS_UNCONFIRMED));
    return ['query' => $query, 'id_column' => 's.id'];
  }

  private function createDbalSubscribersQuery(string $select): DBALQueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager->getConnection()->createQueryBuilder()
      ->select($select)
      ->from($subscribersTable, 's');
  }

  /**
   * The SQL twin of the DQL listing constraints, always over `<subscribers> s`.
   *
   * @param bool $excludeTrashedWithoutGroup The DQL listing applies no deleted_at
   *   condition when there is no group; the bulk resend always did.
   */
  private function applyDbalListingConstraints(DBALQueryBuilder $query, ListingDefinition $definition, bool $excludeTrashedWithoutGroup): void {
    $group = $definition->getGroup();
    if ($group === 'trash') {
      $query->andWhere('s.deleted_at IS NOT NULL');
    } elseif ($group || $excludeTrashedWithoutGroup) {
      $query->andWhere('s.deleted_at IS NULL');
    }

    $search = $definition->getSearch();
    if ($search && strlen(trim($search)) > 0) {
      $search = Helpers::escapeSearch($search);
      $query
        ->andWhere('(s.email LIKE :search OR s.first_name LIKE :search OR s.last_name LIKE :search)')
        ->setParameter('search', "%$search%");
    }

    $filters = $this->normalizeFilters($definition->getFilters() ?: []);
    $hasListJoin = $this->applyDbalSegmentFilters($query, $filters);
    $this->applyDbalTagFilters($query, $filters);

    // Under a single static list the group uses the per-list status, as in applyGroup().
    if ($group && in_array($group, self::$supportedStatuses, true)) {
      $query->andWhere($this->getStatusPredicate($group, $hasListJoin));
    }

    if (isset($filters['minUpdatedAt']) && $filters['minUpdatedAt'] instanceof \DateTimeInterface) {
      $query->andWhere('s.updated_at >= :updated_at')
        ->setParameter('updated_at', $filters['minUpdatedAt']->format('Y-m-d H:i:s'), ParameterType::STRING);
    }

    if (!empty($filters['status_in'])) {
      if ($hasListJoin) {
        $query->andWhere($this->getStatusInPredicate($this->getStringList($filters, 'status_in'), true));
      } else {
        $query->andWhere('s.status IN (:status_in)')
          ->setParameter('status_in', $filters['status_in'], ArrayParameterType::STRING);
      }
    }

    if (!empty($filters['status_not_in'])) {
      if ($hasListJoin) {
        $query->andWhere('NOT ' . $this->getStatusInPredicate($this->getStringList($filters, 'status_not_in'), true));
      } else {
        $query->andWhere('s.status NOT IN (:status_not_in)')
          ->setParameter('status_not_in', $filters['status_not_in'], ArrayParameterType::STRING);
      }
    }

    if (!empty($filters['source_in'])) {
      $query->andWhere('s.source IN (:source_in)')
        ->setParameter('source_in', $filters['source_in'], ArrayParameterType::STRING);
    }
    if (!empty($filters['source_not_in'])) {
      $query->andWhere('s.source NOT IN (:source_not_in)')
        ->setParameter('source_not_in', $filters['source_not_in'], ArrayParameterType::STRING);
    }

    $this->applyDbalScoreAndDateFilters($query, $filters);

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
      if (array_intersect([self::ENGAGEMENT_SCORE_UNKNOWN, self::ENGAGEMENT_SCORE_DORMANT], $engagementScoreInclude)) {
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
      if (array_intersect([self::ENGAGEMENT_SCORE_UNKNOWN, self::ENGAGEMENT_SCORE_DORMANT], $engagementScoreExclude)) {
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
   * @param array $filters Normalized filters.
   * @return bool True when the single static list join (alias ss) was added.
   */
  private function applyDbalSegmentFilters(DBALQueryBuilder $query, array $filters): bool {
    $in = $this->getIdList($filters, 'segment_in');
    $notIn = $this->getIdList($filters, 'segment_not_in');
    if (!$in && !$notIn) {
      return false;
    }

    $single = $this->getSingleSegment($in, $notIn);
    if ($single instanceof SegmentEntity && $single->isStatic()) {
      $subscriberSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
      $query->join('s', $subscriberSegmentsTable, 'ss', 'ss.subscriber_id = s.id AND ss.segment_id = :segment_id')
        ->setParameter('segment_id', $single->getId(), ParameterType::INTEGER);
      return true;
    }

    $subscriberSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $membershipSql = "s.id %s (SELECT ssm.subscriber_id FROM $subscriberSegmentsTable ssm WHERE ssm.segment_id IN (:%s))";
    $dynamicIndex = 0;

    $partitionedIn = $this->partitionSegmentIds($in);
    $conditions = [];
    if ($partitionedIn['static']) {
      $conditions[] = sprintf($membershipSql, 'IN', 'segment_in');
      $query->setParameter('segment_in', $partitionedIn['static'], ArrayParameterType::INTEGER);
    }
    if ($partitionedIn['without']) {
      $conditions[] = $this->segmentSubscribersRepository->getWithoutSegmentSqlPredicate();
    }
    foreach ($partitionedIn['dynamic'] as $segmentId) {
      $segment = $this->entityManager->find(SegmentEntity::class, $segmentId);
      if (!$segment instanceof SegmentEntity) {
        continue;
      }
      $conditions[] = $this->getDynamicSegmentPredicate($query, $segment, 'IN', $dynamicIndex++);
    }
    if ($conditions) {
      $query->andWhere('(' . implode(' OR ', $conditions) . ')');
    }

    $partitionedNotIn = $this->partitionSegmentIds($notIn);
    if ($partitionedNotIn['static']) {
      $query->andWhere(sprintf($membershipSql, 'NOT IN', 'segment_not_in'))
        ->setParameter('segment_not_in', $partitionedNotIn['static'], ArrayParameterType::INTEGER);
    }
    if ($partitionedNotIn['without']) {
      $query->andWhere($this->segmentSubscribersRepository->getWithoutSegmentSqlPredicate(true));
    }
    foreach ($partitionedNotIn['dynamic'] as $segmentId) {
      $segment = $this->entityManager->find(SegmentEntity::class, $segmentId);
      if (!$segment instanceof SegmentEntity || !$this->canExcludeDynamicSegment($segment)) {
        // Fail closed: return nothing rather than keep members of a list we cannot evaluate.
        $query->andWhere('1 = 0');
        continue;
      }
      $query->andWhere($this->getDynamicSegmentPredicate($query, $segment, 'NOT IN', $dynamicIndex++));
    }
    return false;
  }

  /**
   * A filter whose plugin is missing yields an empty set, and excluding an empty
   * set would keep every subscriber. For inclusion FilterHandler already returns
   * no rows in that case.
   */
  private function canExcludeDynamicSegment(SegmentEntity $segment): bool {
    return !$this->segmentDependencyValidator->getMissingPluginsBySegment($segment);
  }

  private function getDynamicSegmentPredicate(DBALQueryBuilder $outer, SegmentEntity $segment, string $operator, int $index): string {
    // Not wrapped in a derived table: that extra materialisation made the page 2-4x slower on 100k subscribers.
    return sprintf('s.id %s (%s)', $operator, $this->getDynamicSegmentInSql($outer, $segment, $index));
  }

  /**
   * The SQL of the segment's subscriber ids, with its parameters copied onto
   * $outer under names prefixed by the index so two segments never collide.
   */
  private function getDynamicSegmentInSql(DBALQueryBuilder $outer, SegmentEntity $segment, int $index): string {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $sub = $this->entityManager->getConnection()->createQueryBuilder()
      ->select("DISTINCT $subscribersTable.id")
      ->from($subscribersTable);
    $sub = $this->dynamicSegmentsFilter->apply($sub, $segment);
    $sql = $sub->getSQL();
    $types = $sub->getParameterTypes();
    foreach ($sub->getParameters() as $name => $value) {
      $name = (string)$name;
      $unique = "ds{$index}_{$name}";
      // DBAL has no parameter rename API. The lookahead stops :products1 from matching
      // inside :products10. Assumes filter SQL has no colon inside quoted literals.
      $sql = (string)preg_replace('/:' . preg_quote($name, '/') . '(?![A-Za-z0-9_])/', ':' . $unique, $sql);
      $outer->setParameter($unique, $value, $types[$name] ?? null);
    }
    return $sql;
  }

  /**
   * @param array $filters Normalized filters.
   */
  private function applyDbalTagFilters(DBALQueryBuilder $query, array $filters): void {
    $in = $this->getIdList($filters, 'tag_in');
    $notIn = $this->getIdList($filters, 'tag_not_in');
    $subscriberTagsTable = $this->entityManager->getClassMetadata(SubscriberTagEntity::class)->getTableName();
    if (count($in) === 1 && !$notIn) {
      $query->join('s', $subscriberTagsTable, 'st', 'st.subscriber_id = s.id AND st.tag_id = :tag_id')
        ->setParameter('tag_id', $in[0], ParameterType::INTEGER);
      return;
    }
    if ($in) {
      $query->andWhere("s.id IN (SELECT sti.subscriber_id FROM $subscriberTagsTable sti WHERE sti.tag_id IN (:tag_in))")
        ->setParameter('tag_in', $in, ArrayParameterType::INTEGER);
    }
    if ($notIn) {
      $query->andWhere("s.id NOT IN (SELECT stn.subscriber_id FROM $subscriberTagsTable stn WHERE stn.tag_id IN (:tag_not_in))")
        ->setParameter('tag_not_in', $notIn, ArrayParameterType::INTEGER);
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
    return sprintf('(s.engagement_score IS NULL AND NOT %s)', $this->getDbalDormantSentCountsCondition());
  }

  private function getDbalDormantEngagementScoreCondition(): string {
    return sprintf('(s.engagement_score IS NULL AND %s)', $this->getDbalDormantSentCountsCondition());
  }

  private function getDbalDormantSentCountsCondition(): string {
    $lifetimeSentCount = $this->getDbalSentCountSubquery('s.id');
    $recentSentCount = $this->getDbalSentCountSubquery('s.id', 'engagement_score_recent_cutoff');
    return sprintf(
      '(%s >= %d AND %s < %d)',
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
    $queryBuilder->select("PARTIAL s.{id,email,firstName,lastName,status,createdAt,deletedAt,updatedAt,countConfirmations,wpUserId,isWoocommerceUser,engagementScore,lastSubscribedAt,source}");
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

    // Under a single static list a member's status is the per-list status, so
    // a member unsubscribed from THIS list belongs in the unsubscribed tab, not
    // in inactive/unconfirmed/bounced. This keeps the listed rows in step with
    // the per-list statistics tab counts.
    $queryBuilder->andWhere($this->getStatusPredicate($group, (bool)$this->getStaticSegmentFromDefinition()));
  }

  /**
   * Predicate for one status, aliased on `s` (and `ss` for the static list join).
   * Works in both DQL and SQL. The status is checked against the whitelist and
   * inlined, so it never carries user input.
   *
   * With a single static list join (`ss`), subscribed/unsubscribed combine the
   * global and per-list status; every other status also requires the member to
   * be subscribed to that list. ss.status is only ever subscribed/unsubscribed,
   * so that is an equality (an index seek) rather than != unsubscribed, and it
   * mirrors the count query in SegmentSubscribersRepository.
   */
  private function getStatusPredicate(string $status, bool $hasStaticListJoin): string {
    if (!in_array($status, self::$supportedStatuses, true)) {
      return '1 = 0';
    }
    if (!$hasStaticListJoin) {
      return "s.status = '$status'";
    }
    if ($status === SubscriberEntity::STATUS_SUBSCRIBED) {
      return "(s.status = '$status' AND ss.status = '$status')";
    }
    if ($status === SubscriberEntity::STATUS_UNSUBSCRIBED) {
      return "(s.status = '$status' OR ss.status = '$status')";
    }
    return "(s.status = '$status' AND ss.status = '" . SubscriberEntity::STATUS_SUBSCRIBED . "')";
  }

  /**
   * @param string[] $statuses
   */
  private function getStatusInPredicate(array $statuses, bool $hasStaticListJoin): string {
    $predicates = array_map(function(string $status) use ($hasStaticListJoin): string {
      return $this->getStatusPredicate($status, $hasStaticListJoin);
    }, $statuses);
    return '(' . implode(' OR ', $predicates) . ')';
  }

  private function getStaticSegmentFromDefinition(): ?SegmentEntity {
    if (!$this->definition) {
      return null;
    }
    $segment = $this->getSingleSegmentFromDefinition($this->definition);
    return ($segment instanceof SegmentEntity && $segment->isStatic()) ? $segment : null;
  }

  protected function applySearch(QueryBuilder $queryBuilder, string $search, array $parameters = []) {
    $search = Helpers::escapeSearch($search);
    $queryBuilder
      ->andWhere('s.email LIKE :search or s.firstName LIKE :search or s.lastName LIKE :search')
      ->setParameter('search', "%$search%");
  }

  protected function applyFilters(QueryBuilder $queryBuilder, array $filters) {
    $filters = $this->normalizeFilters($filters);
    $hasListJoin = $this->applySegmentFilters($queryBuilder, $filters);
    $this->applyTagFilters($queryBuilder, $filters);

    // filtering by minimal updated at
    if (isset($filters['minUpdatedAt']) && $filters['minUpdatedAt'] instanceof \DateTimeInterface) {
      $queryBuilder->andWhere('s.updatedAt >= :updatedAt')
        ->setParameter('updatedAt', $filters['minUpdatedAt']);
    }

    // With a single static list the status is the per-list one (see getStatusPredicate).
    // With 2+ lists or any segment_not_in there is no single list to take it from,
    // so status is the global s.status.
    if (!empty($filters['status_in'])) {
      if ($hasListJoin) {
        $queryBuilder->andWhere($this->getStatusInPredicate($this->getStringList($filters, 'status_in'), true));
      } else {
        $queryBuilder->andWhere('s.status IN (:statusIn)')
          ->setParameter('statusIn', $filters['status_in']);
      }
    }

    if (!empty($filters['status_not_in'])) {
      if ($hasListJoin) {
        $queryBuilder->andWhere('NOT ' . $this->getStatusInPredicate($this->getStringList($filters, 'status_not_in'), true));
      } else {
        $queryBuilder->andWhere('s.status NOT IN (:statusNotIn)')
          ->setParameter('statusNotIn', $filters['status_not_in']);
      }
    }

    if (!empty($filters['source_in'])) {
      $queryBuilder->andWhere('s.source IN (:sourceIn)')
        ->setParameter('sourceIn', $filters['source_in']);
    }
    if (!empty($filters['source_not_in'])) {
      $queryBuilder->andWhere('s.source NOT IN (:sourceNotIn)')
        ->setParameter('sourceNotIn', $filters['source_not_in']);
    }

    $this->applyDqlScoreFilters($queryBuilder, $filters);
    $this->applyDateRangeFilter($queryBuilder, 's.createdAt', $filters, 'created_from', 'created_to');
    $this->applyDateRangeFilter($queryBuilder, 's.lastSubscribedAt', $filters, 'subscribed_from', 'subscribed_to');

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
      if (array_intersect([self::ENGAGEMENT_SCORE_UNKNOWN, self::ENGAGEMENT_SCORE_DORMANT], $engagementScoreInclude)) {
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
      if (array_intersect([self::ENGAGEMENT_SCORE_UNKNOWN, self::ENGAGEMENT_SCORE_DORMANT], $engagementScoreExclude)) {
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
   * @param array $filters Normalized filters.
   * @return array<int|string>
   */
  private function getIdList(array $filters, string $key): array {
    $values = $filters[$key] ?? [];
    /** @var array<int|string> $ids */
    $ids = is_array($values) ? array_values($values) : [];
    return $ids;
  }

  /**
   * @param array $filters Normalized filters.
   * @return string[]
   */
  private function getStringList(array $filters, string $key): array {
    $values = $filters[$key] ?? [];
    /** @var string[] $strings */
    $strings = is_array($values) ? array_values($values) : [];
    return $strings;
  }

  /**
   * @param array $filters Normalized filters.
   * @return bool True when the single static list join (alias ss) was added.
   */
  private function applySegmentFilters(QueryBuilder $queryBuilder, array $filters): bool {
    $in = $this->getIdList($filters, 'segment_in');
    $notIn = $this->getIdList($filters, 'segment_not_in');
    if (!$in && !$notIn) {
      return false;
    }

    $single = $this->getSingleSegment($in, $notIn);
    if ($single instanceof SegmentEntity && $single->isStatic()) {
      $queryBuilder->join('s.subscriberSegments', 'ss', Join::WITH, 'ss.segment = :ssSegment')
        ->setParameter('ssSegment', $single->getId());
      return true;
    }

    $partitionedIn = $this->partitionSegmentIds($in);
    $conditions = [];
    if ($partitionedIn['static']) {
      $conditions[] = 's.id IN (' . $this->getMembershipDql(SubscriberSegmentEntity::class, 'ssIn', 'segment', 'segmentIn') . ')';
      $queryBuilder->setParameter('segmentIn', $partitionedIn['static']);
    }
    if ($partitionedIn['without']) {
      $conditions[] = $this->segmentSubscribersRepository->getWithoutSegmentDqlPredicate();
    }
    // Dynamic segments never reach DQL: hasDynamicSegmentFilter() routes them to the SQL path.
    if ($conditions) {
      $queryBuilder->andWhere('(' . implode(' OR ', $conditions) . ')');
    }

    $partitionedNotIn = $this->partitionSegmentIds($notIn);
    if ($partitionedNotIn['static']) {
      $queryBuilder->andWhere('s.id NOT IN (' . $this->getMembershipDql(SubscriberSegmentEntity::class, 'ssNotIn', 'segment', 'segmentNotIn') . ')')
        ->setParameter('segmentNotIn', $partitionedNotIn['static']);
    }
    if ($partitionedNotIn['without']) {
      $queryBuilder->andWhere($this->segmentSubscribersRepository->getWithoutSegmentDqlPredicate(true));
    }
    return false;
  }

  /**
   * @param array $filters Normalized filters.
   */
  private function applyTagFilters(QueryBuilder $queryBuilder, array $filters): void {
    $in = $this->getIdList($filters, 'tag_in');
    $notIn = $this->getIdList($filters, 'tag_not_in');
    if (count($in) === 1 && !$notIn) {
      $queryBuilder->join('s.subscriberTags', 'st', Join::WITH, 'st.tag = :stTag')
        ->setParameter('stTag', $in[0]);
      return;
    }
    if ($in) {
      $queryBuilder->andWhere('s.id IN (' . $this->getMembershipDql(SubscriberTagEntity::class, 'stIn', 'tag', 'tagIn') . ')')
        ->setParameter('tagIn', $in);
    }
    if ($notIn) {
      $queryBuilder->andWhere('s.id NOT IN (' . $this->getMembershipDql(SubscriberTagEntity::class, 'stNotIn', 'tag', 'tagNotIn') . ')')
        ->setParameter('tagNotIn', $notIn);
    }
  }

  /**
   * DQL subquery with the ids of subscribers that have a membership row for
   * any of the ids in the given parameter. The parameter is set on the outer query.
   */
  private function getMembershipDql(string $entityClass, string $alias, string $association, string $parameter): string {
    return $this->entityManager->createQueryBuilder()
      ->select("IDENTITY($alias.subscriber)")
      ->from($entityClass, $alias)
      ->where("$alias.$association IN (:$parameter)")
      ->getDQL();
  }

  /**
   * @param array<int|string> $ids
   * @return array{static: int[], dynamic: int[], without: bool}
   */
  private function partitionSegmentIds(array $ids): array {
    $result = ['static' => [], 'dynamic' => [], 'without' => false];
    foreach ($ids as $id) {
      if ($id === self::FILTER_WITHOUT_LIST) {
        $result['without'] = true;
        continue;
      }
      $segment = $this->entityManager->find(SegmentEntity::class, (int)$id);
      // An unknown id is treated as static: it matches nobody.
      if ($segment instanceof SegmentEntity && !$segment->isStatic()) {
        $result['dynamic'][] = (int)$id;
      } else {
        $result['static'][] = (int)$id;
      }
    }
    return $result;
  }

  /**
   * Maps the legacy and the new filter keys onto one shape.
   *
   * Legacy `segment`, `tag`, `statusInclude` and `statusExclude` are merged into
   * `segment_in`, `tag_in`, `status_in` and `status_not_in`. Values are cast and
   * invalid or unknown ones are dropped, as are empty keys. Dropping silently is
   * on purpose: the public API (MailPoet\API\MP\v1\Subscribers) has always
   * ignored bad values, while the REST endpoint rejects them before they get here.
   * Any other key (dates, engagement score...) passes through untouched.
   *
   * @param array $filters
   * @return array
   */
  private function normalizeFilters(array $filters): array {
    $normalized = $filters;
    unset($normalized['segment'], $normalized['tag'], $normalized['statusInclude'], $normalized['statusExclude']);

    $legacySegment = $this->normalizeLegacySegment($filters['segment'] ?? null);
    $legacyTag = $this->normalizeLegacyTag($filters['tag'] ?? null);

    $sets = [
      'segment_in' => array_merge($this->normalizeIds($filters['segment_in'] ?? [], true), $legacySegment),
      'segment_not_in' => $this->normalizeIds($filters['segment_not_in'] ?? [], true),
      'tag_in' => array_merge($this->normalizeIds($filters['tag_in'] ?? [], false), $legacyTag),
      'tag_not_in' => $this->normalizeIds($filters['tag_not_in'] ?? [], false),
      'status_in' => $this->combineStatusInclusions(
        $this->sanitizeStatusFilter($filters['status_in'] ?? []),
        $this->sanitizeStatusFilter($filters['statusInclude'] ?? [])
      ),
      'status_not_in' => array_merge($this->sanitizeStatusFilter($filters['status_not_in'] ?? []), $this->sanitizeStatusFilter($filters['statusExclude'] ?? [])),
      'source_in' => $this->sanitizeSourceFilter($filters['source_in'] ?? []),
      'source_not_in' => $this->sanitizeSourceFilter($filters['source_not_in'] ?? []),
    ];
    foreach ($sets as $key => $values) {
      $values = array_values(array_unique($values));
      if ($values) {
        $normalized[$key] = $values;
      } else {
        unset($normalized[$key]);
      }
    }
    return $normalized;
  }

  /**
   * Both include keys narrow the result (AND), as they did before they were
   * merged. An empty intersection must match nothing, so it is kept as a single
   * blank status (no subscriber has one) instead of an empty list, which would
   * read as "no filter".
   *
   * @param string[] $statusIn
   * @param string[] $legacyStatusInclude
   * @return string[]
   */
  private function combineStatusInclusions(array $statusIn, array $legacyStatusInclude): array {
    if (!$statusIn || !$legacyStatusInclude) {
      return array_merge($statusIn, $legacyStatusInclude);
    }
    $common = array_values(array_intersect($statusIn, $legacyStatusInclude));
    return $common ?: [''];
  }

  /**
   * The statuses a listing is restricted to once legacy and new keys are combined,
   * or null when it has no status restriction.
   *
   * @param array $filters
   * @return string[]|null
   */
  public function getNormalizedStatusIn(array $filters): ?array {
    $statuses = $this->getStringList($this->normalizeFilters($filters), 'status_in');
    return $statuses ?: null;
  }

  /**
   * A legacy scalar that resolves to no list is ignored, as it always was.
   *
   * @param mixed $segment
   * @return array<int|string>
   */
  private function normalizeLegacySegment($segment): array {
    if ($segment === self::FILTER_WITHOUT_LIST) {
      return [self::FILTER_WITHOUT_LIST];
    }
    if (!is_scalar($segment)) {
      return [];
    }
    $id = (int)$segment;
    return $this->entityManager->find(SegmentEntity::class, $id) instanceof SegmentEntity ? [$id] : [];
  }

  /**
   * @param mixed $tag
   * @return int[]
   */
  private function normalizeLegacyTag($tag): array {
    if (!is_scalar($tag)) {
      return [];
    }
    $id = (int)$tag;
    return $this->entityManager->find(TagEntity::class, $id) instanceof TagEntity ? [$id] : [];
  }

  /**
   * @param mixed $values
   * @return array<int|string>
   */
  private function normalizeIds($values, bool $allowWithoutList): array {
    $values = is_array($values) ? $values : [$values];
    $ids = [];
    foreach ($values as $value) {
      if ($allowWithoutList && $value === self::FILTER_WITHOUT_LIST) {
        $ids[] = $value;
      } elseif ((is_int($value) || (is_string($value) && ctype_digit($value))) && (int)$value > 0) {
        $ids[] = (int)$value;
      }
    }
    return $ids;
  }

  /**
   * @param mixed $sources
   * @return string[]
   */
  private function sanitizeSourceFilter($sources): array {
    $sources = is_array($sources) ? $sources : [$sources];
    return array_values(array_filter($sources, function($source): bool {
      return is_string($source) && in_array($source, self::$supportedSources, true);
    }));
  }

  /**
   * The only list a definition filters by, when it is exactly one and there is
   * no exclusion. Legacy `segment` and `segment_in` give the same answer.
   *
   * @param array<int|string> $in
   * @param array<int|string> $notIn
   */
  private function getSingleSegment(array $in, array $notIn): ?SegmentEntity {
    if (count($in) !== 1 || $notIn || !is_int($in[0])) {
      return null;
    }
    $segment = $this->entityManager->find(SegmentEntity::class, $in[0]);
    return $segment instanceof SegmentEntity ? $segment : null;
  }

  private function getSingleSegmentFromDefinition(ListingDefinition $definition): ?SegmentEntity {
    $ids = $this->getSegmentIdsFromFilters($definition);
    return $this->getSingleSegment($ids['in'], $ids['not_in']);
  }

  /**
   * @return array{in: array<int|string>, not_in: array<int|string>}
   */
  private function getSegmentIdsFromFilters(ListingDefinition $definition): array {
    $filters = $this->normalizeFilters($definition->getFilters() ?: []);
    return [
      'in' => $this->getIdList($filters, 'segment_in'),
      'not_in' => $this->getIdList($filters, 'segment_not_in'),
    ];
  }

  /**
   * The id (as string) of the one list the definition filters by, if it is exactly one.
   */
  public function getSingleListFilter(ListingDefinition $definition): ?string {
    $ids = $this->getSegmentIdsFromFilters($definition);
    if (count($ids['in']) !== 1 || $ids['not_in']) {
      return null;
    }
    return (string)$ids['in'][0];
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
    return sprintf(
      '(s.engagementScore IS NULL AND NOT %s)',
      $this->getDqlDormantSentCountsCondition($aliasPrefix . 'Unknown')
    );
  }

  private function getDqlDormantEngagementScoreCondition(string $aliasPrefix): string {
    return sprintf(
      '(s.engagementScore IS NULL AND %s)',
      $this->getDqlDormantSentCountsCondition($aliasPrefix)
    );
  }

  private function getDqlDormantSentCountsCondition(string $aliasPrefix): string {
    $lifetimeSentCount = $this->getDqlSentCountSubquery($aliasPrefix . 'LifetimeStats');
    $recentSentCount = $this->getDqlSentCountSubquery($aliasPrefix . 'RecentStats', 'engagementScoreRecentCutoff');
    return sprintf(
      '(%s >= %d AND %s < %d)',
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

  private const SCORE_FILTERS = [
    'score_gt' => '>',
    'score_lt' => '<',
    'score_min' => '>=',
    'score_max' => '<=',
  ];

  private function applyDqlScoreFilters(QueryBuilder $queryBuilder, array $filters): void {
    foreach (self::SCORE_FILTERS as $key => $operator) {
      if (isset($filters[$key]) && is_numeric($filters[$key])) {
        $queryBuilder
          ->andWhere("s.engagementScore $operator :$key")
          ->setParameter($key, (float)$filters[$key]);
      }
    }
  }

  private function applyDbalScoreAndDateFilters(DBALQueryBuilder $query, array $filters): void {
    foreach (self::SCORE_FILTERS as $key => $operator) {
      if (isset($filters[$key]) && is_numeric($filters[$key])) {
        $query->andWhere("s.engagement_score $operator :$key")
          ->setParameter($key, (float)$filters[$key]);
      }
    }
    // Same whole-day bounds as ListingDateRangeFilterTrait.
    $dates = [
      ['created_from', 's.created_at', '>=', ' 00:00:00'],
      ['created_to', 's.created_at', '<=', ' 23:59:59'],
      ['subscribed_from', 's.last_subscribed_at', '>=', ' 00:00:00'],
      ['subscribed_to', 's.last_subscribed_at', '<=', ' 23:59:59'],
    ];
    foreach ($dates as [$key, $column, $operator, $time]) {
      $value = $filters[$key] ?? null;
      if (is_string($value) && $value !== '') {
        $query->andWhere("$column $operator :date_$key")
          ->setParameter("date_$key", $value . $time, ParameterType::STRING);
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
    return $this->formatGroups($this->getGroupCountsForDefinition($definition));
  }

  /**
   * The count of the current group plus the All and Trash tab counts, all under
   * the active filters and search. Unfiltered and single-list requests are
   * answered from the cron-warmed statistics. Anything narrower runs live
   * counts, reusing the current group’s count when it is All or Trash.
   *
   * @return array{count: int, groups: array<int, array<string, mixed>>}
   */
  public function getCountAndAllTrashGroups(ListingDefinition $definition): array {
    $group = $definition->getGroup() ?: 'all';
    $cached = $this->getCachedGroupCounts($definition);
    if ($cached !== null) {
      $all = 0;
      foreach (self::$supportedStatuses as $status) {
        $all += $cached[$status];
      }
      $trash = $cached['trash'];
      if ($group === 'all') {
        $count = $all;
      } elseif ($group === 'trash') {
        $count = $trash;
      } else {
        $count = array_key_exists($group, $cached) ? $cached[$group] : $this->getCount($definition);
      }
    } else {
      $count = $this->getCount($definition);
      $all = $group === 'all' ? $count : $this->getCount($this->withGroup($definition, 'all'));
      $trash = $group === 'trash' ? $count : $this->getCount($this->withGroup($definition, 'trash'));
    }

    return [
      'count' => $count,
      'groups' => [
        ['name' => 'all', 'label' => __('All', 'mailpoet'), 'count' => $all],
        ['name' => 'trash', 'label' => __('Trash', 'mailpoet'), 'count' => $trash],
      ],
    ];
  }

  private function withGroup(ListingDefinition $definition, string $group): ListingDefinition {
    return new ListingDefinition(
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
  }

  /**
   * The status-tab counts. Cheapest first:
   *
   *   1. Global cache: the unfiltered “All Lists” view (cron-warmed).
   *   2. Per-segment cache: a plain single-list filter and nothing else.
   *   3. Live: anything narrower. A grouped scan on the non-segment path, or
   *      one count per status when a segment redefines the buckets (a static
   *      segment uses the per-list status, a dynamic one an id subquery).
   *
   * @return array<string, int>
   */
  private function getGroupCountsForDefinition(ListingDefinition $definition): array {
    $cached = $this->getCachedGroupCounts($definition);
    if ($cached !== null) {
      return $cached;
    }
    return $this->hasSegmentListFilter($definition)
      ? $this->getGroupCountsPerStatus($definition)
      : $this->getGroupCounts($definition);
  }

  /**
   * Counts from the cron-warmed statistics, or null when the request is
   * narrower than they cover.
   *
   * @return array<string, int>|null
   */
  private function getCachedGroupCounts(ListingDefinition $definition): ?array {
    if (!$this->hasSegmentListFilter($definition)) {
      return $this->canUseGlobalStatisticsCache($definition) ? $this->globalStatisticsToCounts() : null;
    }
    if ($this->canUseSegmentStatisticsCache($definition)) {
      $segment = $this->getSegmentFromDefinition($definition);
      if ($segment instanceof SegmentEntity) {
        return $this->segmentStatisticsToCounts($segment);
      }
    }
    return null;
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
    $filters = $this->normalizeFilters($definition->getFilters() ?: []);
    // Only a lone list (or none) keeps the cache valid; any other key narrows.
    if (count($this->getIdList($filters, 'segment_in')) > 1) {
      return false;
    }
    unset($filters['segment_in']);
    return !$this->hasActiveFilter($filters);
  }

  /**
   * "0" and 0 are real filter values (e.g. score_max=0); only null, '' and
   * empty arrays mean "not set".
   *
   * @param array $filters
   */
  private function hasActiveFilter(array $filters): bool {
    foreach ($filters as $value) {
      if ($value !== null && $value !== '' && $value !== []) {
        return true;
      }
    }
    return false;
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
    return $this->getSingleSegmentFromDefinition($definition);
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
    return !$this->hasActiveFilter($this->normalizeFilters($definition->getFilters() ?: []));
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
      $groupDefinition = $group === $definition->getGroup() ? $definition : $this->withGroup($definition, $group);
      $counts[$group] = $this->getCount($groupDefinition);
    }
    return $counts;
  }

  private function hasSegmentListFilter(ListingDefinition $definition): bool {
    $ids = $this->getSegmentIdsFromFilters($definition);
    // "Without a list" alone has no list to bucket by, so it keeps the grouped scan.
    if ($ids['in'] === [self::FILTER_WITHOUT_LIST] && !$ids['not_in']) {
      return false;
    }
    return (bool)($ids['in'] || $ids['not_in']);
  }

  public function getFilters(ListingDefinition $definition): array {
    return [
      'segment' => $this->getSegmentFilter($definition),
      'tag' => $this->getTagsFilter($definition),
      'status' => $this->getStatusFilter(),
      'source' => $this->getSourceFilter(),
    ];
  }

  /**
   * The counts are global (all non-deleted subscribers), not narrowed by the
   * current filters, so they come from the cached global statistics.
   *
   * @return array<int, array{label: string, value: string}>
   */
  private function getStatusFilter(): array {
    $stats = $this->subscribersCountsController->getGlobalStatusStatisticsCount();
    $labels = [
      SubscriberEntity::STATUS_SUBSCRIBED => __('Subscribed', 'mailpoet'),
      SubscriberEntity::STATUS_UNCONFIRMED => __('Unconfirmed', 'mailpoet'),
      SubscriberEntity::STATUS_UNSUBSCRIBED => __('Unsubscribed', 'mailpoet'),
      SubscriberEntity::STATUS_INACTIVE => __('Inactive', 'mailpoet'),
      SubscriberEntity::STATUS_BOUNCED => __('Bounced', 'mailpoet'),
    ];
    $list = [];
    foreach ($labels as $status => $label) {
      $list[] = [
        'label' => sprintf('%s (%s)', $label, number_format((float)($stats[$status] ?? 0))),
        'value' => $status,
      ];
    }
    return $list;
  }

  /**
   * @return array<int, array{label: string, value: string}>
   */
  private function getSourceFilter(): array {
    $labels = [
      Source::FORM => __('MailPoet subscription form', 'mailpoet'),
      Source::IMPORTED => __('Import', 'mailpoet'),
      Source::ADMINISTRATOR => __('Admin', 'mailpoet'),
      Source::API => __('API', 'mailpoet'),
      Source::WORDPRESS_USER => __('WordPress user sync', 'mailpoet'),
      Source::WORDPRESS_USER_DELETED => __('WordPress user (deleted)', 'mailpoet'),
      Source::WOOCOMMERCE_USER => __('WooCommerce customer sync', 'mailpoet'),
      Source::WOOCOMMERCE_CHECKOUT => __('WooCommerce checkout', 'mailpoet'),
      Source::UNKNOWN => __('Unknown', 'mailpoet'),
    ];
    $list = [];
    foreach ($labels as $source => $label) {
      $list[] = ['label' => $label, 'value' => $source];
    }
    return $list;
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

  private function getDataForDynamicSegment(ListingDefinition $definition) {
    $queryBuilder = clone $this->queryBuilder;
    $sortBy = Helpers::underscoreToCamelCase($definition->getSortBy()) ?: self::DEFAULT_SORT_BY;
    $sortBy = $this->getDynamicSegmentSortBy($sortBy);
    $sortOrder = $this->normalizeSortOrder($definition->getSortOrder());
    $this->applySelectClause($queryBuilder);
    $this->applyFromClause($queryBuilder);

    $subscribersIdsQuery = $this->createDbalSubscribersQuery('DISTINCT s.id');
    $this->applyDbalListingConstraints($subscribersIdsQuery, $definition, false);
    $subscribersIdsQuery->orderBy($this->getDynamicSegmentSortColumn($sortBy), $sortOrder);
    if ($sortBy !== 'id') {
      // The page boundary is cut here, so this query needs the same id
      // tiebreaker as applySorting() for pagination to stay stable.
      $subscribersIdsQuery->addOrderBy($this->getDynamicSegmentSortColumn('id'), $sortOrder);
    }
    $subscribersIdsQuery->setFirstResult($definition->getOffset());
    $subscribersIdsQuery->setMaxResults($definition->getLimit());

    $ids = $subscribersIdsQuery->executeQuery()->fetchFirstColumn();
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

  private function getDynamicSegmentSortColumn(string $sortBy): string {
    $metadata = $this->entityManager->getClassMetadata(SubscriberEntity::class);
    $column = $metadata->getColumnName($sortBy);
    return sprintf('s.%s', $this->entityManager->getConnection()->quoteIdentifier($column));
  }

  /**
   * True when any dynamic segment is included or excluded. Such a definition is
   * resolved with SQL over the subscribers table instead of DQL.
   */
  private function hasDynamicSegmentFilter(ListingDefinition $definition): bool {
    $ids = $this->getSegmentIdsFromFilters($definition);
    return (bool)$this->partitionSegmentIds($ids['in'])['dynamic'] || (bool)$this->partitionSegmentIds($ids['not_in'])['dynamic'];
  }
}
