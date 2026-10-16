<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Listing;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Listing\ListingDateRangeFilterTrait;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Newsletter\Sending\NewsletterReplayMetadata;
use MailPoet\Newsletter\Sending\TimeZoneCampaignScheduler;
use MailPoet\Util\Helpers;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

class NewsletterListingRepository extends ListingRepository {
  use ListingDateRangeFilterTrait;

  /**
   * Filter-only status: a sending email whose latest send is paused. It is not a
   * stored status and is deliberately absent from $supportedStatuses (listing groups).
   */
  public const FILTER_STATUS_PAUSED = 'paused';

  /** Largest number of values a multi-value filter (status, lists) accepts. */
  public const MAX_FILTER_SET_VALUES = 100;

  private static $supportedStatuses = [
    NewsletterEntity::STATUS_DRAFT,
    NewsletterEntity::STATUS_SCHEDULED,
    NewsletterEntity::STATUS_SENDING,
    NewsletterEntity::STATUS_SENT,
    NewsletterEntity::STATUS_ACTIVE,
  ];

  private static $supportedTypes = [
    NewsletterEntity::TYPE_STANDARD,
    NewsletterEntity::TYPE_RE_ENGAGEMENT,
    NewsletterEntity::TYPE_WELCOME,
    NewsletterEntity::TYPE_AUTOMATIC,
    NewsletterEntity::TYPE_NOTIFICATION,
    NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
  ];

  /**
   * @return string[]
   */
  public static function getSupportedStatusesForType(?string $type): array {
    switch ($type) {
      case NewsletterEntity::TYPE_STANDARD:
        return [
          NewsletterEntity::STATUS_DRAFT,
          NewsletterEntity::STATUS_SCHEDULED,
          NewsletterEntity::STATUS_SENDING,
          self::FILTER_STATUS_PAUSED,
          NewsletterEntity::STATUS_SENT,
        ];
      case NewsletterEntity::TYPE_NOTIFICATION_HISTORY:
        return [
          NewsletterEntity::STATUS_SENDING,
          self::FILTER_STATUS_PAUSED,
          NewsletterEntity::STATUS_SENT,
        ];
      case NewsletterEntity::TYPE_NOTIFICATION:
      case NewsletterEntity::TYPE_RE_ENGAGEMENT:
      case NewsletterEntity::TYPE_WELCOME:
      case NewsletterEntity::TYPE_AUTOMATIC:
        return [NewsletterEntity::STATUS_ACTIVE, NewsletterEntity::STATUS_DRAFT];
      default:
        return [];
    }
  }

  /**
   * Listing groups that exist for the type, as shown by getGroups().
   *
   * @return string[]
   */
  public static function getSupportedGroupsForType(?string $type): array {
    $statuses = array_diff(self::getSupportedStatusesForType($type), [self::FILTER_STATUS_PAUSED]);
    return array_merge(['all'], array_values($statuses), ['trash']);
  }

  /**
   * A list ID filter value: a non-negative integer or a string of digits.
   *
   * @param mixed $value
   * @phpstan-assert-if-true int|string $value
   */
  public static function isListId($value): bool {
    return is_int($value) ? $value >= 0 : (is_string($value) && ctype_digit($value));
  }

  public function getFilters(ListingDefinition $definition): array {
    $parameters = $definition->getParameters();
    $typeParam = $parameters['type'] ?? null;

    // Options are every list linked to any email of the type, trashed or not, whatever the
    // group, so they stay the same when switching tabs. Soft-deleted lists are not excluded.
    $queryBuilder = clone $this->queryBuilder;
    $queryBuilder->from(NewsletterEntity::class, 'n');
    if ($typeParam) {
      $this->applyType($queryBuilder, $typeParam);
    }
    $parentId = $parameters['parentId'] ?? null;
    if ($parentId) {
      $queryBuilder
        ->andWhere('n.parent = :parentId')
        ->setParameter('parentId', $parentId);
    }

    $queryBuilder
      ->select('DISTINCT s.id, s.name')
      ->join('n.newsletterSegments', 'ns')
      ->join('ns.segment', 's')
      ->orderBy('s.name')
      ->addOrderBy('s.id');

    $segmentList = [
      [
        'label' => __('All Lists', 'mailpoet'),
        'value' => '',
      ],
    ];

    foreach ($queryBuilder->getQuery()->getResult() as $item) {
      $segmentList[] = [
        'label' => $item['name'],
        'value' => $item['id'],
      ];
    }
    $filters = ['segment' => $segmentList];
    $statusOptions = is_string($typeParam) ? $this->getStatusOptions($typeParam, $parentId ? (int)$parentId : null) : [];
    if ($statusOptions) {
      $filters['status'] = $statusOptions;
    }
    return $filters;
  }

