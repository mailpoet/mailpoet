<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Sending;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Doctrine\Repository;
use MailPoet\Doctrine\Types\JsonType;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\DBAL\Types\Type;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Query\Expr\Join;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

/**
 * @extends Repository<ScheduledTaskEntity>
 */
class ScheduledTasksRepository extends Repository {
  const TASK_BATCH_SIZE = 20;
  const CANCELLABLE_STATUSES = [
    ScheduledTaskEntity::STATUS_SCHEDULED,
    ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING,
    null,
  ];

  private SendingQueuesRepository $sendingQueuesRepository;

  public function __construct(
    EntityManager $entityManager,
    SendingQueuesRepository $sendingQueuesRepository
  ) {
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    parent::__construct($entityManager);
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return ScheduledTaskEntity[]
   */
  public function findByNewsletterAndStatus(NewsletterEntity $newsletter, string $status): array {
    return $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->join(SendingQueueEntity::class, 'sq', Join::WITH, 'st = sq.task')
      ->andWhere('st.status = :status')
      ->andWhere('sq.newsletter = :newsletter')
      ->setParameter('status', $status)
      ->setParameter('newsletter', $newsletter)
      ->getQuery()
      ->getResult();
  }

  /**
   * @return ScheduledTaskEntity[]
   */
  public function findPausedForSenderDomain(): array {
    return $this->createPausedByReasonQueryBuilder(
      ScheduledTaskEntity::PAUSE_REASON_SENDER_DOMAIN,
      // Re-engagement emails and "Send latest newsletter" replays have no Resume action, so don't list them.
      array_diff(NewsletterEntity::CAMPAIGN_TYPES, [NewsletterEntity::TYPE_RE_ENGAGEMENT])
    )
      ->orderBy('t.updatedAt', 'DESC')
      ->getQuery()
      ->getResult();
  }

  /**
   * @return ScheduledTaskEntity[]
   */
  public function findHeldOverdue(): array {
    return $this->createPausedByReasonQueryBuilder(ScheduledTaskEntity::PAUSE_REASON_OVERDUE, OverdueCampaignHold::HELD_TYPES)
      ->orderBy('t.scheduledAt', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * @return ScheduledTaskEntity[]
   */
  public function findHeldOverdueForNewsletter(NewsletterEntity $newsletter): array {
    return $this->createPausedByReasonQueryBuilder(ScheduledTaskEntity::PAUSE_REASON_OVERDUE, OverdueCampaignHold::HELD_TYPES)
      ->andWhere('q.newsletter = :newsletter')
      ->setParameter('newsletter', $newsletter)
      ->orderBy('t.scheduledAt', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * @param int[] $newsletterIds
   * @return ScheduledTaskEntity[]
   */
  public function findHeldOverdueForNewsletterIds(array $newsletterIds): array {
    return $this->createPausedByReasonQueryBuilder(ScheduledTaskEntity::PAUSE_REASON_OVERDUE, OverdueCampaignHold::HELD_TYPES)
      ->andWhere('n.id IN (:newsletterIds)')
      ->setParameter('newsletterIds', $newsletterIds)
      ->orderBy('t.scheduledAt', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Whether another issue of the same post notification is running, or a run of it is scheduled and due, so held ones must wait.
   */
  public function hasActiveSiblingIssue(NewsletterEntity $issue): bool {
    $parent = $issue->getParent();
    if (!$parent instanceof NewsletterEntity) {
      return false;
    }
    $count = $this->doctrineRepository->createQueryBuilder('t')
      ->select('COUNT(t.id)')
      ->join('t.sendingQueue', 'q')
      ->join('q.newsletter', 'n')
      ->where('t.type = :type')
      ->andWhere('t.deletedAt IS NULL')
      ->andWhere('n.deletedAt IS NULL')
      ->andWhere('(n.parent = :parent AND n.type = :historyType AND n.id != :issueId AND t.status IS NULL)'
        // A scheduled run belongs to the parent until the Scheduler turns it into an issue.
        . ' OR (n = :parent AND t.status = :scheduled AND t.scheduledAt <= :now)')
      ->setParameter('type', SendingQueue::TASK_TYPE)
      ->setParameter('parent', $parent)
      ->setParameter('historyType', NewsletterEntity::TYPE_NOTIFICATION_HISTORY)
      ->setParameter('issueId', $issue->getId())
      ->setParameter('scheduled', ScheduledTaskEntity::STATUS_SCHEDULED)
      ->setParameter('now', Carbon::now()->millisecond(0))
      ->getQuery()
      ->getSingleScalarResult();
    return (int)$count > 0;
  }

  /**
   * Sending tasks that the Scheduler has moved to running, nothing has been sent for and that were due before the cutoff.
   * A NULL scheduled time (older tasks) falls back to the creation time.
   *
   * @return ScheduledTaskEntity[]
   */
  public function findUnconfirmedRunningCampaignTasksDueBefore(\DateTimeInterface $cutoff): array {
    return $this->doctrineRepository->createQueryBuilder('t')
      ->select('t', 'q', 'n')
      ->join('t.sendingQueue', 'q')
      ->join('q.newsletter', 'n')
      ->where('t.type = :type')
      ->andWhere('t.status IS NULL')
      ->andWhere('COALESCE(t.inProgress, 0) = 0')
      ->andWhere('t.deletedAt IS NULL')
      ->andWhere('n.deletedAt IS NULL')
      ->andWhere('n.type IN (:types)')
      ->andWhere('COALESCE(t.scheduledAt, t.createdAt) <= :cutoff')
      ->andWhere('q.countProcessed = 0')
      ->andWhere('q.meta IS NULL OR q.meta NOT LIKE :replayMeta')
      ->andWhere('t.meta IS NULL OR (t.meta NOT LIKE :replayMeta AND t.meta NOT LIKE :confirmedMarker)')
      ->setParameter('type', SendingQueue::TASK_TYPE)
      ->setParameter('types', OverdueCampaignHold::HELD_TYPES)
      ->setParameter('cutoff', $cutoff)
      ->setParameter('replayMeta', NewsletterReplayMetadata::getMetaLikePattern())
      ->setParameter('confirmedMarker', $this->getMetaMarker(ScheduledTaskEntity::META_OVERDUE_SEND_CONFIRMED, true))
      ->orderBy('t.scheduledAt', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Pauses a running task as overdue unless a worker got to it first. The conditions sit in one UPDATE so
   * the decision and the write cannot be split by a worker that starts sending in between.
   * Returns false when the task was not claimed. The entity is refreshed either way.
   * A batch that is still scheduled can be claimed as well when $includeScheduled is set.
   */
  public function claimForOverdueHold(ScheduledTaskEntity $task, bool $includeScheduled = false): bool {
    $connection = $this->entityManager->getConnection();
    $tasksTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $queuesTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();

    $oldMeta = $connection->fetchOne("SELECT `meta` FROM `{$tasksTable}` WHERE `id` = :id", ['id' => $task->getId()]);
    $claimed = false;
    if ($oldMeta === null || is_string($oldMeta)) {
      $meta = ($oldMeta === null || $oldMeta === '') ? [] : json_decode($oldMeta, true);
      if (is_array($meta)) {
        $meta[ScheduledTaskEntity::META_PAUSE_REASON] = ScheduledTaskEntity::PAUSE_REASON_OVERDUE;
        $newMeta = Type::getType(JsonType::NAME)->convertToDatabaseValue($meta, $connection->getDatabasePlatform());
        $statusCondition = $includeScheduled ? 't.`status` IS NULL OR t.`status` = :scheduled' : 't.`status` IS NULL';
        $params = [
          'paused' => ScheduledTaskEntity::STATUS_PAUSED,
          'newMeta' => $newMeta,
          'updatedAt' => Carbon::now()->millisecond(0)->format('Y-m-d H:i:s'),
          'id' => $task->getId(),
          'oldMeta' => $oldMeta,
        ];
        $types = [
          'paused' => ParameterType::STRING,
          'newMeta' => ParameterType::STRING,
          'updatedAt' => ParameterType::STRING,
          'id' => ParameterType::INTEGER,
          'oldMeta' => ParameterType::STRING,
        ];
        if ($includeScheduled) {
          $params['scheduled'] = ScheduledTaskEntity::STATUS_SCHEDULED;
          $types['scheduled'] = ParameterType::STRING;
        }
        $affectedRows = $connection->executeStatement(
          "UPDATE `{$tasksTable}` t
           INNER JOIN `{$queuesTable}` q ON q.`task_id` = t.`id`
           SET t.`status` = :paused, t.`meta` = :newMeta, t.`updated_at` = :updatedAt
           WHERE t.`id` = :id
             AND ({$statusCondition})
             AND COALESCE(t.`in_progress`, 0) = 0
             AND q.`count_processed` = 0
             AND t.`meta` <=> :oldMeta",
          $params,
          $types
        );
        $claimed = $affectedRows === 1;
      }
    }

    $this->entityManager->refresh($task);
    return $claimed;
  }

  /**
   * @param string[] $newsletterTypes
   */
  private function createPausedByReasonQueryBuilder(string $reason, array $newsletterTypes): QueryBuilder {
    return $this->doctrineRepository->createQueryBuilder('t')
      ->select('t')
      ->join('t.sendingQueue', 'q')
      ->join('q.newsletter', 'n')
      ->leftJoin('n.wpPost', 'wp')
      ->leftJoin('n.parent', 'np')
      ->addSelect('q', 'n', 'wp', 'np')
      ->where('t.status = :paused')
      ->andWhere('t.deletedAt IS NULL')
      ->andWhere('n.deletedAt IS NULL')
      ->andWhere('n.type IN (:campaignTypes)')
      ->andWhere('q.meta IS NULL OR q.meta NOT LIKE :replayMeta')
      ->andWhere('t.meta LIKE :marker')
      ->andWhere('t.meta NOT LIKE :replayMeta')
      ->setParameter('paused', ScheduledTaskEntity::STATUS_PAUSED)
      ->setParameter('campaignTypes', $newsletterTypes)
      ->setParameter('replayMeta', NewsletterReplayMetadata::getMetaLikePattern())
      ->setParameter('marker', $this->getMetaMarker(ScheduledTaskEntity::META_PAUSE_REASON, $reason));
  }

  /**
   * @param string|bool $value
   */
  private function getMetaMarker(string $key, $value): string {
    // Task meta is a LONGTEXT column written by JsonType (unescaped slashes and unicode), so the
    // stored text contains exactly this fragment of the encoder output.
    return '%' . trim((string)json_encode([$key => $value], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), '{}') . '%';
  }

  /**
   * @param NewsletterEntity $newsletter
   */
  public function findOneByNewsletter(NewsletterEntity $newsletter): ?ScheduledTaskEntity {
    $scheduledTask = $this->doctrineRepository->createQueryBuilder('st')
      ->join(SendingQueueEntity::class, 'sq', Join::WITH, 'st = sq.task')
      ->andWhere('sq.newsletter = :newsletter')
      ->orderBy('sq.updatedAt', 'desc')
      ->setMaxResults(1)
      ->setParameter('newsletter', $newsletter)
      ->getQuery()
      ->getOneOrNullResult();
    // for phpstan because it detects mixed instead of entity
    return ($scheduledTask instanceof ScheduledTaskEntity) ? $scheduledTask : null;
  }

  public function findOneBySendingQueue(SendingQueueEntity $sendingQueue): ?ScheduledTaskEntity {
    $scheduledTask = $this->doctrineRepository->createQueryBuilder('st')
      ->join(SendingQueueEntity::class, 'sq', Join::WITH, 'st = sq.task')
      ->andWhere('sq.id = :sendingQueue')
      ->setMaxResults(1)
      ->setParameter('sendingQueue', $sendingQueue)
      ->getQuery()
      ->getOneOrNullResult();
    // for phpstan because it detects mixed instead of entity
    return ($scheduledTask instanceof ScheduledTaskEntity) ? $scheduledTask : null;
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return ScheduledTaskEntity[]
   */
  public function findByScheduledAndRunningForNewsletter(NewsletterEntity $newsletter): array {
    return $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->join(SendingQueueEntity::class, 'sq', Join::WITH, 'st = sq.task')
      ->andWhere('st.status = :status OR st.status IS NULL')
      ->andWhere('sq.newsletter = :newsletter')
      ->setParameter('status', NewsletterEntity::STATUS_SCHEDULED)
      ->setParameter('newsletter', $newsletter)
      ->getQuery()
      ->getResult();
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return ScheduledTaskEntity[]
   */
  public function findByNewsletterAndSubscriberId(NewsletterEntity $newsletter, int $subscriberId): array {
    return $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->join(SendingQueueEntity::class, 'sq', Join::WITH, 'st = sq.task')
      ->join(ScheduledTaskSubscriberEntity::class, 'sts', Join::WITH, 'st = sts.task')
      ->andWhere('sq.newsletter = :newsletter')
      ->andWhere('sts.subscriber = :subscriber')
      ->setParameter('newsletter', $newsletter)
      ->setParameter('subscriber', $subscriberId)
      ->getQuery()
      ->getResult();
  }

  public function findOneScheduledByNewsletterAndSubscriber(NewsletterEntity $newsletter, SubscriberEntity $subscriber): ?ScheduledTaskEntity {
    $scheduledTask = $this->doctrineRepository->createQueryBuilder('st')
      ->join(SendingQueueEntity::class, 'sq', Join::WITH, 'st = sq.task')
      ->join(ScheduledTaskSubscriberEntity::class, 'sts', Join::WITH, 'st = sts.task')
      ->andWhere('st.status = :status')
      ->andWhere('sq.newsletter = :newsletter')
      ->andWhere('sts.subscriber = :subscriber')
      ->setMaxResults(1)
      ->setParameter('status', ScheduledTaskEntity::STATUS_SCHEDULED)
      ->setParameter('newsletter', $newsletter)
      ->setParameter('subscriber', $subscriber)
      ->getQuery()
      ->getOneOrNullResult();
    // for phpstan because it detects mixed instead of entity
    return ($scheduledTask instanceof ScheduledTaskEntity) ? $scheduledTask : null;
  }

  public function findScheduledOrRunningTask(?string $type): ?ScheduledTaskEntity {
    $queryBuilder = $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->where('((st.status = :scheduledStatus) OR (st.status is NULL))')
      ->andWhere('st.deletedAt IS NULL')
      ->setParameter('scheduledStatus', ScheduledTaskEntity::STATUS_SCHEDULED)
      ->setMaxResults(1)
      ->orderBy('st.scheduledAt', 'DESC');
    if (!empty($type)) {
      $queryBuilder
        ->andWhere('st.type = :type')
        ->setParameter('type', $type);
    }
    return $queryBuilder->getQuery()->getOneOrNullResult();
  }

  public function findScheduledTask(?string $type): ?ScheduledTaskEntity {
    $queryBuilder = $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->where('st.status = :scheduledStatus')
      ->andWhere('st.deletedAt IS NULL')
      ->setParameter('scheduledStatus', ScheduledTaskEntity::STATUS_SCHEDULED)
      ->setMaxResults(1)
      ->orderBy('st.scheduledAt', 'DESC');
    if (!empty($type)) {
      $queryBuilder
        ->andWhere('st.type = :type')
        ->setParameter('type', $type);
    }
    return $queryBuilder->getQuery()->getOneOrNullResult();
  }

  public function findSoonestScheduledTaskByType(string $type): ?ScheduledTaskEntity {
    return $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->where('st.status = :scheduledStatus')
      ->andWhere('st.type = :type')
      ->andWhere('st.deletedAt IS NULL')
      ->setParameter('scheduledStatus', ScheduledTaskEntity::STATUS_SCHEDULED)
      ->setParameter('type', $type)
      ->setMaxResults(1)
      ->orderBy('st.scheduledAt', 'ASC')
      ->getQuery()
      ->getOneOrNullResult();
  }

  /**
   * Atomically claims a scheduled/paused row for a CLI run by flipping it to STATUS_CLI in a single
   * guarded UPDATE. Returns false when no row was changed (another process already claimed or ran it),
   * so concurrent CLI runs never both process the same task.
   * A claim of a sending task must also clear its pause_reason (sending tasks can't be claimed today).
   */
  public function claimAsCli(ScheduledTaskEntity $task): bool {
    $updated = (int)$this->entityManager->createQuery(
      'UPDATE ' . ScheduledTaskEntity::class . ' st ' .
      'SET st.status = :cli ' .
      'WHERE st.id = :id AND st.status IN (:claimable) AND st.deletedAt IS NULL'
    )
      ->setParameter('cli', ScheduledTaskEntity::STATUS_CLI)
      ->setParameter('id', $task->getId())
      ->setParameter('claimable', [ScheduledTaskEntity::STATUS_SCHEDULED, ScheduledTaskEntity::STATUS_PAUSED])
      ->execute();

    if ($updated === 0) {
      return false;
    }

    // The DQL UPDATE bypasses the entity manager, so refresh the in-memory entity to the claimed state.
    // Otherwise its status snapshot stays 'scheduled' and a later hand-back to 'scheduled' is seen as a
    // no-op change, leaving the row stuck as 'cli'.
    $this->entityManager->refresh($task);
    return true;
  }

  public function findPreviousTask(ScheduledTaskEntity $task): ?ScheduledTaskEntity {
    return $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->where('st.type = :type')
      ->setParameter('type', $task->getType())
      ->andWhere('st.createdAt < :created')
      ->setParameter('created', $task->getCreatedAt())
      ->orderBy('st.scheduledAt', 'DESC')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();
  }

  public function findDueByType($type, $limit = null) {
    return $this->findByTypeAndStatus($type, ScheduledTaskEntity::STATUS_SCHEDULED, $limit);
  }

  public function findRunningByType($type, $limit = null) {
    return $this->findByTypeAndStatus($type, null, $limit);
  }

  public function findCompletedByType($type, $limit = null) {
    return $this->findByTypeAndStatus($type, ScheduledTaskEntity::STATUS_COMPLETED, $limit);
  }

  public function findFutureScheduledByType($type, $limit = null) {
    return $this->findByTypeAndStatus($type, ScheduledTaskEntity::STATUS_SCHEDULED, $limit, true);
  }

  public function getCountsPerStatus(string $type = 'sending') {
    $stats = [
      ScheduledTaskEntity::STATUS_COMPLETED => 0,
      ScheduledTaskEntity::STATUS_PAUSED => 0,
      ScheduledTaskEntity::STATUS_SCHEDULED => 0,
      ScheduledTaskEntity::STATUS_CANCELLED => 0,
      ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING => 0,
    ];

    $counts = $this->doctrineRepository->createQueryBuilder('st')
      ->select('COUNT(st.id) as value')
      ->addSelect('st.status')
      ->where('st.deletedAt IS NULL')
      ->andWhere('st.type = :type')
      ->setParameter('type', $type)
      ->addGroupBy('st.status')
      ->getQuery()
      ->getResult();

    foreach ($counts as $count) {
      if ($count['status'] === null) {
        $stats[ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING] = (int)$count['value'];
        continue;
      }
      $stats[$count['status']] = (int)$count['value'];
    }
    return $stats;
  }

  /**
   * @param string|null $type
   * @param array $statuses
   * @param int $limit
   * @return array<ScheduledTaskEntity>
   */
  public function getLatestTasks(
    $type = null,
    $statuses = [
      ScheduledTaskEntity::STATUS_COMPLETED,
      ScheduledTaskEntity::STATUS_CANCELLED,
      ScheduledTaskEntity::STATUS_SCHEDULED,
      ScheduledTaskEntity::STATUS_PAUSED,
      ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING,
    ],
    $limit = self::TASK_BATCH_SIZE
  ) {
    $result = [];
    foreach ($statuses as $status) {
      $tasksQuery = $this->doctrineRepository->createQueryBuilder('st')
        ->select('st')
        ->where('st.deletedAt IS NULL');

      if ($status === ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING) {
        $tasksQuery = $tasksQuery->andWhere('st.status = :status OR st.status IS NULL');
      } else {
        $tasksQuery = $tasksQuery->andWhere('st.status = :status');
      }

      if ($type) {
        $tasksQuery = $tasksQuery->andWhere('st.type = :type')
          ->setParameter('type', $type);
      }

      $tasks = $tasksQuery
        ->setParameter('status', $status)
        ->setMaxResults($limit)
        ->orderBy('st.id', 'desc')
        ->getQuery()
        ->getResult();
      $result = array_merge($result, $tasks);
    }

    return $result;
  }

  /**
   * @return ScheduledTaskEntity[]
   */
  public function findRunningSendingTasks(?int $limit = null): array {
    return $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->join('st.sendingQueue', 'sq')
      ->where('st.type = :type')
      ->andWhere('st.status IS NULL')
      ->andWhere('st.deletedAt IS NULL')
      ->orderBy('st.priority', 'ASC')
      ->addOrderBy('st.updatedAt', 'ASC')
      ->setMaxResults($limit)
      ->setParameter('type', SendingQueue::TASK_TYPE)
      ->getQuery()
      ->getResult();
  }

  /**
   * @param string $type
   * @param SubscriberEntity $subscriber
   * @return ScheduledTaskEntity[]
   * @throws \MailPoetVendor\Doctrine\ORM\NonUniqueResultException
   */
  public function findByTypeAndSubscriber(string $type, SubscriberEntity $subscriber): array {
    $query = $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->join(ScheduledTaskSubscriberEntity::class, 'sts', Join::WITH, 'st = sts.task')
      ->where('st.type = :type')
      ->andWhere('sts.subscriber = :subscriber')
      ->andWhere('st.deletedAt IS NULL')
      ->andWhere('st.status = :status')
      ->setParameter('type', $type)
      ->setParameter('subscriber', $subscriber->getId())
      ->setParameter('status', ScheduledTaskEntity::STATUS_SCHEDULED)
      ->getQuery();
    $tasks = $query->getResult();
    return $tasks;
  }

  public function touchAllByIds(array $ids): void {
    $now = Carbon::now()->millisecond(0);
    $this->entityManager->createQueryBuilder()
      ->update(ScheduledTaskEntity::class, 'st')
      ->set('st.updatedAt', ':updatedAt')
      ->setParameter('updatedAt', $now)
      ->where('st.id IN (:ids)')
      ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
      ->getQuery()
      ->execute();

    // update was done via DQL, make sure the entities are also refreshed in the entity manager
    $this->refreshAll(function (ScheduledTaskEntity $entity) use ($ids) {
      return in_array($entity->getId(), $ids, true);
    });
  }

  /**
   * @return ScheduledTaskEntity[]
   */
  public function findScheduledSendingTasks(?int $limit = null): array {
    $now = Carbon::now()->millisecond(0);
    return $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->join('st.sendingQueue', 'sq')
      ->where('st.deletedAt IS NULL')
      ->andWhere('st.status = :status')
      ->andWhere('st.scheduledAt <= :now')
      ->andWhere('st.type = :type')
      ->orderBy('st.updatedAt', 'ASC')
      ->setMaxResults($limit)
      ->setParameter('status', ScheduledTaskEntity::STATUS_SCHEDULED)
      ->setParameter('now', $now)
      ->setParameter('type', SendingQueue::TASK_TYPE)
      ->getQuery()
      ->getResult();
  }

  public function invalidateTask(ScheduledTaskEntity $task): void {
    $task->setStatus(ScheduledTaskEntity::STATUS_INVALID);
    $this->persist($task);
    $this->flush();
  }

  public function cancelTask(ScheduledTaskEntity $task): void {
    if (!in_array($task->getStatus(), self::CANCELLABLE_STATUSES)) {
      throw new \Exception(__('Only scheduled and running tasks can be cancelled', 'mailpoet'), 400);
    }
    $task->setStatus(ScheduledTaskEntity::STATUS_CANCELLED);
    $task->setCancelledAt(Carbon::now()->millisecond(0));
    $this->persist($task);
    $this->flush();
  }

  public function rescheduleTask(ScheduledTaskEntity $task): void {
    if ($task->getStatus() !== ScheduledTaskEntity::STATUS_CANCELLED) {
      throw new \Exception(__('Only cancelled tasks can be rescheduled', 'mailpoet'), 400);
    }
    if ($task->getScheduledAt() <= Carbon::now()->millisecond(0)) {
      $task->setStatus(ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING);
      $queue = $task->getSendingQueue();
      if ($queue) {
        $this->sendingQueuesRepository->resume($queue);
      }
    } else {
      $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    }
    $task->setCancelledAt(null);
    $this->persist($task);
    $this->flush();
  }

  /** @param int[] $ids */
  public function deleteByIds(array $ids): void {
    $this->entityManager->createQueryBuilder()
      ->delete(ScheduledTaskEntity::class, 't')
      ->where('t.id IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();

    // delete was done via DQL, make sure the entities are also detached from the entity manager
    $this->detachAll(function (ScheduledTaskEntity $entity) use ($ids) {
      return in_array($entity->getId(), $ids, true);
    });
  }

  protected function findByTypeAndStatus($type, $status, $limit = null, $future = false) {
    $queryBuilder = $this->doctrineRepository->createQueryBuilder('st')
      ->select('st')
      ->where('st.type = :type')
      ->setParameter('type', $type)
      ->andWhere('st.deletedAt IS NULL');

    if (is_null($status)) {
      $queryBuilder->andWhere('st.status IS NULL');
    } else {
      $queryBuilder
        ->andWhere('st.status = :status')
        ->setParameter('status', $status);
    }

    if ($future) {
      $queryBuilder->andWhere('st.scheduledAt > :now');
    } else {
      $queryBuilder->andWhere('st.scheduledAt <= :now');
    }

    $now = Carbon::now()->millisecond(0);
    $queryBuilder->setParameter('now', $now);

    if ($limit) {
      $queryBuilder->setMaxResults($limit);
    }

    return $queryBuilder->getQuery()->getResult();
  }

  protected function getEntityClassName() {
    return ScheduledTaskEntity::class;
  }
}
