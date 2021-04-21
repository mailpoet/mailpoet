<?php declare(strict_types=1);

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Tasks\Sending;

/**
 * @extends Repository<StatisticsOpenEntity>
 */
class StatisticsOpensRepository extends Repository {
  protected function getEntityClassName(): string {
    return StatisticsOpenEntity::class;
  }

  public function recalculateSubscriberScore(SubscriberEntity $subscriber): void {
    $subscriber->setEngagementScoreUpdatedAt(new \DateTimeImmutable());
    $newslettersSentCount = $this
      ->entityManager
      ->createQueryBuilder()
      ->select('count(DISTINCT task.id)')
      ->from(ScheduledTaskSubscriberEntity::class, 'scheduledTaskSubscriber')
      ->where('scheduledTaskSubscriber.subscriber = :subscriber')
      ->setParameter('subscriber', $subscriber)
      ->join('scheduledTaskSubscriber.task', 'task')
      ->andWhere('task.type = :sending')
      ->setParameter('sending', Sending::TASK_TYPE)
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
      ->where('opens.subscriber = :subscriberId')
      ->setParameter('subscriberId', $subscriber)
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
