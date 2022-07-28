<?php

namespace MailPoet\Subscribers;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Carbon\CarbonImmutable;
use MailPoetVendor\Doctrine\DBAL\Connection;
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

  public function __construct(
    EntityManager $entityManager,
    WPFunctions $wp
  ) {
    $this->wp = $wp;
    parent::__construct($entityManager);
  }

  protected function getEntityClassName() {
    return SubscriberEntity::class;
  }

  /**
   * @return int
   */
  public function getTotalSubscribers() {
    $query = $this->entityManager
      ->createQueryBuilder()
      ->select('count(n.id)')
      ->from(SubscriberEntity::class, 'n')
      ->where('n.deletedAt IS NULL AND n.status IN (:statuses)')
      ->setParameter('statuses', [
        SubscriberEntity::STATUS_SUBSCRIBED,
        SubscriberEntity::STATUS_UNCONFIRMED,
        SubscriberEntity::STATUS_INACTIVE,
      ])
      ->getQuery();
    return (int)$query->getSingleScalarResult();
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
      $this->bulkRemoveFromAllSegments($ids);

      // Delete subscriber custom fields
      $subscriberCustomFieldTable = $entityManager->getClassMetadata(SubscriberCustomFieldEntity::class)->getTableName();
      $subscriberTable = $entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
      $entityManager->getConnection()->executeStatement("
         DELETE scs FROM $subscriberCustomFieldTable scs
         JOIN $subscriberTable s ON s.`id` = scs.`subscriber_id`
         WHERE scs.`subscriber_id` IN (:ids)
         AND s.`is_woocommerce_user` = false
         AND s.`wp_user_id` IS NULL
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      $queryBuilder = $entityManager->createQueryBuilder();
      $count = $queryBuilder->delete(SubscriberEntity::class, 's')
        ->where('s.id IN (:ids)')
        ->andWhere('s.wpUserId IS NULL')
        ->andWhere('s.isWoocommerceUser = false')
        ->setParameter('ids', $ids)
        ->getQuery()->execute();
    });

    return $count;
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
    ", ['ids' => $ids, 'segment_id' => $segment->getId()], ['ids' => Connection::PARAM_INT_ARRAY]);

    return $count;
  }

  /**
   * @return int - number of processed ids
   */
  public function bulkRemoveFromAllSegments(array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    $subscriberSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $segmentsTable = $this->entityManager->getClassMetadata(SegmentEntity::class)->getTableName();
    $count = (int)$this->entityManager->getConnection()->executeStatement("
       DELETE ss FROM $subscriberSegmentsTable ss
       JOIN $segmentsTable s ON s.id = ss.segment_id AND s.`type` = :typeDefault
       WHERE ss.`subscriber_id` IN (:ids)
    ", [
      'ids' => $ids,
      'typeDefault' => SegmentEntity::TYPE_DEFAULT,
    ], ['ids' => Connection::PARAM_INT_ARRAY]);

    return $count;
  }

  /**
   * @return int - number of processed ids
   */
  public function bulkAddToSegment(SegmentEntity $segment, array $ids): int {
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

    $this->entityManager->transactional(function (EntityManager $entityManager) use ($subscribers, $segment) {
      foreach ($subscribers as $subscriber) {
        $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
        $this->entityManager->persist($subscriberSegment);
      }
      $this->entityManager->flush();
    });

    return count($subscribers);
  }

  public function woocommerceUserExists(): bool {
    $subscribers = $this->entityManager
      ->createQueryBuilder()
      ->select('s')
      ->from(SubscriberEntity::class, 's')
      ->join('s.subscriberSegments', 'ss')
      ->join('ss.segment', 'segment')
      ->where('segment.type = :segmentType')
      ->setParameter('segmentType', SegmentEntity::TYPE_WC_USERS)
      ->andWhere('s.isWoocommerceUser = true')
      ->getQuery()
      ->setMaxResults(1)
      ->execute();

    return count($subscribers) > 0;
  }

   /**
   * @return int - number of processed ids
   */
  public function bulkMoveToSegment(SegmentEntity $segment, array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    $this->bulkRemoveFromAllSegments($ids);
    return $this->bulkAddToSegment($segment, $ids);
  }

  public function bulkUnsubscribe(array $ids): int {
    $this->entityManager->createQueryBuilder()
      ->update(SubscriberEntity::class, 's')
      ->set('s.status', ':status')
      ->where('s.id IN (:ids)')
      ->setParameter('status', SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->setParameter('ids', $ids)
      ->getQuery()->execute();

    return count($ids);
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
    return $this->entityManager->createQueryBuilder()
    ->select('s.id')
    ->from(SubscriberEntity::class, 's')
    ->where('s.email IN (:emails)')
    ->andWhere('s.deletedAt IS NOT NULL')
    ->setParameter('emails', $emails)
    ->getQuery()->getResult();
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
    $now = CarbonImmutable::createFromTimestamp((int)$this->wp->currentTime('timestamp'));
    // Do not update engagement if was recently updated to avoid unnecessary updates in DB
    if ($subscriberEntity->getLastEngagementAt() && $subscriberEntity->getLastEngagementAt() > $now->subMinute()) {
      return;
    }
    // Update last engagement
    $subscriberEntity->setLastEngagementAt($now);
    $this->flush();
  }

  /**
   * @param array $ids
   * @return string[]
   */
  public function getUndeletedSubscribersEmailsByIds(array $ids): array {
    return $this->entityManager->createQueryBuilder()
      ->select('s.email')
      ->from(SubscriberEntity::class, 's')
      ->where('s.deletedAt IS NULL')
      ->andWhere('s.id IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()
      ->getArrayResult();
  }

  public function getMaxSubscriberId(): int {
    $maxSubscriberId = $this->entityManager->createQueryBuilder()
      ->select('MAX(s.id)')
      ->from(SubscriberEntity::class, 's')
      ->getQuery()
      ->getSingleScalarResult();

    return is_int($maxSubscriberId) ? $maxSubscriberId : 0;
  }
}