  /**
   * Counts non-trashed emails of the type, ignoring the group, search and other filters.
   * “Sending” excludes and “Paused” includes paused emails, like the status filter.
   *
   * @return array<int, array{label: string, value: string}>
   */
  private function getStatusOptions(string $type, ?int $parentId): array {
    $statuses = self::getSupportedStatusesForType($type);
    if (!$statuses) {
      return [];
    }

    $queryBuilder = clone $this->queryBuilder;
    $queryBuilder->from(NewsletterEntity::class, 'n');
    $this->applyParameters($queryBuilder, ['type' => $type, 'parentId' => $parentId]);
    $queryBuilder->andWhere('n.deletedAt IS NULL');

    $pausedCount = 0;
    if (in_array(self::FILTER_STATUS_PAUSED, $statuses, true)) {
      $pausedQueryBuilder = clone $queryBuilder;
      $pausedQueryBuilder
        ->select('COUNT(n)')
        ->andWhere('n.status = :filterSendingStatus')
        ->andWhere($this->getPausedExistsDql($pausedQueryBuilder))
        ->setParameter('filterSendingStatus', NewsletterEntity::STATUS_SENDING);
      $pausedCount = (int)$pausedQueryBuilder->getQuery()->getSingleScalarResult();
    }

    $queryBuilder->select('n.status, COUNT(n) AS newsletterCount')->groupBy('n.status');
    $counts = [];
    foreach ($queryBuilder->getQuery()->getResult() as $row) {
      $counts[$row['status']] = (int)$row['newsletterCount'];
    }
    $counts[NewsletterEntity::STATUS_SENDING] = ($counts[NewsletterEntity::STATUS_SENDING] ?? 0) - $pausedCount;
    $counts[self::FILTER_STATUS_PAUSED] = $pausedCount;

    $labels = $this->getStatusLabels($type);
    $options = [];
    foreach ($statuses as $status) {
      $options[] = [
        'label' => sprintf('%s (%s)', $labels[$status], number_format($counts[$status] ?? 0)),
        'value' => $status,
      ];
    }
    return $options;
  }

  /** @return array<string, string> */
  private function getStatusLabels(string $type): array {
    $isAutomated = in_array($type, [
      NewsletterEntity::TYPE_NOTIFICATION,
      NewsletterEntity::TYPE_RE_ENGAGEMENT,
      NewsletterEntity::TYPE_WELCOME,
      NewsletterEntity::TYPE_AUTOMATIC,
    ], true);
    return [
      NewsletterEntity::STATUS_DRAFT => $isAutomated ? __('Not active', 'mailpoet') : __('Draft', 'mailpoet'),
      NewsletterEntity::STATUS_SCHEDULED => __('Scheduled', 'mailpoet'),
      NewsletterEntity::STATUS_SENDING => __('Sending', 'mailpoet'),
      self::FILTER_STATUS_PAUSED => __('Paused', 'mailpoet'),
      NewsletterEntity::STATUS_SENT => __('Sent', 'mailpoet'),
      NewsletterEntity::STATUS_ACTIVE => __('Active', 'mailpoet'),
    ];
  }

