<?php

namespace MailPoet\Newsletter\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoetVendor\Doctrine\ORM\UnexpectedResultException;

class NewsletterStatisticsRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterEntity::class;
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return int
   */
  public function getTotalSentCount(NewsletterEntity $newsletter) {
    try {
      return (int)$this->doctrine_repository
        ->createQueryBuilder('n')
        ->join('n.queues', 'q')
        ->join('q.task', 't')
        ->select('SUM(q.count_processed)')
        ->where('t.status = :status')
        ->setParameter('status', ScheduledTaskEntity::STATUS_COMPLETED)
        ->andWhere('q.newsletter = :newsletter')
        ->setParameter('newsletter', $newsletter)
        ->getQuery()
        ->getSingleScalarResult();
    } catch (UnexpectedResultException $e) {
      return 0;
    }
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return NewsletterStatistics
   */
  public function getStatistics(NewsletterEntity $newsletter) {
    return new NewsletterStatistics(
      $this->getStatisticsClickCount($newsletter),
      $this->getStatisticsOpenCount($newsletter),
      $this->getStatisticsUnsubscribeCount($newsletter),
      $this->getTotalSentCount($newsletter)
    );
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return int
   */
  public function getStatisticsClickCount(NewsletterEntity $newsletter) {
    return $this->getStatisticsCount($newsletter, StatisticsClickEntity::class);
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return int
   */
  public function getStatisticsOpenCount(NewsletterEntity $newsletter) {
    return $this->getStatisticsCount($newsletter, StatisticsOpenEntity::class);
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return int
   */
  public function getStatisticsUnsubscribeCount(NewsletterEntity $newsletter) {
    return $this->getStatisticsCount($newsletter, StatisticsUnsubscribeEntity::class);
  }

  private function getStatisticsCount(NewsletterEntity $newsletter, $statistics_entity_name) {
    try {
      $qb = $this->entity_manager
        ->createQueryBuilder();
      return $qb->select('COUNT(DISTINCT stats.subscriber_id) as cnt')
        ->from($statistics_entity_name, 'stats')
        ->where('stats.newsletter = :newsletter')
        ->setParameter('newsletter', $newsletter)
        ->getQuery()
        ->getSingleScalarResult();
    } catch (UnexpectedResultException $e) {
      return 0;
    }
  }
}
