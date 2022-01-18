<?php

namespace MailPoet\Subscribers;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoetVendor\Doctrine\ORM\Query\Expr\Join;

/**
 * @extends Repository<SubscriberSegmentEntity>
 */
class SubscriberSegmentRepository extends Repository {
  protected function getEntityClassName() {
    return SubscriberSegmentEntity::class;
  }

  public function getNonDefaultSubscribedSegments(int $subscriberId): array {
    $qb = $this->entityManager->createQueryBuilder();
    return $qb->select('ss')
      ->from(SubscriberSegmentEntity::class, 'ss')
      ->join('ss.segment', 'seg', Join::WITH, 'seg.type != :typeDefault')
      ->where('ss.subscriber = :subscriberId')
      ->andWhere('ss.status = :subscribed')
      ->setParameter('subscriberId', $subscriberId)
      ->setParameter('subscribed', SubscriberEntity::STATUS_SUBSCRIBED)
      ->setParameter('typeDefault', SegmentEntity::TYPE_DEFAULT)
      ->getQuery()
      ->getResult();
  }

  /**
   * @param SegmentEntity[] $segments
   */
  public function unsubscribeFromSegments(SubscriberEntity $subscriber, array $segments = []): void {
    $subscriber->setConfirmationsCount(0);

    if (!empty($segments)) {
      // unsubscribe from segments
      foreach ($segments as $segment) {
        // do not remove subscriptions to the WP Users segment
        if ($segment->getType() === SegmentEntity::TYPE_WP_USERS) {
          continue;
        }

        $this->createOrUpdate($subscriber, $segment, SubscriberEntity::STATUS_UNSUBSCRIBED);
      }
      $this->entityManager->flush();
    } else {
      $subscriberSegmentTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
      $segmentTable = $this->entityManager->getClassMetadata(SegmentEntity::class)->getTableName();
      $this->entityManager->getConnection()->executeStatement("
         UPDATE $subscriberSegmentTable ss
         JOIN $segmentTable s ON s.`id` = ss.`segment_id` AND ss.`subscriber_id` = :subscriberId
         SET ss.`status` = :status
         WHERE s.`type` != :typeWordPress
      ", [
        'subscriberId' => $subscriber->getId(),
        'status' => SubscriberEntity::STATUS_UNSUBSCRIBED,
        'typeWordPress' => SegmentEntity::TYPE_WP_USERS,
      ]);
      // Refresh SubscriberSegments status
      foreach ($subscriber->getSubscriberSegments() as $subscriberSegment) {
        $this->entityManager->refresh($subscriberSegment);
      }
    }
  }

  public function resetSubscriptions(SubscriberEntity $subscriber, array $segments): void {
    $this->unsubscribeFromSegments($subscriber);
    $this->subscribeToSegments($subscriber, $segments);
  }

  /**
   * @param SegmentEntity[] $segments
   */
  public function subscribeToSegments(SubscriberEntity $subscriber, array $segments): void {
    foreach ($segments as $segment) {
      $this->createOrUpdate($subscriber, $segment, SubscriberEntity::STATUS_SUBSCRIBED);
    }
  }

  public function createOrUpdate(SubscriberEntity $subscriber, SegmentEntity $segment, string $status): SubscriberSegmentEntity {
    $subscriberSegment = $this->findOneBy(['segment' => $segment, 'subscriber' => $subscriber]);
    if ($subscriberSegment instanceof SubscriberSegmentEntity) {
      $subscriberSegment->setStatus($status);
    } else {
      $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, $status);
      $subscriber->getSubscriberSegments()->add($subscriberSegment);
      $this->entityManager->persist($subscriberSegment);
    }
    $this->entityManager->flush();
    return $subscriberSegment;
  }
}