  public function getGroups(ListingDefinition $definition): array {
    $queryBuilder = clone $this->queryBuilder;
    $this->applyFromClause($queryBuilder);
    $this->applyParameters($queryBuilder, $definition->getParameters());

    // total count
    $countQueryBuilder = clone $queryBuilder;
    $countQueryBuilder->select('COUNT(n) AS newsletterCount');
    $countQueryBuilder->andWhere('n.deletedAt IS NULL');
    $totalCount = (int)$countQueryBuilder->getQuery()->getSingleScalarResult();

    // trashed count
    $trashedCountQueryBuilder = clone $queryBuilder;
    $trashedCountQueryBuilder->select('COUNT(n) AS newsletterCount');
    $trashedCountQueryBuilder->andWhere('n.deletedAt IS NOT NULL');
    $trashedCount = (int)$trashedCountQueryBuilder->getQuery()->getSingleScalarResult();

    // count-by-status query
    $queryBuilder->select('n.status, COUNT(n) AS newsletterCount');
    $queryBuilder->andWhere('n.deletedAt IS NULL');
    $queryBuilder->groupBy('n.status');

    $map = [];
    foreach ($queryBuilder->getQuery()->getResult() as $item) {
      $map[$item['status']] = (int)$item['newsletterCount'];
    }

    $groups = [
      [
        'name' => 'all',
        'label' => __('All', 'mailpoet'),
        'count' => $totalCount,
      ],
    ];

    $type = $definition->getParameters()['type'] ?? null;
    switch ($type) {
      case NewsletterEntity::TYPE_STANDARD:
        $groups = array_merge($groups, [
          [
            'name' => NewsletterEntity::STATUS_DRAFT,
            'label' => __('Draft', 'mailpoet'),
            'count' => $map[NewsletterEntity::STATUS_DRAFT] ?? 0,
          ],
          [
            'name' => NewsletterEntity::STATUS_SCHEDULED,
            'label' => __('Scheduled', 'mailpoet'),
            'count' => $map[NewsletterEntity::STATUS_SCHEDULED] ?? 0,
          ],
          [
            'name' => NewsletterEntity::STATUS_SENDING,
            'label' => __('Sending', 'mailpoet'),
            'count' => $map[NewsletterEntity::STATUS_SENDING] ?? 0,
          ],
          [
            'name' => NewsletterEntity::STATUS_SENT,
            'label' => __('Sent', 'mailpoet'),
            'count' => $map[NewsletterEntity::STATUS_SENT] ?? 0,
          ],
        ]);
        break;

      case NewsletterEntity::TYPE_NOTIFICATION_HISTORY:
        $groups = array_merge($groups, [
          [
            'name' => NewsletterEntity::STATUS_SENDING,
            'label' => __('Sending', 'mailpoet'),
            'count' => $map[NewsletterEntity::STATUS_SENDING] ?? 0,
          ],
          [
            'name' => NewsletterEntity::STATUS_SENT,
            'label' => __('Sent', 'mailpoet'),
            'count' => $map[NewsletterEntity::STATUS_SENT] ?? 0,
          ],
        ]);
        break;

      case NewsletterEntity::TYPE_WELCOME:
      case NewsletterEntity::TYPE_RE_ENGAGEMENT:
      case NewsletterEntity::TYPE_NOTIFICATION:
      case NewsletterEntity::TYPE_AUTOMATIC:
        $groups = array_merge($groups, [
          [
            'name' => NewsletterEntity::STATUS_ACTIVE,
            'label' => __('Active', 'mailpoet'),
            'count' => $map[NewsletterEntity::STATUS_ACTIVE] ?? 0,
          ],
          [
            'name' => NewsletterEntity::STATUS_DRAFT,
            'label' => __('Not active', 'mailpoet'),
            'count' => $map[NewsletterEntity::STATUS_DRAFT] ?? 0,
          ],
        ]);
        break;
    }

    $groups[] = [
      'name' => 'trash',
      'label' => __('Trash', 'mailpoet'),
      'count' => $trashedCount,
    ];

    return $groups;
  }

  protected function applySelectClause(QueryBuilder $queryBuilder) {
    $queryBuilder->select("PARTIAL n.{id,subject,hash,type,status,sentAt,createdAt,updatedAt,deletedAt}, PARTIAL wpPost.{id,postTitle}");
  }

  protected function applyFromClause(QueryBuilder $queryBuilder) {
    $queryBuilder->from(NewsletterEntity::class, 'n')
      ->leftJoin('n.wpPost', 'wpPost');
  }

  protected function applyGroup(QueryBuilder $queryBuilder, string $group) {
    // include/exclude deleted
    if ($group === 'trash') {
      $queryBuilder->andWhere('n.deletedAt IS NOT NULL');
    } else {
      $queryBuilder->andWhere('n.deletedAt IS NULL');
    }

    if (!in_array($group, self::$supportedStatuses)) {
      return;
    }

    $queryBuilder
      ->andWhere('n.status = :status')
      ->setParameter('status', $group);
  }

  protected function applySearch(QueryBuilder $queryBuilder, string $search, array $parameters = []) {
    $search = Helpers::escapeSearch($search);

    $type = $parameters['type'] ?? null;

    if ($type && $type === NewsletterEntity::TYPE_NOTIFICATION_HISTORY) {
      $queryBuilder
        ->leftJoin('n.queues', 'sq')
        ->andWhere('sq.newsletterRenderedSubject LIKE :search or n.subject LIKE :search')
        ->setParameter('search', "%$search%");
    } else {
      $queryBuilder
        ->andWhere('n.subject LIKE :search')
        ->setParameter('search', "%$search%");
    }
  }

  protected function applyConstraints(QueryBuilder $queryBuilder, ListingDefinition $definition) {
    parent::applyConstraints($queryBuilder, $definition);
    // The status filter depends on the email type, which applyFilters() does not receive.
    $type = $definition->getParameters()['type'] ?? null;
    $this->applyStatusFilter(
      $queryBuilder,
      $definition->getFilters()['status'] ?? null,
      is_string($type) ? $type : null
    );
  }

