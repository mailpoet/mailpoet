<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscribers;

use DateTimeInterface;
use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Entities\SubscriberTagEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\Source;
use MailPoet\Util\License\Features\Subscribers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Query\Expr\Join;

/**
 * @extends Repository<SubscriberEntity>
 */
class SubscribersRepository extends Repository {
  /** @var WPFunctions */
  private $wp;

  protected $ignoreColumnsForUpdate = [
    'wp_user_id',
    'is_woocommerce_user',
    'email',
    'created_at',
    'last_subscribed_at',
  ];

  /** @var SubscriberChangesNotifier */
  private $changesNotifier;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SegmentsCountRecalculator */
  private $segmentsCountRecalculator;

  public function __construct(
    EntityManager $entityManager,
    SubscriberChangesNotifier $changesNotifier,
    WPFunctions $wp,
    SegmentsRepository $segmentsRepository,
    SegmentsCountRecalculator $segmentsCountRecalculator
  ) {
    $this->wp = $wp;
    parent::__construct($entityManager);
    $this->changesNotifier = $changesNotifier;
    $this->segmentsRepository = $segmentsRepository;
    $this->segmentsCountRecalculator = $segmentsCountRecalculator;
  }

  protected function getEntityClassName() {
    return SubscriberEntity::class;
  }

  public function getTotalSubscribers(): int {
    return $this->getCountOfSubscribersForStates([
      SubscriberEntity::STATUS_SUBSCRIBED,
      SubscriberEntity::STATUS_UNCONFIRMED,
      SubscriberEntity::STATUS_INACTIVE,
    ]);
  }

  public function getCountOfSubscribersForStates(array $states): int {
    $query = $this->entityManager
      ->createQueryBuilder()
      ->select('count(n.id)')
      ->from(SubscriberEntity::class, 'n')
      ->where('n.deletedAt IS NULL AND n.status IN (:statuses)')
      ->setParameter('statuses', $states)
      ->getQuery();
    return intval($query->getSingleScalarResult());
  }

  public function invalidateTotalSubscribersCache(): void {
    $this->wp->deleteTransient(Subscribers::SUBSCRIBERS_COUNT_CACHE_KEY);
  }

  public function findBySegment(int $segmentId): array {
    return $this->entityManager
    ->createQueryBuilder()
    ->select('s')
    ->from(SubscriberEntity::class, 's')
    ->join('s.subscriberSegments', 'ss', Join::WITH, 'ss.segment = :segment')
    ->setParameter('segment', $segmentId)
    ->getQuery()->getResult();
  }

  public function findExclusiveSubscribersBySegment(int $segmentId): array {
    return $this->entityManager->createQueryBuilder()
      ->select('s')
      ->from(SubscriberEntity::class, 's')
      ->join('s.subscriberSegments', 'ss', Join::WITH, 'ss.segment = :segment')
      ->leftJoin('s.subscriberSegments', 'ss2', Join::WITH, 'ss2.segment <> :segment AND ss2.status = :subscribed')
      ->leftJoin('ss2.segment', 'seg', Join::WITH, 'seg.deletedAt IS NULL')
      ->groupBy('s.id')
      ->andHaving('COUNT(seg.id) = 0')
      ->setParameter('segment', $segmentId)
      ->setParameter('subscribed', SubscriberEntity::STATUS_SUBSCRIBED)
      ->getQuery()->getResult();
  }

  public function getWooCommerceSegmentSubscriber(string $email): ?SubscriberEntity {
    $subscriber = $this->doctrineRepository->createQueryBuilder('s')
      ->join('s.subscriberSegments', 'ss')
      ->join('ss.segment', 'sg', Join::WITH, 'sg.type = :typeWcUsers')
      ->where('s.isWoocommerceUser = 1')
      ->andWhere('s.status IN (:subscribed, :unconfirmed)')
      ->andWhere('ss.status = :subscribed')
      ->andWhere('s.email = :email')
      ->setParameter('typeWcUsers', SegmentEntity::TYPE_WC_USERS)
      ->setParameter('subscribed', SubscriberEntity::STATUS_SUBSCRIBED)
      ->setParameter('unconfirmed', SubscriberEntity::STATUS_UNCONFIRMED)
      ->setParameter('email', $email)
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();
    return $subscriber instanceof SubscriberEntity ? $subscriber : null;
  }

