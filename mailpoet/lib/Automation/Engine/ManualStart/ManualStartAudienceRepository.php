<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\ManualStart;

use MailPoet\Automation\Engine\Data\AutomationRun as AutomationRunData;
use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class ManualStartAudienceRepository {
  public const QUEUE_CHUNK_SIZE = 100;
  private const ENTERED_RUN_STATUSES = [
    AutomationRunData::STATUS_RUNNING,
    AutomationRunData::STATUS_COMPLETE,
  ];

  public const REASON_ALREADY_ENTERED = 'already_entered';
  public const REASON_NOT_SUBSCRIBED = 'not_subscribed';
  public const REASON_UNCONFIRMED = 'unconfirmed';
  public const REASON_UNSUBSCRIBED = 'unsubscribed';
  public const REASON_BOUNCED = 'bounced';
  public const REASON_DELETED = 'deleted';
  public const REASON_NOT_IN_LIST = 'not_in_list';
  public const REASON_DYNAMIC_FILTER_MISMATCH = 'dynamic_filter_mismatch';
  public const REASON_TRIGGER_FILTER_MISMATCH = 'trigger_filter_mismatch';
  public const REASON_RUN_CREATE_HOOK_REJECTED = 'run_create_hook_rejected';
  public const REASON_AUTOMATION_INACTIVE = 'automation_inactive';
  public const REASON_SUBSCRIBER_MISSING = 'subscriber_missing';
  public const REASON_STEP_SCHEDULING_FAILED = 'step_scheduling_failed';

  /** @var EntityManager */
  private $entityManager;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct(
    EntityManager $entityManager,
    SegmentSubscribersRepository $segmentSubscribersRepository,
    SubscribersRepository $subscribersRepository,
    SegmentsRepository $segmentsRepository
  ) {
    $this->entityManager = $entityManager;
    $this->segmentSubscribersRepository = $segmentSubscribersRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->segmentsRepository = $segmentsRepository;
  }

  /**
   * @return array{selected_count: int, eligible_count: int, skipped_by_reason: array<string, int>}
   */
  public function getPreviewCounts(int $automationId, SegmentEntity $segment, ?SegmentEntity $filterSegment): array {
    $statusCounts = $this->getListStatusCounts((int)$segment->getId());
    $subscribedInList = $this->countSubscribedInList($segment, null, false, $automationId);
    $subscribedAfterFilter = $this->countSubscribedInList($segment, $filterSegment, false, $automationId);
    $alreadyEntered = $this->countSubscribedInList($segment, $filterSegment, true, $automationId);

    $skippedByReason = [
      self::REASON_ALREADY_ENTERED => $alreadyEntered,
      self::REASON_NOT_SUBSCRIBED => $statusCounts[self::REASON_NOT_SUBSCRIBED],
      self::REASON_UNCONFIRMED => $statusCounts[self::REASON_UNCONFIRMED],
      self::REASON_UNSUBSCRIBED => $statusCounts[self::REASON_UNSUBSCRIBED],
      self::REASON_BOUNCED => $statusCounts[self::REASON_BOUNCED],
      self::REASON_DELETED => $statusCounts[self::REASON_DELETED],
      self::REASON_NOT_IN_LIST => 0,
      self::REASON_DYNAMIC_FILTER_MISMATCH => $filterSegment ? max(0, $subscribedInList - $subscribedAfterFilter) : 0,
      self::REASON_TRIGGER_FILTER_MISMATCH => 0,
      self::REASON_RUN_CREATE_HOOK_REJECTED => 0,
      self::REASON_AUTOMATION_INACTIVE => 0,
      self::REASON_SUBSCRIBER_MISSING => 0,
      self::REASON_STEP_SCHEDULING_FAILED => 0,
    ];

    return [
      'selected_count' => $statusCounts['selected_count'],
      'eligible_count' => max(0, $subscribedAfterFilter - $alreadyEntered),
      'skipped_by_reason' => $skippedByReason,
    ];
  }

  public function queueEligibleSubscribers(ScheduledTaskEntity $task, int $automationId, SegmentEntity $segment, ?SegmentEntity $filterSegment): int {
    $taskId = $task->getId();
    if (!$taskId) {
      return 0;
    }

    $scheduledTaskSubscribersTable = $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();
    $eligibleQueryBuilder = $this->createSubscribedInListQueryBuilder($segment, $filterSegment);
    $this->addAlreadyEnteredCondition($eligibleQueryBuilder, $automationId, true);

    $queuedCount = 0;
    $lastSubscriberId = 0;
    do {
      $candidateSubscriberIds = $this->getCandidateSubscriberIds($eligibleQueryBuilder, $lastSubscriberId);
      if ($candidateSubscriberIds === []) {
        break;
      }

      $insertedCount = (int)$this->entityManager->getConnection()->executeStatement(
        "INSERT IGNORE INTO $scheduledTaskSubscribersTable
         (`task_id`, `subscriber_id`, `processed`)
         SELECT :manualStartTaskId, candidates.id, :manualStartProcessed
         FROM ({$eligibleQueryBuilder->getSQL()}) candidates
         WHERE candidates.id IN (:manualStartSubscriberIds)",
        array_merge($eligibleQueryBuilder->getParameters(), [
          'manualStartTaskId' => $taskId,
          'manualStartProcessed' => ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
          'manualStartSubscriberIds' => $candidateSubscriberIds,
        ]),
        array_merge($eligibleQueryBuilder->getParameterTypes(), [
          'manualStartTaskId' => ParameterType::INTEGER,
          'manualStartProcessed' => ParameterType::INTEGER,
          'manualStartSubscriberIds' => ArrayParameterType::INTEGER,
        ])
      );
      $queuedCount += $insertedCount;
      $lastSubscriberId = (int)max($candidateSubscriberIds);
    } while (count($candidateSubscriberIds) === self::QUEUE_CHUNK_SIZE);

    return $queuedCount;
  }

  public function getSegmentIneligibleReason(int $segmentId, ?int $filterSegmentId): ?string {
    $segment = $this->segmentsRepository->findOneById($segmentId);
    if (!$segment instanceof SegmentEntity || $segment->getDeletedAt() !== null || $segment->getType() !== SegmentEntity::TYPE_DEFAULT) {
      return self::REASON_NOT_IN_LIST;
    }

    if ($filterSegmentId === null) {
      return null;
    }

    $filterSegment = $this->segmentsRepository->findOneById($filterSegmentId);
    if (!$filterSegment instanceof SegmentEntity || $filterSegment->getDeletedAt() !== null || $filterSegment->getType() !== SegmentEntity::TYPE_DYNAMIC) {
      return self::REASON_DYNAMIC_FILTER_MISMATCH;
    }

    return null;
  }

  public function getSubscriberIneligibleReason(int $subscriberId, int $segmentId, ?int $filterSegmentId): ?string {
    $subscriber = $this->subscribersRepository->findOneById($subscriberId);
    if (!$subscriber instanceof SubscriberEntity) {
      return self::REASON_SUBSCRIBER_MISSING;
    }

    if ($subscriber->getDeletedAt() !== null) {
      return self::REASON_DELETED;
    }

    $status = $subscriber->getStatus();
    if ($status === SubscriberEntity::STATUS_UNCONFIRMED) {
      return self::REASON_UNCONFIRMED;
    }
    if ($status === SubscriberEntity::STATUS_UNSUBSCRIBED) {
      return self::REASON_UNSUBSCRIBED;
    }
    if ($status === SubscriberEntity::STATUS_BOUNCED) {
      return self::REASON_BOUNCED;
    }
    if ($status !== SubscriberEntity::STATUS_SUBSCRIBED) {
      return self::REASON_NOT_SUBSCRIBED;
    }

    if (!$this->isSubscriberSubscribedToSegment($subscriberId, $segmentId)) {
      return self::REASON_NOT_IN_LIST;
    }

    if ($filterSegmentId !== null && !$this->isSubscriberInDynamicSegment($subscriberId, $filterSegmentId)) {
      return self::REASON_DYNAMIC_FILTER_MISMATCH;
    }

    return null;
  }

  public function hasSubscriberEnteredAutomation(int $automationId, int $subscriberId): bool {
    global $wpdb;
    $runsTable = $wpdb->prefix . 'mailpoet_automation_runs';
    $subjectsTable = $wpdb->prefix . 'mailpoet_automation_run_subjects';
    $subject = new SubjectData(SubscriberSubject::KEY, ['subscriber_id' => $subscriberId]);

    $count = $this->entityManager->getConnection()->executeQuery(
      "SELECT COUNT(DISTINCT automation_runs.id)
       FROM $runsTable automation_runs
       INNER JOIN $subjectsTable automation_run_subjects ON automation_run_subjects.automation_run_id = automation_runs.id
       WHERE automation_runs.automation_id = :automationId
         AND automation_runs.status IN (:enteredRunStatuses)
         AND automation_run_subjects.`key` = :subjectKey
         AND automation_run_subjects.`hash` = :subjectHash",
      [
        'automationId' => $automationId,
        'enteredRunStatuses' => self::ENTERED_RUN_STATUSES,
        'subjectKey' => $subject->getKey(),
        'subjectHash' => $subject->getHash(),
      ],
      [
        'automationId' => ParameterType::INTEGER,
        'enteredRunStatuses' => ArrayParameterType::STRING,
        'subjectKey' => ParameterType::STRING,
        'subjectHash' => ParameterType::STRING,
      ]
    )->fetchOne();

    return $this->toInt($count) > 0;
  }

  /**
   * @return array<string, int>
   */
  private function getListStatusCounts(int $segmentId): array {
    $subscriberSegmentTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    $result = $this->entityManager->getConnection()->executeQuery(
      "SELECT
        COUNT(DISTINCT subscribers.id) AS selected_count,
        IFNULL(SUM(CASE WHEN subscribers.deleted_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS deleted,
        IFNULL(SUM(CASE WHEN subscribers.deleted_at IS NULL AND subscribers.status = :unconfirmed AND subscriber_segment.status != :unsubscribed THEN 1 ELSE 0 END), 0) AS unconfirmed,
        IFNULL(SUM(CASE WHEN subscribers.deleted_at IS NULL AND (subscribers.status = :unsubscribed OR subscriber_segment.status = :unsubscribed) THEN 1 ELSE 0 END), 0) AS unsubscribed,
        IFNULL(SUM(CASE WHEN subscribers.deleted_at IS NULL AND subscribers.status = :bounced AND subscriber_segment.status != :unsubscribed THEN 1 ELSE 0 END), 0) AS bounced,
        IFNULL(SUM(CASE WHEN subscribers.deleted_at IS NULL AND subscribers.status NOT IN (:knownStatuses) AND subscriber_segment.status != :unsubscribed THEN 1 ELSE 0 END), 0) AS not_subscribed
       FROM $subscriberSegmentTable subscriber_segment
       INNER JOIN $subscribersTable subscribers ON subscribers.id = subscriber_segment.subscriber_id
       WHERE subscriber_segment.segment_id = :segmentId",
      [
        'segmentId' => $segmentId,
        'unconfirmed' => SubscriberEntity::STATUS_UNCONFIRMED,
        'unsubscribed' => SubscriberEntity::STATUS_UNSUBSCRIBED,
        'bounced' => SubscriberEntity::STATUS_BOUNCED,
        'knownStatuses' => [
          SubscriberEntity::STATUS_SUBSCRIBED,
          SubscriberEntity::STATUS_UNCONFIRMED,
          SubscriberEntity::STATUS_UNSUBSCRIBED,
          SubscriberEntity::STATUS_BOUNCED,
        ],
      ],
      [
        'segmentId' => ParameterType::INTEGER,
        'unconfirmed' => ParameterType::STRING,
        'unsubscribed' => ParameterType::STRING,
        'bounced' => ParameterType::STRING,
        'knownStatuses' => ArrayParameterType::STRING,
      ]
    )->fetchAssociative();

    $result = is_array($result) ? $result : [];
    return [
      'selected_count' => $this->toInt($result['selected_count'] ?? 0),
      self::REASON_DELETED => $this->toInt($result['deleted'] ?? 0),
      self::REASON_UNCONFIRMED => $this->toInt($result['unconfirmed'] ?? 0),
      self::REASON_UNSUBSCRIBED => $this->toInt($result['unsubscribed'] ?? 0),
      self::REASON_BOUNCED => $this->toInt($result['bounced'] ?? 0),
      self::REASON_NOT_SUBSCRIBED => $this->toInt($result['not_subscribed'] ?? 0),
    ];
  }

  private function countSubscribedInList(SegmentEntity $segment, ?SegmentEntity $filterSegment, bool $alreadyEntered, int $automationId): int {
    $queryBuilder = $this->createSubscribedInListQueryBuilder($segment, $filterSegment);
    if ($alreadyEntered) {
      $this->addAlreadyEnteredCondition($queryBuilder, $automationId, false);
    }
    $count = $this->entityManager->getConnection()->executeQuery(
      "SELECT COUNT(*) FROM ({$queryBuilder->getSQL()}) manual_start_candidates",
      $queryBuilder->getParameters(),
      $queryBuilder->getParameterTypes()
    )->fetchOne();

    return $this->toInt($count);
  }

  private function createSubscribedInListQueryBuilder(SegmentEntity $segment, ?SegmentEntity $filterSegment): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subscriberSegmentTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();

    $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder()
      ->select('DISTINCT subscribers.id')
      ->from($subscribersTable, 'subscribers')
      ->innerJoin(
        'subscribers',
        $subscriberSegmentTable,
        'subscriber_segment',
        'subscriber_segment.subscriber_id = subscribers.id AND subscriber_segment.segment_id = :manualStartSegmentId'
      )
      ->where('subscribers.deleted_at IS NULL')
      ->andWhere('subscribers.status = :manualStartSubscribedStatus')
      ->andWhere('subscriber_segment.status = :manualStartSubscribedStatus')
      ->setParameter('manualStartSegmentId', (int)$segment->getId(), ParameterType::INTEGER)
      ->setParameter('manualStartSubscribedStatus', SubscriberEntity::STATUS_SUBSCRIBED, ParameterType::STRING);

    if ($filterSegment instanceof SegmentEntity) {
      $filterQueryBuilder = $this->segmentSubscribersRepository->createSubscribersInSegmentQueryBuilder($filterSegment, SubscriberEntity::STATUS_SUBSCRIBED);
      $queryBuilder->innerJoin(
        'subscribers',
        '(' . $filterQueryBuilder->getSQL() . ')',
        'filter_segment',
        'filter_segment.id = subscribers.id'
      );
      $queryBuilder->setParameters(
        array_merge($filterQueryBuilder->getParameters(), $queryBuilder->getParameters()),
        array_merge($filterQueryBuilder->getParameterTypes(), $queryBuilder->getParameterTypes())
      );
    }

    return $queryBuilder;
  }

  private function addAlreadyEnteredCondition(QueryBuilder $queryBuilder, int $automationId, bool $excludeAlreadyEntered): void {
    global $wpdb;
    $runsTable = $wpdb->prefix . 'mailpoet_automation_runs';
    $subjectsTable = $wpdb->prefix . 'mailpoet_automation_run_subjects';
    $subjectHashExpression = SubscriberSubject::getHashSqlExpression('subscribers.id', ':manualStartSubscriberSubjectKey');
    $existsCondition = "EXISTS (
      SELECT 1
      FROM $subjectsTable automation_run_subjects
      INNER JOIN $runsTable automation_runs ON automation_runs.id = automation_run_subjects.automation_run_id
      WHERE automation_runs.automation_id = :manualStartAutomationId
        AND automation_runs.status IN (:manualStartEnteredRunStatuses)
        AND automation_run_subjects.`key` = :manualStartSubscriberSubjectKey
        AND automation_run_subjects.`hash` = $subjectHashExpression
    )";

    $queryBuilder->andWhere($excludeAlreadyEntered ? "NOT $existsCondition" : $existsCondition)
      ->setParameter('manualStartAutomationId', $automationId, ParameterType::INTEGER)
      ->setParameter('manualStartEnteredRunStatuses', self::ENTERED_RUN_STATUSES, ArrayParameterType::STRING)
      ->setParameter('manualStartSubscriberSubjectKey', SubscriberSubject::KEY, ParameterType::STRING);
  }

  /**
   * @return int[]
   */
  private function getCandidateSubscriberIds(QueryBuilder $eligibleQueryBuilder, int $lastSubscriberId): array {
    $subscriberIds = $this->entityManager->getConnection()->executeQuery(
      "SELECT candidates.id
       FROM ({$eligibleQueryBuilder->getSQL()}) candidates
       WHERE candidates.id > :manualStartLastSubscriberId
       ORDER BY candidates.id ASC
       LIMIT :manualStartQueueLimit",
      array_merge($eligibleQueryBuilder->getParameters(), [
        'manualStartLastSubscriberId' => $lastSubscriberId,
        'manualStartQueueLimit' => self::QUEUE_CHUNK_SIZE,
      ]),
      array_merge($eligibleQueryBuilder->getParameterTypes(), [
        'manualStartLastSubscriberId' => ParameterType::INTEGER,
        'manualStartQueueLimit' => ParameterType::INTEGER,
      ])
    )->fetchFirstColumn();

    $ids = [];
    foreach ($subscriberIds as $subscriberId) {
      if (is_numeric($subscriberId)) {
        $ids[] = (int)$subscriberId;
      }
    }

    return $ids;
  }

  private function isSubscriberSubscribedToSegment(int $subscriberId, int $segmentId): bool {
    $subscriberSegment = $this->entityManager->getRepository(SubscriberSegmentEntity::class)->findOneBy([
      'subscriber' => $subscriberId,
      'segment' => $segmentId,
    ]);
    return $subscriberSegment instanceof SubscriberSegmentEntity
      && $subscriberSegment->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED;
  }

  private function isSubscriberInDynamicSegment(int $subscriberId, int $filterSegmentId): bool {
    $segment = $this->segmentsRepository->findOneById($filterSegmentId);
    if (!$segment instanceof SegmentEntity || $segment->getDeletedAt() !== null || $segment->getType() !== SegmentEntity::TYPE_DYNAMIC) {
      return false;
    }
    $subscriberIds = $this->segmentSubscribersRepository->findSubscribersIdsInSegment($filterSegmentId, [$subscriberId]);
    return in_array($subscriberId, array_map('intval', $subscriberIds), true);
  }

  /** @param mixed $value */
  private function toInt($value): int {
    if (is_numeric($value)) {
      return (int)$value;
    }
    return 0;
  }
}