  protected function applyFilters(QueryBuilder $queryBuilder, array $filters) {
    $segmentId = $filters['segment'] ?? null;
    if ($segmentId !== null && $segmentId !== '') {
      if (!self::isListId($segmentId)) {
        $queryBuilder->andWhere('1 = 0');
        return;
      }
      $queryBuilder
        ->andWhere('EXISTS (' . $this->getListMembershipDql($queryBuilder, 'nsLegacy', 'segmentId') . ')')
        ->setParameter('segmentId', [(int)$segmentId]);
    }

    $this->applySentAtBound($queryBuilder, $filters['sent_at_from'] ?? null, false);
    $this->applySentAtBound($queryBuilder, $filters['sent_at_to'] ?? null, true);

    // Filter by segment IDs with advanced operators
    $segmentIds = $filters['segment_ids'] ?? null;
    if ($segmentIds === null || $segmentIds === '' || $segmentIds === []) {
      return;
    }
    $segmentOperator = $filters['segment_operator'] ?? null;
    $segmentIds = $this->normalizeSegmentIds($segmentIds);
    if ($segmentIds === null || !in_array($segmentOperator, ['isAny', 'isNone'], true)) {
      $queryBuilder->andWhere('1 = 0');
      return;
    }

    $queryBuilder
      ->andWhere(($segmentOperator === 'isNone' ? 'NOT ' : '') . 'EXISTS (' . $this->getListMembershipDql($queryBuilder, 'nsIds', 'segmentIds') . ')')
      ->setParameter('segmentIds', $segmentIds);
  }

  /**
   * Matches `sent_at` only, so scheduled and other unsent emails never match. A
   * `yyyy-MM-dd` value is a whole site-local day; any other value matches nothing.
   *
   * @param mixed $value
   */
  private function applySentAtBound(QueryBuilder $queryBuilder, $value, bool $isUpperBound): void {
    if ($value === null || $value === '') {
      return;
    }
    $param = $isUpperBound ? 'sentToUtc' : 'sentFromUtc';
    if (!is_string($value)) {
      $queryBuilder->andWhere('1 = 0');
      return;
    }
    $dayStart = $this->getSiteDayStartInUtc($value, $isUpperBound ? 1 : 0);
    if ($dayStart === null) {
      $queryBuilder->andWhere('1 = 0');
      return;
    }
    $queryBuilder
      ->andWhere('n.sentAt ' . ($isUpperBound ? '<' : '>=') . " :$param")
      ->setParameter($param, $dayStart);
  }

  private function getListMembershipDql(QueryBuilder $queryBuilder, string $alias, string $parameter): string {
    return $queryBuilder->getEntityManager()->createQueryBuilder()
      ->select('1')
      ->from(NewsletterSegmentEntity::class, $alias)
      ->where("$alias.newsletter = n")
      ->andWhere("$alias.segment IN (:$parameter)")
      ->getDQL();
  }

  /**
   * @param mixed $segmentIds
   * @return int[]|null Null when the value is not a list of list IDs
   */
  private function normalizeSegmentIds($segmentIds): ?array {
    if (!is_array($segmentIds)) {
      return null;
    }
    $ids = [];
    foreach ($segmentIds as $id) {
      if (!self::isListId($id)) {
        return null;
      }
      $ids[] = (int)$id;
    }
    return $ids;
  }

  /**
   * @param mixed $status A status string or a list of them
   */
  private function applyStatusFilter(QueryBuilder $queryBuilder, $status, ?string $type): void {
    if ($status === null || $status === '' || $status === []) {
      return;
    }
    $requested = array_filter(is_array($status) ? $status : [$status], 'is_string');
    $statuses = array_values(array_intersect(self::getSupportedStatusesForType($type), $requested));
    if ($statuses === []) {
      $queryBuilder->andWhere('1 = 0');
      return;
    }

    $conditions = [];
    $plainStatuses = array_values(array_diff($statuses, [NewsletterEntity::STATUS_SENDING, self::FILTER_STATUS_PAUSED]));
    if ($plainStatuses) {
      $conditions[] = 'n.status IN (:filterStatuses)';
      $queryBuilder->setParameter('filterStatuses', $plainStatuses);
    }
    $wantsSending = in_array(NewsletterEntity::STATUS_SENDING, $statuses, true);
    $wantsPaused = in_array(self::FILTER_STATUS_PAUSED, $statuses, true);
    if ($wantsSending || $wantsPaused) {
      $queryBuilder->setParameter('filterSendingStatus', NewsletterEntity::STATUS_SENDING);
      if ($wantsSending && $wantsPaused) {
        $conditions[] = 'n.status = :filterSendingStatus';
      } else {
        $isPaused = $this->getPausedExistsDql($queryBuilder);
        $conditions[] = 'n.status = :filterSendingStatus AND ' . ($wantsPaused ? '' : 'NOT ') . $isPaused;
      }
    }
    $queryBuilder->andWhere('(' . implode(') OR (', $conditions) . ')');
  }

