<?php declare(strict_types = 1);

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

/**
 * @extends Repository<StatisticsOpenEntity>
 */
class StatisticsOpensRepository extends Repository {
  protected function getEntityClassName(): string {
    return StatisticsOpenEntity::class;
  }

  public function recalculateSubscriberScore(SubscriberEntity $subscriber): void {
    $subscriber->setEngagementScoreUpdatedAt(new \DateTimeImmutable());
    $dateTime = (new Carbon())->subYear();
    $newslettersSentCount = $this
      ->entityManager
      ->createQueryBuilder()
      ->select('count(DISTINCT statisticsNewsletter.newsletter)')
      ->from(StatisticsNewsletterEntity::class, 'statisticsNewsletter')
      ->where('statisticsNewsletter.subscriber = :subscriber')
      ->andWhere('statisticsNewsletter.sentAt > :dateTime')
      ->setParameter('subscriber', $subscriber)
      ->setParameter('dateTime', $dateTime)
      ->getQuery()
      ->getSingleScalarResult();
    if ($newslettersSentCount < 3) {
      $this->entityManager->flush();
      return;
    }
    $opens = $this
      ->entityManager
      ->createQueryBuilder()
      ->select('count(DISTINCT opens.newsletter)')
      ->from(StatisticsOpenEntity::class, 'opens')
      ->join('opens.newsletter', 'newsletter')
      ->where('opens.subscriber = :subscriberId')
      ->andWhere('(newsletter.sentAt > :dateTime OR newsletter.sentAt IS NULL)')
      ->andWhere('opens.createdAt > :dateTime')
      ->setParameter('subscriberId', $subscriber)
      ->setParameter('dateTime', $dateTime)
      ->getQuery()
      ->getSingleScalarResult();
    $score = ($opens / $newslettersSentCount) * 100;
    $subscriber->setEngagementScore($score);
    $this->entityManager->flush();
  }

  public function resetSubscribersScoreCalculation() {
    $this->entityManager->createQueryBuilder()->update(SubscriberEntity::class, 's')
      ->set('s.engagementScoreUpdatedAt', ':updatedAt')
      ->setParameter('updatedAt', null)
      ->getQuery()->execute();
  }

  public function recalculateSegmentScore(SegmentEntity $segment): void {
    $segment->setAverageEngagementScoreUpdatedAt(new \DateTimeImmutable());
    $avgScore = $this
      ->entityManager
      ->createQueryBuilder()
      ->select('avg(subscriber.engagementScore)')
      ->from(SubscriberEntity::class, 'subscriber')
      ->join('subscriber.subscriberSegments', 'subscriberSegments')
      ->where('subscriberSegments.segment = :segment')
      ->andWhere('subscriber.status = :subscribed')
      ->andWhere('subscriber.deletedAt IS NULL')
      ->andWhere('subscriberSegments.status = :subscribed')
      ->setParameter('segment', $segment)
      ->setParameter('subscribed', SubscriberEntity::STATUS_SUBSCRIBED)
      ->getQuery()
      ->getSingleScalarResult();
    $segment->setAverageEngagementScore($avgScore === null ? $avgScore : (float)$avgScore);
    $this->entityManager->flush();
  }

  public function resetSegmentsScoreCalculation(): void {
    $this->entityManager->createQueryBuilder()->update(SegmentEntity::class, 's')
      ->set('s.averageEngagementScoreUpdatedAt', ':updatedAt')
      ->setParameter('updatedAt', null)
      ->getQuery()->execute();
  }

  public function getAllForSubscriber(SubscriberEntity $subscriber): QueryBuilder {
    return $this->entityManager->createQueryBuilder()
      ->select('opens.id id, queue.newsletterRenderedSubject, opens.createdAt, userAgent.userAgent')
      ->from(StatisticsOpenEntity::class, 'opens')
      ->join('opens.queue', 'queue')
      ->leftJoin('opens.userAgent', 'userAgent')
      ->where('opens.subscriber = :subscriber')
      ->orderBy('queue.newsletterRenderedSubject')
      ->setParameter('subscriber', $subscriber->getId());
  }
}
