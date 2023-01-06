<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter;

use DateTimeInterface;
use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\AutomaticEmails\WooCommerce\Events\FirstPurchase;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedInCategory;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedProduct;
use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Entities\StatsNotificationEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Query\Expr\Join;

/**
 * @extends Repository<NewsletterEntity>
 */
class NewslettersRepository extends Repository {
  /** @var LoggerFactory */
  private $loggerFactory;

  public function __construct(
    EntityManager $entityManager
  ) {
    parent::__construct($entityManager);
    $this->loggerFactory = LoggerFactory::getInstance();
  }

  protected function getEntityClassName() {
    return NewsletterEntity::class;
  }

  /**
   * @param string[] $types
   * @return NewsletterEntity[]
   */
  public function findActiveByTypes($types) {
    return $this->entityManager
      ->createQueryBuilder()
      ->select('n')
      ->from(NewsletterEntity::class, 'n')
      ->where('n.status = :status')
      ->setParameter(':status', NewsletterEntity::STATUS_ACTIVE)
      ->andWhere('n.deletedAt is null')
      ->andWhere('n.type IN (:types)')
      ->setParameter('types', $types)
      ->orderBy('n.subject')
      ->getQuery()
      ->getResult();
  }

  public function getCountForStatusAndTypes(string $status, array $types): int {
    return intval($this->entityManager
      ->createQueryBuilder()
      ->select('COUNT(n.id)')
      ->from(NewsletterEntity::class, 'n')
      ->where('n.status = :status')
      ->andWhere('n.deletedAt is null')
      ->andWhere('n.type IN (:types)')
      ->setParameter('status', $status)
      ->setParameter('types', $types)
      ->getQuery()
      ->getSingleScalarResult());
  }

  public function getCountOfActiveAutomaticEmailsForEvent(string $event): int {
    return intval($this->entityManager->createQueryBuilder()
      ->select('COUNT(n.id)')
      ->from(NewsletterEntity::class, 'n')
      ->where('n.status = :status')
      ->andWhere('n.deletedAt IS NULL')
      ->andWhere('n.type = :type')
      ->join('n.options', 'o', Join::WITH, 'o.value = :event')
      ->join('o.optionField', 'f', Join::WITH, 'f.name = :nameEvent AND f.newsletterType = :type')
      ->setParameter('status', NewsletterEntity::STATUS_ACTIVE)
      ->setParameter('nameEvent', NewsletterOptionFieldEntity::NAME_EVENT)
      ->setParameter('type', NewsletterEntity::TYPE_AUTOMATIC)
      ->setParameter('event', $event)
      ->getQuery()
      ->getSingleScalarResult());
  }

  /**
   * @return NewsletterEntity[]
   */
  public function findActiveByTypeAndGroup(string $type, ?string $group): array {
    $qb = $this->entityManager
      ->createQueryBuilder()
      ->select('n')
      ->from(NewsletterEntity::class, 'n')
      ->where('n.status = :status')
      ->setParameter(':status', NewsletterEntity::STATUS_ACTIVE)
      ->andWhere('n.deletedAt IS NULL')
      ->andWhere('n.type = :type')
      ->setParameter('type', $type);

    if ($group) {
      $qb->join('n.options', 'o', Join::WITH, 'o.value = :group')
        ->join('o.optionField', 'f', Join::WITH, 'f.name = :nameGroup AND f.newsletterType = :type')
        ->setParameter('nameGroup', NewsletterOptionFieldEntity::NAME_GROUP)
        ->setParameter('group', $group);
    }

    return $qb->getQuery()->getResult();
  }

  /**
   * @param string[] $types
   * @return NewsletterEntity[]
   */
  public function findDraftByTypes($types) {
    return $this->entityManager
      ->createQueryBuilder()
      ->select('n')
      ->from(NewsletterEntity::class, 'n')
      ->where('n.status = :status')
      ->setParameter(':status', NewsletterEntity::STATUS_DRAFT)
      ->andWhere('n.deletedAt is null')
      ->andWhere('n.type IN (:types)')
      ->setParameter('types', $types)
      ->orderBy('n.subject')
      ->getQuery()
      ->getResult();
  }

