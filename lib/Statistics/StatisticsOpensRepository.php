<?php declare(strict_types=1);

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;

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
      ->set('s.engagementScoreUpdatedAt', ':verified')
      ->setParameter('verified', null)
      ->getQuery()->execute();
  }

  public function recalculateSegmentScore(SegmentEntity $segment): void {
    $segment->setAverageEngagementScoreUpdatedAt(new \DateTimeImmutable());
    $dateTime = (new Carbon())->subYear();
    $newslettersSentCount = $this
      ->entityManager
      ->createQueryBuilder()
      ->select('count(DISTINCT statisticsNewsletter.newsletter)')
      ->from(StatisticsNewsletterEntity::class, 'statisticsNewsletter')
      ->join('statisticsNewsletter.subscriber', 'subscriber')
      ->join('subscriber.subscriberSegments', 'subscriberSegments')
      ->where('subscriber.status = :subscribed')
      ->andWhere('subscriberSegments.segment = :segment')
      ->andWhere('subscriberSegments.status = :subscribed')
      ->andWhere('subscriber.deletedAt IS NULL')
      ->andWhere('subscriber.engagementScore IS NOT NULL')
      ->andWhere('statisticsNewsletter.sentAt > :dateTime')
      ->setParameter('segment', $segment)
      ->setParameter('subscribed', SubscriberEntity::STATUS_SUBSCRIBED)
      ->setParameter('dateTime', $dateTime)
      ->getQuery()
      ->getSingleScalarResult();
    if ($newslettersSentCount < 3) {
      $this->entityManager->flush();
      return;
    }
    $avgScore = $this
      ->entityManager
      ->createQueryBuilder()
      ->select('avg(DISTINCT subscriber.engagementScore)')
      ->from(SubscriberEntity::class, 'subscriber')
      ->join('subscriber.subscriberSegments', 'subscriberSegments')
      ->where('subscriberSegments.segment = :segment')
      ->andWhere('subscriber.status = :subscribed')
      ->andWhere('subscriberSegments.status = :subscribed')
      ->andWhere('subscriber.engagementScore IS NOT NULL')
      ->setParameter('segment', $segment)
      ->setParameter('subscribed', SubscriberEntity::STATUS_SUBSCRIBED)
      ->getQuery()
      ->getSingleScalarResult();
    $segment->setAverageEngagementScore((float)$avgScore);
    $this->entityManager->flush();
  }

  public function resetNewslettersScoreCalculation(): void {
    $this->entityManager->createQueryBuilder()->update(SegmentEntity::class, 's')
      ->set('s.averageEngagementScoreUpdatedAt', ':verified')
      ->setParameter('verified', null)
      ->getQuery()->execute();
  }
}
