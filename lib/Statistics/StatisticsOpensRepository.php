<?php declare(strict_types=1);

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
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
}