  public function getStandardNewsletterSentCount(DateTimeInterface $since): int {
    return (int)$this->doctrineRepository->createQueryBuilder('n')
      ->select('COUNT(n)')
      ->join('n.queues', 'q')
      ->join('q.task', 't')
      ->andWhere('n.type = :type')
      ->andWhere('n.status = :status')
      ->andWhere('t.status = :taskStatus')
      ->andWhere('t.processedAt >= :since')
      ->setParameter('type', NewsletterEntity::TYPE_STANDARD)
      ->setParameter('status', NewsletterEntity::STATUS_SENT)
      ->setParameter('taskStatus', ScheduledTaskEntity::STATUS_COMPLETED)
      ->setParameter('since', $since)
      ->getQuery()
      ->getSingleScalarResult() ?: 0;
  }

  public function getAnalytics(): array {
    // for automatic emails join 'event' newsletter option to further group the counts
    $eventOptionId = (int)$this->entityManager->createQueryBuilder()
      ->select('nof.id')
      ->from(NewsletterOptionFieldEntity::class, 'nof')
      ->andWhere('nof.newsletterType = :eventOptionFieldType')
      ->andWhere('nof.name = :eventOptionFieldName')
      ->setParameter('eventOptionFieldType', NewsletterEntity::TYPE_AUTOMATIC)
      ->setParameter('eventOptionFieldName', 'event')
      ->getQuery()
      ->getSingleScalarResult();

    $results = $this->doctrineRepository->createQueryBuilder('n')
      ->select('n.type, eventOption.value AS event, COUNT(n) AS cnt')
      ->leftJoin('n.options', 'eventOption', Join::WITH, "eventOption.optionField = :eventOptionId")
      ->andWhere('n.deletedAt IS NULL')
      ->andWhere('n.status IN (:statuses)')
      ->setParameter('eventOptionId', $eventOptionId)
      ->setParameter('statuses', [NewsletterEntity::STATUS_ACTIVE, NewsletterEntity::STATUS_SENT])
      ->groupBy('n.type, eventOption.value')
      ->getQuery()
      ->getResult();

    $analyticsMap = [];
    foreach ($results as $result) {
      $type = $result['type'];
      if ($type === NewsletterEntity::TYPE_AUTOMATIC) {
        $analyticsMap[$type][$result['event'] ?? ''] = (int)$result['cnt'];
      } else {
        $analyticsMap[$type] = (int)$result['cnt'];
      }
    }

    return [
      'welcome_newsletters_count' => $analyticsMap[NewsletterEntity::TYPE_WELCOME] ?? 0,
      'notifications_count' => $analyticsMap[NewsletterEntity::TYPE_NOTIFICATION] ?? 0,
      'automatic_emails_count' => array_sum($analyticsMap[NewsletterEntity::TYPE_AUTOMATIC] ?? []),
      'sent_newsletters_count' => $analyticsMap[NewsletterEntity::TYPE_STANDARD] ?? 0,
      'sent_newsletters_3_months' => $this->getStandardNewsletterSentCount(Carbon::now()->subMonths(3)),
      'sent_newsletters_30_days' => $this->getStandardNewsletterSentCount(Carbon::now()->subDays(30)),
      'first_purchase_emails_count' => $analyticsMap[NewsletterEntity::TYPE_AUTOMATIC][FirstPurchase::SLUG] ?? 0,
      'product_purchased_emails_count' => $analyticsMap[NewsletterEntity::TYPE_AUTOMATIC][PurchasedProduct::SLUG] ?? 0,
      'product_purchased_in_category_emails_count' => $analyticsMap[NewsletterEntity::TYPE_AUTOMATIC][PurchasedInCategory::SLUG] ?? 0,
      'abandoned_cart_emails_count' => $analyticsMap[NewsletterEntity::TYPE_AUTOMATIC][AbandonedCart::SLUG] ?? 0,
    ];
  }