  /**
   * @return int - number of processed ids
   */
  public function bulkTrash(array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    $this->entityManager->createQueryBuilder()
      ->update(SubscriberEntity::class, 's')
      ->set('s.deletedAt', 'CURRENT_TIMESTAMP()')
      ->where('s.id IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()->execute();

    $this->changesNotifier->subscribersUpdated($ids);
    $this->changesNotifier->subscribersCountChanged($ids);
    $this->invalidateTotalSubscribersCache();
    return count($ids);
  }

  /**
   * @return int - number of processed ids
   */
  public function bulkRestore(array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    $this->entityManager->createQueryBuilder()
      ->update(SubscriberEntity::class, 's')
      ->set('s.deletedAt', ':deletedAt')
      ->where('s.id IN (:ids)')
      ->setParameter('deletedAt', null)
      ->setParameter('ids', $ids)
      ->getQuery()->execute();

    $this->changesNotifier->subscribersUpdated($ids);
    $this->changesNotifier->subscribersCountChanged($ids);
    $this->invalidateTotalSubscribersCache();
    return count($ids);
  }

   /**
   * @return int - number of processed ids
   */
  public function bulkDelete(array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    $count = 0;
    $this->entityManager->transactional(function (EntityManager $entityManager) use ($ids, &$count) {
      // Delete subscriber segments
      $this->removeSubscribersFromAllSegments($ids);

      // Delete subscriber custom fields
      $subscriberCustomFieldTable = $entityManager->getClassMetadata(SubscriberCustomFieldEntity::class)->getTableName();
      $subscriberTable = $entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
      $entityManager->getConnection()->executeStatement("
         DELETE scs FROM $subscriberCustomFieldTable scs
         JOIN $subscriberTable s ON s.`id` = scs.`subscriber_id`
         WHERE scs.`subscriber_id` IN (:ids)
         AND s.`is_woocommerce_user` = false
         AND s.`wp_user_id` IS NULL
      ", ['ids' => $ids], ['ids' => ArrayParameterType::INTEGER]);

      // Delete subscriber tags
      $subscriberTagTable = $entityManager->getClassMetadata(SubscriberTagEntity::class)->getTableName();
      $entityManager->getConnection()->executeStatement("
         DELETE st FROM $subscriberTagTable st
         JOIN $subscriberTable s ON s.`id` = st.`subscriber_id`
         WHERE st.`subscriber_id` IN (:ids)
         AND s.`is_woocommerce_user` = false
         AND s.`wp_user_id` IS NULL
      ", ['ids' => $ids], ['ids' => ArrayParameterType::INTEGER]);

      $queryBuilder = $entityManager->createQueryBuilder();
      $count = $queryBuilder->delete(SubscriberEntity::class, 's')
        ->where('s.id IN (:ids)')
        ->andWhere('s.wpUserId IS NULL')
        ->andWhere('s.isWoocommerceUser = false')
        ->setParameter('ids', $ids)
        ->getQuery()->execute();
    });

    $this->changesNotifier->subscribersDeleted($ids);
    $this->invalidateTotalSubscribersCache();
    return $count;
  }

  public function sendPublicConfirmationEmailWithCap(
    SubscriberEntity $subscriber,
    int $maxConfirmationEmails,
    callable $sendConfirmationEmail
  ): bool {
    if (!$subscriber->getId()) {
      return false;
    }

    $connection = $this->entityManager->getConnection();
    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();

    $claimedRows = (int)$connection->executeStatement(
      "UPDATE $subscriberTable
       SET `count_confirmations` = `count_confirmations` + 1
       WHERE `id` = :id
       AND `count_confirmations` < :max_confirmation_emails",
      [
        'id' => $subscriber->getId(),
        'max_confirmation_emails' => $maxConfirmationEmails,
      ],
      [
        'id' => ParameterType::INTEGER,
        'max_confirmation_emails' => ParameterType::INTEGER,
      ]
    );

    if ($claimedRows !== 1) {
      $this->entityManager->refresh($subscriber);
      return false;
    }

    try {
      if (!$sendConfirmationEmail()) {
        $this->releasePublicConfirmationEmailClaim($subscriberTable, (int)$subscriber->getId());
        $this->entityManager->refresh($subscriber);
        return false;
      }
    } catch (\Throwable $throwable) {
      $this->releasePublicConfirmationEmailClaim($subscriberTable, (int)$subscriber->getId());
      $this->entityManager->refresh($subscriber);
      throw $throwable;
    }

    $connection->executeStatement(
      "UPDATE $subscriberTable
       SET `last_confirmation_email_sent_at` = :sent_at
       WHERE `id` = :id",
      [
        'id' => $subscriber->getId(),
        'sent_at' => Carbon::now()->format('Y-m-d H:i:s'),
      ],
      [
        'id' => ParameterType::INTEGER,
        'sent_at' => ParameterType::STRING,
      ]
    );

    $this->entityManager->refresh($subscriber);
    return true;
  }

  /**
   * @return array{claimed: bool, reason?: string, claim_time?: string, previous_last_confirmation_email_sent_at?: string|null, previous_count_confirmations?: int}
   */
  public function claimAdminConfirmationEmailResend(
    SubscriberEntity $subscriber,
    int $maxConfirmationEmails,
    DateTimeInterface $recentCutoff,
    ?DateTimeInterface $oldestLifecycleDate = null
  ): array {
    if (!$subscriber->getId()) {
      return ['claimed' => false, 'reason' => 'not_found'];
    }

    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $row = $this->getConfirmationResendState($subscriberTable, (int)$subscriber->getId());
    $reason = $this->getConfirmationResendIneligibilityReasonFromRow($row, $maxConfirmationEmails, $recentCutoff, $oldestLifecycleDate);
    if ($reason !== null) {
      $this->entityManager->refresh($subscriber);
      return ['claimed' => false, 'reason' => $reason];
    }

    $previousCountConfirmations = $this->toInt($row['count_confirmations'] ?? 0);
    $previousLastConfirmationEmailSentAt = $this->toStringOrNull($row['last_confirmation_email_sent_at'] ?? null);
    $claimTime = Carbon::now()->millisecond(0)->format('Y-m-d H:i:s');
    $ageCondition = $oldestLifecycleDate instanceof DateTimeInterface
      ? 'AND COALESCE(`last_subscribed_at`, `created_at`) >= :oldest_lifecycle_date'
      : '';
    $lastConfirmationEmailSentAtCondition = $previousLastConfirmationEmailSentAt === null
      ? 'AND `last_confirmation_email_sent_at` IS NULL'
      : 'AND `last_confirmation_email_sent_at` = :previous_last_confirmation_email_sent_at';
    $parameters = [
      'id' => $subscriber->getId(),
      'status' => SubscriberEntity::STATUS_UNCONFIRMED,
      'max_confirmation_emails' => $maxConfirmationEmails,
      'recent_cutoff' => $recentCutoff->format('Y-m-d H:i:s'),
      'claim_time' => $claimTime,
      'previous_count_confirmations' => $previousCountConfirmations,
    ];
    $types = [
      'id' => ParameterType::INTEGER,
      'max_confirmation_emails' => ParameterType::INTEGER,
      'recent_cutoff' => ParameterType::STRING,
      'claim_time' => ParameterType::STRING,
      'previous_count_confirmations' => ParameterType::INTEGER,
    ];
    if ($previousLastConfirmationEmailSentAt !== null) {
      $parameters['previous_last_confirmation_email_sent_at'] = $previousLastConfirmationEmailSentAt;
      $types['previous_last_confirmation_email_sent_at'] = ParameterType::STRING;
    }
    if ($oldestLifecycleDate instanceof DateTimeInterface) {
      $parameters['oldest_lifecycle_date'] = $oldestLifecycleDate->format('Y-m-d H:i:s');
      $types['oldest_lifecycle_date'] = ParameterType::STRING;
    }

    $claimedRows = (int)$this->entityManager->getConnection()->executeStatement(
      "UPDATE $subscriberTable
       SET `count_confirmations` = `count_confirmations` + 1,
         `last_confirmation_email_sent_at` = :claim_time
       WHERE `id` = :id
       AND `status` = :status
       AND `deleted_at` IS NULL
       AND `count_confirmations` < :max_confirmation_emails
       AND (`last_confirmation_email_sent_at` IS NULL OR `last_confirmation_email_sent_at` <= :recent_cutoff)
       AND `count_confirmations` = :previous_count_confirmations
       $lastConfirmationEmailSentAtCondition
       $ageCondition",
      $parameters,
      $types
    );

    $this->entityManager->refresh($subscriber);
    if ($claimedRows !== 1) {
      $row = $this->getConfirmationResendState($subscriberTable, (int)$subscriber->getId());
      return [
        'claimed' => false,
        'reason' => $this->getConfirmationResendIneligibilityReasonFromRow($row, $maxConfirmationEmails, $recentCutoff, $oldestLifecycleDate) ?? 'not_found',
      ];
    }

    return [
      'claimed' => true,
      'claim_time' => $claimTime,
      'previous_last_confirmation_email_sent_at' => $previousLastConfirmationEmailSentAt,
      'previous_count_confirmations' => $previousCountConfirmations,
    ];
  }

  public function releaseAdminConfirmationEmailResendClaim(
    SubscriberEntity $subscriber,
    string $claimTime,
    ?string $previousLastConfirmationEmailSentAt,
    int $previousCountConfirmations
  ): void {
    if (!$subscriber->getId()) {
      return;
    }

    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE $subscriberTable
       SET `count_confirmations` = :previous_count_confirmations,
         `last_confirmation_email_sent_at` = :previous_last_confirmation_email_sent_at
       WHERE `id` = :id
       AND `last_confirmation_email_sent_at` = :claim_time
       AND `count_confirmations` = :claimed_count_confirmations",
      [
        'id' => $subscriber->getId(),
        'claim_time' => $claimTime,
        'previous_last_confirmation_email_sent_at' => $previousLastConfirmationEmailSentAt,
        'previous_count_confirmations' => $previousCountConfirmations,
        'claimed_count_confirmations' => $previousCountConfirmations + 1,
      ],
      [
        'id' => ParameterType::INTEGER,
        'claim_time' => ParameterType::STRING,
        'previous_last_confirmation_email_sent_at' => $previousLastConfirmationEmailSentAt === null ? ParameterType::NULL : ParameterType::STRING,
        'previous_count_confirmations' => ParameterType::INTEGER,
        'claimed_count_confirmations' => ParameterType::INTEGER,
      ]
    );
    $this->entityManager->refresh($subscriber);
  }

  public function completeAdminConfirmationEmailResendClaim(
    SubscriberEntity $subscriber,
    string $claimTime,
    ?string $previousLastConfirmationEmailSentAt,
    int $previousCountConfirmations
  ): void {
    if (!$subscriber->getId()) {
      return;
    }

    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE $subscriberTable
       SET `last_confirmation_email_sent_at` = :previous_last_confirmation_email_sent_at
       WHERE `id` = :id
       AND `last_confirmation_email_sent_at` = :claim_time
       AND `count_confirmations` = :claimed_count_confirmations",
      [
        'id' => $subscriber->getId(),
        'claim_time' => $claimTime,
        'previous_last_confirmation_email_sent_at' => $previousLastConfirmationEmailSentAt,
        'claimed_count_confirmations' => $previousCountConfirmations + 1,
      ],
      [
        'id' => ParameterType::INTEGER,
        'claim_time' => ParameterType::STRING,
        'previous_last_confirmation_email_sent_at' => $previousLastConfirmationEmailSentAt === null ? ParameterType::NULL : ParameterType::STRING,
        'claimed_count_confirmations' => ParameterType::INTEGER,
      ]
    );
    $this->entityManager->refresh($subscriber);
  }

  public function getAdminConfirmationEmailResendIneligibilityReason(
    SubscriberEntity $subscriber,
    int $maxConfirmationEmails,
    DateTimeInterface $recentCutoff,
    ?DateTimeInterface $oldestLifecycleDate = null
  ): ?string {
    if (!$subscriber->getId()) {
      return 'not_found';
    }
    $subscriberTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $row = $this->getConfirmationResendState($subscriberTable, (int)$subscriber->getId());
    return $this->getConfirmationResendIneligibilityReasonFromRow($row, $maxConfirmationEmails, $recentCutoff, $oldestLifecycleDate);
  }

  /**
   * @return array<string, mixed>|false
   */
  private function getConfirmationResendState(string $subscriberTable, int $subscriberId) {
    return $this->entityManager->getConnection()->executeQuery(
      "SELECT `id`, `status`, `deleted_at`, `count_confirmations`, `last_confirmation_email_sent_at`,
         COALESCE(`last_subscribed_at`, `created_at`) AS lifecycle_date
       FROM $subscriberTable
       WHERE `id` = :id",
      ['id' => $subscriberId],
      ['id' => ParameterType::INTEGER]
    )->fetchAssociative();
  }

  /**
   * @param array<string, mixed>|false $row
   */
  private function getConfirmationResendIneligibilityReasonFromRow(
    $row,
    int $maxConfirmationEmails,
    DateTimeInterface $recentCutoff,
    ?DateTimeInterface $oldestLifecycleDate
  ): ?string {
    if (!$row) {
      return 'not_found';
    }
    if (!empty($row['deleted_at'])) {
      return 'deleted';
    }
    if (($row['status'] ?? null) !== SubscriberEntity::STATUS_UNCONFIRMED) {
      return 'not_unconfirmed';
    }
    if ($this->toInt($row['count_confirmations'] ?? 0) >= $maxConfirmationEmails) {
      return 'max_confirmations_reached';
    }
    $lastConfirmationEmailSentAt = $this->toStringOrNull($row['last_confirmation_email_sent_at'] ?? null);
    if ($lastConfirmationEmailSentAt !== null && strtotime($lastConfirmationEmailSentAt) > $recentCutoff->getTimestamp()) {
      return 'recently_sent';
    }
    $lifecycleDate = $this->toStringOrNull($row['lifecycle_date'] ?? null);
    if ($oldestLifecycleDate instanceof DateTimeInterface && $lifecycleDate !== null && strtotime($lifecycleDate) < $oldestLifecycleDate->getTimestamp()) {
      return 'too_old';
    }
    return null;
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

  private function toStringOrNull($value): ?string {
    if ($value === null || $value === '') {
      return null;
    }
    if (is_scalar($value)) {
      return (string)$value;
    }
    return null;
  }

  private function releasePublicConfirmationEmailClaim(string $subscriberTable, int $subscriberId): void {
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE $subscriberTable
       SET `count_confirmations` = `count_confirmations` - 1
       WHERE `id` = :id
       AND `count_confirmations` > 0",
      ['id' => $subscriberId],
      ['id' => ParameterType::INTEGER]
    );
  }

  /**
   * @return int[]
   */
  public function deleteUnconfirmedSubscribersForCleanup(DateTimeInterface $cutoff, int $limit): array {
    if ($limit <= 0) {
      return [];
    }

    $deletedIds = [];
    $this->entityManager->transactional(function (EntityManager $entityManager) use ($cutoff, $limit, &$deletedIds) {
      $subscriberTable = $entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
      $subscriberCustomFieldTable = $entityManager->getClassMetadata(SubscriberCustomFieldEntity::class)->getTableName();
      $subscriberTagTable = $entityManager->getClassMetadata(SubscriberTagEntity::class)->getTableName();

      $confirmationDateIds = $this->findUnconfirmedSubscriberIdsForCleanup(
        $subscriberTable,
        's.`last_confirmation_email_sent_at` <= :cutoff',
        $cutoff,
        $limit
      );

      $legacyCreatedAtIds = $this->findUnconfirmedSubscriberIdsForCleanup(
        $subscriberTable,
        's.`last_confirmation_email_sent_at` IS NULL AND COALESCE(s.`last_subscribed_at`, s.`created_at`) <= :cutoff',
        $cutoff,
        $limit
      );

      $deletedIds = array_values(array_unique(array_merge($confirmationDateIds, $legacyCreatedAtIds)));
      sort($deletedIds);
      $deletedIds = array_slice($deletedIds, 0, $limit);

      if (empty($deletedIds)) {
        return;
      }

      $markedAt = Carbon::now()->format('Y-m-d H:i:s');
      $entityManager->getConnection()->executeStatement(
        "UPDATE $subscriberTable
         SET `deleted_at` = :marked_at
         WHERE `id` IN (:ids)
         AND `status` = :status
         AND `deleted_at` IS NULL
         AND `wp_user_id` IS NULL
         AND `is_woocommerce_user` = 0
         AND (
           `last_confirmation_email_sent_at` <= :cutoff
           OR (
             `last_confirmation_email_sent_at` IS NULL
             AND COALESCE(`last_subscribed_at`, `created_at`) <= :cutoff
           )
         )",
        [
          'ids' => $deletedIds,
          'status' => SubscriberEntity::STATUS_UNCONFIRMED,
          'cutoff' => $cutoff->format('Y-m-d H:i:s'),
          'marked_at' => $markedAt,
        ],
        [
          'ids' => ArrayParameterType::INTEGER,
          'cutoff' => ParameterType::STRING,
          'marked_at' => ParameterType::STRING,
        ]
      );

      $deletedIds = array_map(static function($id): int {
        if (is_int($id)) {
          return $id;
        }
        return is_string($id) ? (int)$id : 0;
      }, $entityManager->getConnection()->executeQuery(
        "SELECT `id`
         FROM $subscriberTable
         WHERE `id` IN (:ids)
         AND `deleted_at` = :marked_at",
        [
          'ids' => $deletedIds,
          'marked_at' => $markedAt,
        ],
        [
          'ids' => ArrayParameterType::INTEGER,
          'marked_at' => ParameterType::STRING,
        ]
      )->fetchFirstColumn());

      if (empty($deletedIds)) {
        return;
      }

      $this->removeSubscribersFromAllSegments($deletedIds);

      $entityManager->getConnection()->executeStatement("
         DELETE scs FROM $subscriberCustomFieldTable scs
         WHERE scs.`subscriber_id` IN (:ids)
      ", ['ids' => $deletedIds], ['ids' => ArrayParameterType::INTEGER]);

      $entityManager->getConnection()->executeStatement("
         DELETE st FROM $subscriberTagTable st
         WHERE st.`subscriber_id` IN (:ids)
      ", ['ids' => $deletedIds], ['ids' => ArrayParameterType::INTEGER]);

      $deletedCount = (int)$entityManager->getConnection()->executeStatement(
        "DELETE FROM $subscriberTable
         WHERE `id` IN (:ids)
         AND `deleted_at` = :marked_at",
        [
          'ids' => $deletedIds,
          'marked_at' => $markedAt,
        ],
        [
          'ids' => ArrayParameterType::INTEGER,
          'marked_at' => ParameterType::STRING,
        ]
      );

      if ($deletedCount !== count($deletedIds)) {
        throw new \RuntimeException('Unconfirmed subscribers cleanup deleted an unexpected number of rows.');
      }
    });

    if (!empty($deletedIds)) {
      $this->changesNotifier->subscribersDeleted($deletedIds);
      $this->invalidateTotalSubscribersCache();
    }
    return $deletedIds;
  }

  /**
   * @return int[]
   */
  private function findUnconfirmedSubscriberIdsForCleanup(
    string $subscriberTable,
    string $datePredicate,
    DateTimeInterface $cutoff,
    int $limit
  ): array {
    return array_map(static function($id): int {
      if (is_int($id)) {
        return $id;
      }
      return is_string($id) ? (int)$id : 0;
    }, $this->entityManager->getConnection()->executeQuery(
      "SELECT s.`id`
       FROM $subscriberTable s
       WHERE s.`status` = :status
       AND s.`deleted_at` IS NULL
       AND s.`wp_user_id` IS NULL
       AND s.`is_woocommerce_user` = 0
       AND $datePredicate
       ORDER BY s.`id` ASC
       LIMIT :limit",
      [
        'status' => SubscriberEntity::STATUS_UNCONFIRMED,
        'cutoff' => $cutoff->format('Y-m-d H:i:s'),
        'limit' => $limit,
      ],
      [
        'cutoff' => ParameterType::STRING,
        'limit' => ParameterType::INTEGER,
      ]
    )->fetchFirstColumn());
  }

  /**
   * Recalculate the denormalized segments_count for the given subscribers.
   * Exposed for raw-SQL write paths (e.g. import) that bypass the repository's
   * own segment mutators.
   *
   * @param int[] $subscriberIds
   */
  public function recalculateSegmentsCount(array $subscriberIds): void {
    $this->segmentsCountRecalculator->recalculateForSubscribers($subscriberIds);
  }

  /**
   * @return int - number of processed ids
   */
  public function bulkRemoveFromSegment(SegmentEntity $segment, array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    $subscriberSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $count = (int)$this->entityManager->getConnection()->executeStatement("
       DELETE ss FROM $subscriberSegmentsTable ss
       WHERE ss.`subscriber_id` IN (:ids)
       AND ss.`segment_id` = :segment_id
    ", ['ids' => $ids, 'segment_id' => $segment->getId()], ['ids' => ArrayParameterType::INTEGER]);

    $this->segmentsCountRecalculator->recalculateForSubscribers($ids);
    $this->changesNotifier->subscribersUpdated($ids);
    return $count;
  }

  /**
   * @return int - number of processed ids
   */
  public function bulkRemoveFromAllSegments(array $ids): int {
    $count = $this->removeSubscribersFromAllSegments($ids);
    $this->changesNotifier->subscribersUpdated($ids);
    return $count;
  }

  /**
   * @return int - number of processed ids
   */
  public function bulkAddToSegment(SegmentEntity $segment, array $ids): int {
    $count = $this->addSubscribersToSegment($segment, $ids);
    $this->changesNotifier->subscribersUpdated($ids);
    return $count;
  }

   /**
   * @return int - number of processed ids
   */
  public function bulkMoveToSegment(SegmentEntity $segment, array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    $this->removeSubscribersFromAllSegments($ids);
    $count = $this->addSubscribersToSegment($segment, $ids);

    $this->changesNotifier->subscribersUpdated($ids);
    return $count;
  }

  public function bulkUnsubscribe(array $ids): int {
    $this->entityManager->createQueryBuilder()
      ->update(SubscriberEntity::class, 's')
      ->set('s.status', ':status')
      ->where('s.id IN (:ids)')
      ->setParameter('status', SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->setParameter('ids', $ids)
      ->getQuery()->execute();

    $this->changesNotifier->subscribersUpdated($ids);
    $this->changesNotifier->subscribersCountChanged($ids);
    $this->invalidateTotalSubscribersCache();
    return count($ids);
  }

  public function bulkUpdateLastSendingAt(array $ids, DateTimeInterface $dateTime): int {
    if (empty($ids)) {
      return 0;
    }
    $this->entityManager->createQueryBuilder()
      ->update(SubscriberEntity::class, 's')
      ->set('s.lastSendingAt', ':lastSendingAt')
      ->where('s.id IN (:ids)')
      ->setParameter('lastSendingAt', $dateTime)
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();
    return count($ids);
  }

  public function bulkUpdateEngagementScoreUpdatedAt(array $ids, ?DateTimeInterface $dateTime): void {
    if (empty($ids)) {
      return;
    }
    $this->entityManager->createQueryBuilder()
      ->update(SubscriberEntity::class, 's')
      ->set('s.engagementScoreUpdatedAt', ':dateTime')
      ->where('s.id IN (:ids)')
      ->setParameter('dateTime', $dateTime)
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();
  }

  public function findWpUserIdAndEmailByEmails(array $emails): array {
    return $this->entityManager->createQueryBuilder()
      ->select('s.wpUserId AS wp_user_id, LOWER(s.email) AS email')
      ->from(SubscriberEntity::class, 's')
      ->where('s.email IN (:emails)')
      ->setParameter('emails', $emails)
      ->getQuery()->getResult();
  }

  public function findIdAndEmailByEmails(array $emails): array {
    return $this->entityManager->createQueryBuilder()
      ->select('s.id, s.email')
      ->from(SubscriberEntity::class, 's')
      ->where('s.email IN (:emails)')
      ->setParameter('emails', $emails)
      ->getQuery()->getResult();
  }

  /**
   * @return int[]
   */
  public function findIdsOfDeletedByEmails(array $emails): array {
    $rows = $this->entityManager->createQueryBuilder()
    ->select('s.id')
    ->from(SubscriberEntity::class, 's')
    ->where('s.email IN (:emails)')
    ->andWhere('s.deletedAt IS NOT NULL')
    ->setParameter('emails', $emails)
    ->getQuery()->getResult();
    return array_values(array_map('intval', array_column(is_array($rows) ? $rows : [], 'id')));
  }

  public function getCurrentWPUser(): ?SubscriberEntity {
    $wpUser = WPFunctions::get()->wpGetCurrentUser();
    if (empty($wpUser->ID)) {
      return null; // Don't look up a subscriber for guests
    }
    return $this->findOneBy(['wpUserId' => $wpUser->ID]);
  }

  public function findByUpdatedScoreNotInLastMonth(int $limit): array {
    $dateTime = (new Carbon())->subMonths(1);
    return $this->entityManager->createQueryBuilder()
      ->select('s')
      ->from(SubscriberEntity::class, 's')
      ->where('s.engagementScoreUpdatedAt IS NULL')
      ->orWhere('s.engagementScoreUpdatedAt < :dateTime')
      ->setParameter('dateTime', $dateTime)
      ->getQuery()
      ->setMaxResults($limit)
      ->getResult();
  }

  public function maybeUpdateLastEngagement(SubscriberEntity $subscriberEntity): void {
    $now = $this->getCurrentDateTime();
    // Do not update engagement if was recently updated to avoid unnecessary updates in DB
    if ($subscriberEntity->getLastEngagementAt() && $subscriberEntity->getLastEngagementAt() > $now->subMinute()) {
      return;
    }
    // Update last engagement
    $subscriberEntity->setLastEngagementAt($now);
    $this->flush();
  }

  public function maybeUpdateLastOpenAt(SubscriberEntity $subscriberEntity): void {
    $now = $this->getCurrentDateTime();
    // Avoid unnecessary DB calls
    if ($subscriberEntity->getLastOpenAt() && $subscriberEntity->getLastOpenAt() > $now->subMinute()) {
      return;
    }
    $subscriberEntity->setLastOpenAt($now);
    $subscriberEntity->setLastEngagementAt($now);
    $this->flush();
  }

  public function maybeUpdateLastClickAt(SubscriberEntity $subscriberEntity): void {
    $now = $this->getCurrentDateTime();
    // Avoid unnecessary DB calls
    if ($subscriberEntity->getLastClickAt() && $subscriberEntity->getLastClickAt() > $now->subMinute()) {
      return;
    }
    $subscriberEntity->setLastClickAt($now);
    $subscriberEntity->setLastEngagementAt($now);
    $this->flush();
  }

  public function maybeUpdateLastPurchaseAt(SubscriberEntity $subscriberEntity): void {
    $now = $this->getCurrentDateTime();
    // Avoid unnecessary DB calls
    if ($subscriberEntity->getLastPurchaseAt() && $subscriberEntity->getLastPurchaseAt() > $now->subMinute()) {
      return;
    }
    $subscriberEntity->setLastPurchaseAt($now);
    $subscriberEntity->setLastEngagementAt($now);
    $this->flush();
  }

  public function maybeUpdateLastPageViewAt(SubscriberEntity $subscriberEntity): void {
    $now = $this->getCurrentDateTime();
    // Avoid unnecessary DB calls
    if ($subscriberEntity->getLastPageViewAt() && $subscriberEntity->getLastPageViewAt() > $now->subMinute()) {
      return;
    }
    $subscriberEntity->setLastPageViewAt($now);
    $subscriberEntity->setLastEngagementAt($now);
    $this->flush();
  }

  public function getMaxSubscriberId(): int {
    $maxSubscriberId = $this->entityManager->createQueryBuilder()
      ->select('MAX(s.id)')
      ->from(SubscriberEntity::class, 's')
      ->getQuery()
      ->getSingleScalarResult();

    return intval($maxSubscriberId);
  }

  /**
   * Returns count of subscribers who subscribed after given date regardless of their current status.
   * @return int
   */
  public function getCountOfLastSubscribedAfter(\DateTimeInterface $subscribedAfter): int {
    $result = $this->entityManager->createQueryBuilder()
      ->select('COUNT(s.id)')
      ->from(SubscriberEntity::class, 's')
      ->where('s.lastSubscribedAt > :lastSubscribedAt')
      ->andWhere('s.deletedAt IS NULL')
      ->setParameter('lastSubscribedAt', $subscribedAfter)
      ->getQuery()
      ->getSingleScalarResult();
    return intval($result);
  }

  /**
   * Returns count of subscribers who unsubscribed after given date regardless of their current status.
   * @return int
   */
  public function getCountOfUnsubscribedAfter(\DateTimeInterface $unsubscribedAfter): int {
    $result = $this->entityManager->createQueryBuilder()
      ->select('COUNT(DISTINCT s.id)')
      ->from(StatisticsUnsubscribeEntity::class, 'su')
      ->join('su.subscriber', 's')
      ->andWhere('su.createdAt > :unsubscribedAfter')
      ->andWhere('s.deletedAt IS NULL')
      ->setParameter('unsubscribedAfter', $unsubscribedAfter)
      ->getQuery()
      ->getSingleScalarResult();
    return intval($result);
  }

  /**
   * Returns count of subscribers who subscribed to a list after given date regardless of their current global status.
   */
  public function getListLevelCountsOfSubscribedAfter(\DateTimeInterface $date): array {
    $data = $this->entityManager->createQueryBuilder()
      ->select('seg.id, seg.name, seg.type, seg.averageEngagementScore, COUNT(ss.id) as count')
      ->from(SubscriberSegmentEntity::class, 'ss')
      ->join('ss.subscriber', 's')
      ->join('ss.segment', 'seg')
      ->where('ss.updatedAt > :date')
      ->andWhere('ss.status = :segment_status')
      ->andWhere('s.lastSubscribedAt > :date') // subscriber subscribed at some point after the date
      ->andWhere('s.deletedAt IS NULL')
      ->andWhere('seg.deletedAt IS NULL') // no trashed lists and disabled WP Users list
      ->setParameter('date', $date)
      ->setParameter('segment_status', SubscriberEntity::STATUS_SUBSCRIBED)
      ->groupBy('ss.segment')
      ->getQuery()
      ->getArrayResult();
    return $data;
  }

  /**
   * Returns count of subscribers who unsubscribed from a list after given date regardless of their current global status.
   */
  public function getListLevelCountsOfUnsubscribedAfter(\DateTimeInterface $date): array {
    return $this->entityManager->createQueryBuilder()
      ->select('seg.id, seg.name, seg.type, seg.averageEngagementScore, COUNT(ss.id) as count')
      ->from(SubscriberSegmentEntity::class, 'ss')
      ->join('ss.subscriber', 's')
      ->join('ss.segment', 'seg')
      ->where('ss.updatedAt > :date')
      ->andWhere('ss.status = :segment_status')
      ->andWhere('s.deletedAt IS NULL')
      ->andWhere('seg.deletedAt IS NULL') // no trashed lists and disabled WP Users list
      ->setParameter('date', $date)
      ->setParameter('segment_status', SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->groupBy('ss.segment')
      ->getQuery()
      ->getArrayResult();
  }

  /**
   * @return int - number of processed ids
   */
  public function bulkAddTag(TagEntity $tag, array $ids): int {
    $count = $this->addTagToSubscribers($tag, $ids);
    $this->changesNotifier->subscribersUpdated($ids);
    return $count;
  }

  /**
   * @return int - number of processed ids
   */
  public function bulkRemoveTag(TagEntity $tag, array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    $subscriberTagsTable = $this->entityManager->getClassMetadata(SubscriberTagEntity::class)->getTableName();
    $count = (int)$this->entityManager->getConnection()->executeStatement("
       DELETE st FROM $subscriberTagsTable st
       WHERE st.`subscriber_id` IN (:ids)
       AND st.`tag_id` = :tag_id
    ", ['ids' => $ids, 'tag_id' => $tag->getId()], ['ids' => ArrayParameterType::INTEGER]);

    $this->changesNotifier->subscribersUpdated($ids);
    return $count;
  }

  public function removeOrphanedSubscribersFromWpSegment(): void {
    global $wpdb;

    $segmentId = $this->segmentsRepository->getWpUsersSegment()->getId();

    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subscriberSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $segmentsTable = $this->entityManager->getClassMetadata(SegmentEntity::class)->getTableName();
    $deletedAt = $this->getCurrentDateTime()->format('Y-m-d H:i:s');

    $this->entityManager->wrapInTransaction(function () use ($segmentId, $subscribersTable, $subscriberSegmentsTable, $segmentsTable, $deletedAt, $wpdb): void {
      // Hard-delete broken subscribers in the WP-Users segment when they have no
      // email, or when they have no WP user ID and no other list to belong to.
      $this->entityManager->getConnection()->executeStatement(
        "DELETE s
         FROM {$subscribersTable} s
         INNER JOIN {$subscriberSegmentsTable} ss ON s.id = ss.subscriber_id
         WHERE ss.segment_id = :segmentId
           AND (
             s.email = ''
             OR (
               s.wp_user_id IS NULL
               AND s.is_woocommerce_user = 0
               AND NOT EXISTS (
                 SELECT 1 FROM {$subscriberSegmentsTable} ss_other
                 INNER JOIN {$segmentsTable} seg ON seg.id = ss_other.segment_id
                 WHERE ss_other.subscriber_id = s.id
                   AND seg.type != :wpType
                   AND seg.deleted_at IS NULL
               )
             )
           )",
        [
          'segmentId' => $segmentId,
          'wpType' => SegmentEntity::TYPE_WP_USERS,
        ],
        [
          'segmentId' => ParameterType::INTEGER,
          'wpType' => ParameterType::STRING,
        ]
      );

      // Trash subscribers whose WP user is gone, who are only on the WP-Users list,
      // and who are not WC customers — they have nowhere left to belong, but we keep
      // them as soft-deleted so admins can recover them if needed.
      $this->entityManager->getConnection()->executeStatement(
        "UPDATE {$subscribersTable} s
         LEFT JOIN {$wpdb->users} u ON u.id = s.wp_user_id
         SET s.deleted_at = :deletedAt, s.status = :unconfirmed
         WHERE s.deleted_at IS NULL
           AND s.is_woocommerce_user = 0
           AND s.wp_user_id IS NOT NULL
           AND u.id IS NULL
           AND EXISTS (
             SELECT 1 FROM {$subscriberSegmentsTable} ss_wp
             WHERE ss_wp.subscriber_id = s.id AND ss_wp.segment_id = :segmentId
           )
           AND NOT EXISTS (
             SELECT 1 FROM {$subscriberSegmentsTable} ss_other
             INNER JOIN {$segmentsTable} seg ON seg.id = ss_other.segment_id
             WHERE ss_other.subscriber_id = s.id
               AND seg.type != :wpType
               AND seg.deleted_at IS NULL
           )",
        [
          'segmentId' => $segmentId,
          'unconfirmed' => SubscriberEntity::STATUS_UNCONFIRMED,
          'wpType' => SegmentEntity::TYPE_WP_USERS,
          'deletedAt' => $deletedAt,
        ],
        [
          'segmentId' => ParameterType::INTEGER,
          'unconfirmed' => ParameterType::STRING,
          'wpType' => ParameterType::STRING,
          'deletedAt' => ParameterType::STRING,
        ]
      );

      // Remove WP-Users segment memberships for orphans.
      $this->entityManager->getConnection()->executeStatement(
        "DELETE ss
         FROM {$subscriberSegmentsTable} ss
         INNER JOIN {$subscribersTable} s ON s.id = ss.subscriber_id
         LEFT JOIN {$wpdb->users} u ON u.id = s.wp_user_id
         WHERE ss.segment_id = :segmentId
           AND (s.wp_user_id IS NULL OR u.id IS NULL)",
        ['segmentId' => $segmentId],
        ['segmentId' => ParameterType::INTEGER]
      );

      // Detach subscribers from non-existent WP users and mark the source.
      $this->entityManager->getConnection()->executeStatement(
        "UPDATE {$subscribersTable} s
         LEFT JOIN {$wpdb->users} u ON u.id = s.wp_user_id
         SET s.wp_user_id = NULL, s.source = :source
         WHERE s.wp_user_id IS NOT NULL AND u.id IS NULL",
        ['source' => Source::WORDPRESS_USER_DELETED],
        ['source' => ParameterType::STRING]
      );
    });
  }

  public function removeByWpUserIds(array $wpUserIds) {
    $queryBuilder = $this->entityManager->createQueryBuilder();

    $queryBuilder
      ->delete(SubscriberEntity::class, 's')
      ->where('s.wpUserId IN (:wpUserIds)')
      ->setParameter('wpUserIds', $wpUserIds);

    return $queryBuilder->getQuery()->execute();
  }

  /**
   * @return int - number of processed ids
   */
  private function removeSubscribersFromAllSegments(array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    $subscriberSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $segmentsTable = $this->entityManager->getClassMetadata(SegmentEntity::class)->getTableName();

    // Count unique subscribers that will have segments removed
    $uniqueSubscribersCount = $this->entityManager->getConnection()->executeQuery("
       SELECT COUNT(DISTINCT subscriber_id)
       FROM $subscriberSegmentsTable ss
       JOIN $segmentsTable s ON s.id = ss.segment_id AND s.`type` = :typeDefault
       WHERE ss.`subscriber_id` IN (:ids)
    ", [
      'ids' => $ids,
      'typeDefault' => SegmentEntity::TYPE_DEFAULT,
    ], ['ids' => ArrayParameterType::INTEGER])->fetchOne();

    // Delete the segment relationships
    $this->entityManager->getConnection()->executeStatement("
       DELETE ss FROM $subscriberSegmentsTable ss
       JOIN $segmentsTable s ON s.id = ss.segment_id AND s.`type` = :typeDefault
       WHERE ss.`subscriber_id` IN (:ids)
    ", [
      'ids' => $ids,
      'typeDefault' => SegmentEntity::TYPE_DEFAULT,
    ], ['ids' => ArrayParameterType::INTEGER]);

    $this->segmentsCountRecalculator->recalculateForSubscribers($ids);

    return is_numeric($uniqueSubscribersCount) ? (int)$uniqueSubscribersCount : 0;
  }

  /**
   * @return int - number of processed ids
   */
  private function addSubscribersToSegment(SegmentEntity $segment, array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    $subscribers = $this->entityManager
      ->createQueryBuilder()
      ->select('s')
      ->from(SubscriberEntity::class, 's')
      ->leftJoin('s.subscriberSegments', 'ss', Join::WITH, 'ss.segment = :segment')
      ->where('s.id IN (:ids)')
      ->andWhere('ss.segment IS NULL')
      ->setParameter('ids', $ids)
      ->setParameter('segment', $segment)
      ->getQuery()->execute();

    $subscribers = is_array($subscribers) ? array_values(array_filter($subscribers, function ($s) {
      return $s instanceof SubscriberEntity;
    })) : [];

    $this->entityManager->transactional(function (EntityManager $entityManager) use ($subscribers, $segment) {
      foreach ($subscribers as $subscriber) {
        $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
        $this->entityManager->persist($subscriberSegment);
      }
      $this->entityManager->flush();
    });

    if ($subscribers !== []) {
      $this->segmentsCountRecalculator->recalculateForSubscribers(array_map(function (SubscriberEntity $subscriber): int {
        return (int)$subscriber->getId();
      }, $subscribers));
    }

    return count($subscribers);
  }

  /**
   * @return int - number of processed ids
   */
  private function addTagToSubscribers(TagEntity $tag, array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    /** @var SubscriberEntity[] $subscribers */
    $subscribers = $this->entityManager
      ->createQueryBuilder()
      ->select('s')
      ->from(SubscriberEntity::class, 's')
      ->leftJoin('s.subscriberTags', 'st', Join::WITH, 'st.tag = :tag')
      ->where('s.id IN (:ids)')
      ->andWhere('st.tag IS NULL')
      ->setParameter('ids', $ids)
      ->setParameter('tag', $tag)
      ->getQuery()->execute();

    $this->entityManager->wrapInTransaction(function (EntityManager $entityManager) use ($subscribers, $tag) {
      foreach ($subscribers as $subscriber) {
        $subscriberTag = new SubscriberTagEntity($tag, $subscriber);
        $entityManager->persist($subscriberTag);
      }
      $entityManager->flush();
    });

    return count($subscribers);
  }

  private function getCurrentDateTime(): Carbon {
    return Carbon::now()->setMilliseconds(0);
  }
}