  /**
   * Mirrors the “paused” label: a non-time-zone email is paused when its latest
   * non-replay queue is paused, a time zone campaign when any of its batches is.
   * Known gap: batches of an older time zone campaign also count; those queues are
   * deleted when a campaign is rescheduled, so this needs a started campaign to be
   * replaced by a newer queue.
   */
  private function getPausedExistsDql(QueryBuilder $queryBuilder): string {
    $entityManager = $queryBuilder->getEntityManager();
    $latestQueueId = $entityManager->createQueryBuilder()
      ->select('MAX(pausedLatest.id)')
      ->from(SendingQueueEntity::class, 'pausedLatest')
      ->where('pausedLatest.newsletter = n.id')
      ->andWhere('pausedLatest.meta IS NULL OR pausedLatest.meta NOT LIKE :pausedReplayMeta')
      ->getDQL();
    $pausedQueue = $entityManager->createQueryBuilder()
      ->select('1')
      ->from(SendingQueueEntity::class, 'pausedQueue')
      ->join('pausedQueue.task', 'pausedTask')
      ->where('pausedQueue.newsletter = n.id')
      ->andWhere('pausedTask.status = :pausedTaskStatus')
      ->andWhere('pausedQueue.meta IS NULL OR pausedQueue.meta NOT LIKE :pausedReplayMeta')
      ->andWhere("pausedQueue.id = ($latestQueueId) OR pausedQueue.meta LIKE :pausedTimeZoneMeta")
      ->getDQL();
    $queryBuilder
      ->setParameter('pausedTaskStatus', ScheduledTaskEntity::STATUS_PAUSED)
      ->setParameter('pausedReplayMeta', NewsletterReplayMetadata::getMetaLikePattern())
      ->setParameter('pausedTimeZoneMeta', '%"' . TimeZoneCampaignScheduler::META_SEND_BY_TIMEZONE . '":true%');
    return 'EXISTS (' . $pausedQueue . ')';
  }

  protected function applyParameters(QueryBuilder $queryBuilder, array $parameters) {
    $type = $parameters['type'] ?? null;
    $group = $parameters['group'] ?? null;
    $parentId = $parameters['parentId'] ?? null;

    if ($type) {
      $this->applyType($queryBuilder, $type, $group);
    }

    if ($parentId) {
      $queryBuilder
        ->andWhere('n.parent = :parentId')
        ->setParameter('parentId', $parentId);
    }
  }

  protected function applySorting(QueryBuilder $queryBuilder, string $sortBy, string $sortOrder) {
    if ($sortBy === 'name') {
      $queryBuilder->addSelect('CONCAT(COALESCE(wpPost.postTitle, \'\'), n.subject) AS HIDDEN sortingName');
      $queryBuilder->addOrderBy("sortingName", $sortOrder);
      $queryBuilder->addOrderBy('n.id', $sortOrder);
      return;
    }
    if ($sortBy === 'sentAt') {
      $queryBuilder->addSelect('CASE WHEN n.sentAt IS NULL THEN 1 ELSE 0 END AS HIDDEN sentAtIsNull');
      $queryBuilder->addOrderBy('sentAtIsNull', 'DESC');
    }
    $queryBuilder->addOrderBy("n.$sortBy", $sortOrder);
    if ($sortBy !== 'id') {
      $queryBuilder->addOrderBy('n.id', $sortOrder);
    }
  }

  private function applyType(QueryBuilder $queryBuilder, string $type, ?string $group = null) {
    if (!in_array($type, self::$supportedTypes)) {
      return;
    }

    if ($type === NewsletterEntity::TYPE_AUTOMATIC && $group) {
      $queryBuilder
        ->join('n.options', 'o')
        ->join('o.optionField', 'opf')
        ->andWhere('o.value = :group')
        ->setParameter('group', $group)
        ->andWhere('opf.newsletterType = n.type');
    } else {
      $queryBuilder
        ->andWhere('n.type = :type')
        ->setParameter('type', $type);
    }
  }
}