  /**
   * @param array $segmentIds
   * @return NewsletterEntity[]
   */
  public function getArchives(array $segmentIds = []) {
    $types = [
      NewsletterEntity::TYPE_STANDARD,
      NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
    ];

    $queryBuilder = $this->entityManager
      ->createQueryBuilder()
      ->select('n')
      ->distinct()
      ->from(NewsletterEntity::class, 'n')
      ->innerJoin(SendingQueueEntity::class, 'sq', Join::WITH, 'sq.newsletter = n.id')
      ->innerJoin(ScheduledTaskEntity::class, 'st', Join::WITH, 'st.id = sq.task')
      ->where('n.type IN (:types)')
      ->andWhere('st.status = :statusCompleted')
      ->andWhere('n.deletedAt IS NULL')
      ->orderBy('st.processedAt', 'DESC')
      ->addOrderBy('st.id', 'ASC')
      ->setParameter('types', $types)
      ->setParameter('statusCompleted', SendingQueueEntity::STATUS_COMPLETED);

    if (!empty($segmentIds)) {
      $queryBuilder->innerJoin(NewsletterSegmentEntity::class, 'ns', Join::WITH, 'ns.newsletter = n.id')
        ->andWhere('ns.segment IN (:segmentIds)')
        ->setParameter('segmentIds', $segmentIds);
    }

    return $queryBuilder->getQuery()->getResult();
  }

  /**
   * @return int - number of processed ids
   */
  public function bulkTrash(array $ids): int {
    if (empty($ids)) {
      return 0;
    }
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS, $attachProcessors = true)->info(
      'trashing newsletters', ['id' => $ids]
    );
    // Fetch children id for trashing
    $childrenIds = $this->fetchChildrenIds($ids);
    $ids = array_merge($ids, $childrenIds);

