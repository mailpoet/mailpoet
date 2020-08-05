<?php

namespace MailPoet\Subscribers\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;

/**
 * @extends Repository<SubscriberEntity>
 */
class SubscriberStatisticsRepository extends Repository {
  protected function getEntityClassName() {
    return SubscriberEntity::class;
  }

  public function getStatistics(SubscriberEntity $subscriber) {
    return new SubscriberStatistics(
      $this->getStatisticsClickCount($subscriber),
      $this->getStatisticsOpenCount($subscriber),
      $this->getTotalSentCount($subscriber)
    );
  }

  private function getStatisticsClickCount(SubscriberEntity $subscriber): int {
    return $this->getStatisticsCount(StatisticsClickEntity::class, $subscriber);
  }

  private function getStatisticsOpenCount(SubscriberEntity $subscriber): int {
    return $this->getStatisticsCount(StatisticsOpenEntity::class, $subscriber);
  }

  private function getTotalSentCount(SubscriberEntity $subscriber): int {
    return $this->getStatisticsCount(StatisticsNewsletterEntity::class, $subscriber);
  }

  private function getStatisticsCount($entityName, SubscriberEntity $subscriber): int {
    return (int)$this->entityManager->createQueryBuilder()
      ->select('COUNT(DISTINCT stats.newsletter) as cnt')
      ->from($entityName, 'stats')
      ->where('stats.subscriber = :subscriber')
      ->setParameter('subscriber', $subscriber)
      ->getQuery()
      ->getSingleScalarResult();
  }
}
