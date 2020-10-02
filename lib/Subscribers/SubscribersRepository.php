<?php

namespace MailPoet\Subscribers;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Doctrine\ORM\Query\Expr\Join;

/**
 * @extends Repository<SubscriberEntity>
 */
class SubscribersRepository extends Repository {
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

  /**
   * @return int
   */
  public function getTotalSubscribersWithoutWPUsers() {
    $query = $this->entityManager
      ->createQueryBuilder()
      ->select('count(n.id)')
      ->from(SubscriberEntity::class, 'n')
      ->where('n.deletedAt IS NULL AND n.status IN (:statuses) AND n.wpUserId IS NULL')
      ->setParameter('statuses', [
        SubscriberEntity::STATUS_SUBSCRIBED,
        SubscriberEntity::STATUS_UNCONFIRMED,
        SubscriberEntity::STATUS_INACTIVE,
      ])
      ->getQuery();
    return (int)$query->getSingleScalarResult();
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

    $this->entityManager->transactional(function (EntityManager $entityManager) use ($ids) {
      // Delete subscriber segments
      $this->bulkRemoveFromAllSegments($ids);

      // Delete subscriber custom fields
      $subscriberCustomFieldTable = $entityManager->getClassMetadata(SubscriberCustomFieldEntity::class)->getTableName();
      $entityManager->getConnection()->executeUpdate("
         DELETE scs FROM $subscriberCustomFieldTable scs
         WHERE scs.`subscriber_id` IN (:ids)
      ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

      $queryBuilder = $entityManager->createQueryBuilder();
      $queryBuilder->delete(SubscriberEntity::class, 's')
        ->where('s.id IN (:ids)')
        ->setParameter('ids', $ids)
        ->getQuery()->execute();
    });

    return count($ids);
  }

  /**
   * @return int - number of processed ids
   */
  public function bulkRemoveFromSegment(SegmentEntity $segment, array $ids): int {
    if (empty($ids)) {
      return 0;
    }

    $subscriberSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $count = $this->entityManager->getConnection()->executeUpdate("
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
    $count = $this->entityManager->getConnection()->executeUpdate("
       DELETE ss FROM $subscriberSegmentsTable ss
       WHERE ss.`subscriber_id` IN (:ids)
    ", ['ids' => $ids], ['ids' => Connection::PARAM_INT_ARRAY]);

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
}