    $this->entityManager->createQueryBuilder()
      ->update(NewsletterEntity::class, 'n')
      ->set('n.deletedAt', 'CURRENT_TIMESTAMP()')
      ->where('n.id IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()->execute();

    // Trash scheduled tasks
    $scheduledTasksTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $sendingQueueTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement("
       UPDATE $scheduledTasksTable t
       JOIN $sendingQueueTable q ON t.`id` = q.`task_id`
       SET t.`deleted_at` = NOW()
       WHERE q.`newsletter_id` IN (:ids)
    ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

    // Trash sending queues
    $this->entityManager->getConnection()->executeStatement("
       UPDATE $sendingQueueTable q
       SET q.`deleted_at` = NOW()
       WHERE q.`newsletter_id` IN (:ids)
    ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

    return count($ids);
  }

  public function bulkRestore(array $ids) {
    if (empty($ids)) {
      return 0;
    }
    // Fetch children ids to restore
    $childrenIds = $this->fetchChildrenIds($ids);
    $ids = array_merge($ids, $childrenIds);

    $this->entityManager->createQueryBuilder()->update(NewsletterEntity::class, 'n')
      ->set('n.deletedAt', ':deletedAt')
      ->where('n.id IN (:ids)')
      ->setParameter('deletedAt', null)
      ->setParameter('ids', $ids)
      ->getQuery()->execute();

    // Restore scheduled tasks and pause running ones
    $scheduledTasksTable = $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
    $sendingQueueTable = $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement("
       UPDATE $scheduledTasksTable t
       JOIN $sendingQueueTable q ON t.`id` = q.`task_id`
       SET t.`deleted_at` = null, t.`status` = IFNULL(t.status, :pausedStatus)
       WHERE q.`newsletter_id` IN (:ids)
    ", [
      'ids' => $ids,
      'pausedStatus' => ScheduledTaskEntity::STATUS_PAUSED,
    ], [
      'ids' => Connection::PARAM_INT_ARRAY,
    ]);

    // Restore sending queues
    $this->entityManager->getConnection()->executeStatement("
       UPDATE $sendingQueueTable q
       SET q.`deleted_at` = null
       WHERE q.`newsletter_id` IN (:ids)
    ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

    return count($ids);
  }

  public function bulkDelete(array $ids) {
    if (empty($ids)) {
      return 0;
    }
    // Fetch children ids for deleting
    $childrenIds = $this->fetchChildrenIds($ids);
    $ids = array_merge($ids, $childrenIds);

    $this->entityManager->transactional(function (EntityManager $entityManager) use ($ids) {
      // Delete statistics data
      $newsletterStatisticsTable = $entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
      $entityManager->getConnection()->executeStatement("
         DELETE s FROM $newsletterStatisticsTable s
         WHERE s.`newsletter_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      $statisticsOpensTable = $entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName();
      $entityManager->getConnection()->executeStatement("
         DELETE s FROM $statisticsOpensTable s
         WHERE s.`newsletter_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      $statisticsClicksTable = $entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName();
      $entityManager->getConnection()->executeStatement("
         DELETE s FROM $statisticsClicksTable s
         WHERE s.`newsletter_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      $statisticsPurchasesTable = $entityManager->getClassMetadata(StatisticsWooCommercePurchaseEntity::class)->getTableName();
      $entityManager->getConnection()->executeStatement("
         DELETE s FROM $statisticsPurchasesTable s
         WHERE s.`newsletter_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      // Delete newsletter posts
      $postsTable = $entityManager->getClassMetadata(NewsletterPostEntity::class)->getTableName();
      $entityManager->getConnection()->executeStatement("
         DELETE np FROM $postsTable np
         WHERE np.`newsletter_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      // Delete newsletter options
      $optionsTable = $entityManager->getClassMetadata(NewsletterOptionEntity::class)->getTableName();
      $entityManager->getConnection()->executeStatement("
         DELETE no FROM $optionsTable no
         WHERE no.`newsletter_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      // Delete newsletter links
      $linksTable = $entityManager->getClassMetadata(NewsletterLinkEntity::class)->getTableName();
      $entityManager->getConnection()->executeStatement("
         DELETE nl FROM $linksTable nl
         WHERE nl.`newsletter_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      // Delete stats notifications tasks
      $scheduledTasksTable = $entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName();
      $statsNotificationsTable = $entityManager->getClassMetadata(StatsNotificationEntity::class)->getTableName();
      $taskIds = $entityManager->getConnection()->executeQuery("
         SELECT task_id FROM $statsNotificationsTable sn
         WHERE sn.`newsletter_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY])->fetchAllAssociative();
      $taskIds = array_column($taskIds, 'task_id');
      $entityManager->getConnection()->executeStatement("
         DELETE st FROM $scheduledTasksTable st
         WHERE st.`id` IN (:ids)
      ", ['ids' => $taskIds], ['ids' => Connection::PARAM_INT_ARRAY]);

      // Delete stats notifications
      $entityManager->getConnection()->executeStatement("
         DELETE sn FROM $statsNotificationsTable sn
         WHERE sn.`newsletter_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      // Delete scheduled tasks and scheduled task subscribers
      $sendingQueueTable = $entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName();
      $scheduledTaskSubscribersTable = $entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName();

      // Delete scheduled tasks subscribers
      $entityManager->getConnection()->executeStatement("
         DELETE ts FROM $scheduledTaskSubscribersTable ts
         JOIN $scheduledTasksTable t ON t.`id` = ts.`task_id`
         JOIN $sendingQueueTable q ON q.`task_id` = t.`id`
         WHERE q.`newsletter_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      $entityManager->getConnection()->executeStatement("
         DELETE t FROM $scheduledTasksTable t
         JOIN $sendingQueueTable q ON t.`id` = q.`task_id`
         WHERE q.`newsletter_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      // Delete sending queues
      $entityManager->getConnection()->executeStatement("
         DELETE q FROM $sendingQueueTable q
         WHERE q.`newsletter_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      // Delete newsletter segments
      $newsletterSegmentsTable = $entityManager->getClassMetadata(NewsletterSegmentEntity::class)->getTableName();
      $entityManager->getConnection()->executeStatement("
         DELETE ns FROM $newsletterSegmentsTable ns
         WHERE ns.`newsletter_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      $queryBuilder = $entityManager->createQueryBuilder();
      $queryBuilder->delete(NewsletterEntity::class, 'n')
        ->where('n.id IN (:ids)')
        ->setParameter('ids', $ids)
        ->getQuery()->execute();
    });
    return count($ids);
  }

  /**
   * @return NewsletterEntity[]
   */
  public function findSendingNotificationHistoryWithoutPausedTask(NewsletterEntity $newsletter): array {
    return $this->entityManager->createQueryBuilder()
      ->select('n')
      ->from(NewsletterEntity::class, 'n')
      ->join('n.queues', 'q')
      ->join('q.task', 't')
      ->where('n.parent = :parent')
      ->andWhere('n.type = :type')
      ->andWhere('n.status = :status')
      ->andWhere('n.deletedAt IS NULL')
      ->andWhere('t.status != :taskStatus')
      ->setParameter('parent', $newsletter)
      ->setParameter('type', NewsletterEntity::TYPE_NOTIFICATION_HISTORY)
      ->setParameter('status', NewsletterEntity::STATUS_SENDING)
      ->setParameter('taskStatus', ScheduledTaskEntity::STATUS_PAUSED)
      ->getQuery()->getResult();
  }

  /**
   * Returns standard newsletters ordered by sentAt
   * @return NewsletterEntity[]
   */
  public function getStandardNewsletterList(): array {
    return $this->entityManager->createQueryBuilder()
      ->select('PARTIAL n.{id,subject,sentAt}')
      ->addSelect('CASE WHEN n.sentAt IS NULL THEN 1 ELSE 0 END as HIDDEN sent_at_is_null')
      ->from(NewsletterEntity::class, 'n')
      ->where('n.type = :typeStandard')
      ->andWhere('n.deletedAt IS NULL')
      ->orderBy('sent_at_is_null', 'DESC')
      ->addOrderBy('n.sentAt', 'DESC')
      ->setParameter('typeStandard', NewsletterEntity::TYPE_STANDARD)
      ->getQuery()
      ->getResult();
  }

  public function prefetchOptions(array $newsletters) {
    $this->entityManager->createQueryBuilder()
      ->select('PARTIAL n.{id}, o, opf')
      ->from(NewsletterEntity::class, 'n')
      ->join('n.options', 'o')
      ->join('o.optionField', 'opf')
      ->where('n.id IN (:newsletters)')
      ->setParameter('newsletters', $newsletters)
      ->getQuery()
      ->getResult();
  }

  public function prefetchSegments(array $newsletters) {
    $this->entityManager->createQueryBuilder()
      ->select('PARTIAL n.{id}, ns, s')
      ->from(NewsletterEntity::class, 'n')
      ->join('n.newsletterSegments', 'ns')
      ->join('ns.segment', 's')
      ->where('n.id IN (:newsletters)')
      ->setParameter('newsletters', $newsletters)
      ->getQuery()
      ->getResult();
  }

  /**
   * Returns a list of emails that are either scheduled standard emails
   * or active automatic emails of the provided types.
   *
   * @param array $automaticEmailTypes
   *
   * @return array
   */
  public function getScheduledStandardEmailsAndActiveAutomaticEmails(array $automaticEmailTypes): array {
    $queryBuilder = $this->entityManager->createQueryBuilder();

    $newsletters = $queryBuilder
      ->select('n')
      ->from(NewsletterEntity::class, 'n')
      ->orWhere(
        $queryBuilder->expr()->andX(
          $queryBuilder->expr()->eq('n.type', ':typeStandard'),
          $queryBuilder->expr()->eq('n.status', ':statusScheduled')
        )
      )
      ->orWhere(
        $queryBuilder->expr()->andX(
          $queryBuilder->expr()->in('n.type', ':automaticEmailTypes'),
          $queryBuilder->expr()->eq('n.status', ':statusActive')
        )
      )
      ->setParameter('typeStandard', NewsletterEntity::TYPE_STANDARD)
      ->setParameter('statusScheduled', NewsletterEntity::STATUS_SCHEDULED)
      ->setParameter('automaticEmailTypes', $automaticEmailTypes)
      ->setParameter('statusActive', NewsletterEntity::STATUS_ACTIVE)
      ->getQuery()
      ->getResult();

    return $newsletters;
  }

  private function fetchChildrenIds(array $parentIds) {
    $ids = $this->entityManager->createQueryBuilder()->select('n.id')
      ->from(NewsletterEntity::class, 'n')
      ->where('n.parent IN (:ids)')
      ->setParameter('ids', $parentIds)
      ->getQuery()->getScalarResult();
    return array_column($ids, 'id');
  }
}
