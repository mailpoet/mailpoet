<?php

namespace MailPoet\Newsletter\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\StatisticsClicksEntity;
use MailPoet\Entities\StatisticsOpensEntity;
use MailPoet\Entities\StatisticsUnsubscribesEntity;
use MailPoetVendor\Doctrine\Common\Collections\Criteria;
use MailPoetVendor\Doctrine\ORM\UnexpectedResultException as UnexpectedResultExceptionAlias;

class NewsletterStatisticsRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterEntity::class;
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return int
   */
  function getTotalSentCount(NewsletterEntity $newsletter) {
    try {
      return (int)$this->doctrine_repository
        ->createQueryBuilder('n')
        ->join('n.queues', 'q')
        ->join('q.task', 't')
        ->select('SUM(q.count_processed)')
        ->where('t.status = :status')
        ->setParameter('status', ScheduledTaskEntity::STATUS_COMPLETED)
        ->getQuery()
        ->getSingleScalarResult();
    } catch (UnexpectedResultExceptionAlias $e) {
      return 0;
    }
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return NewsletterStatistics
   */
  function getStatistics(NewsletterEntity $newsletter) {
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
  function getStatisticsClickCount(NewsletterEntity $newsletter) {
    return $this->getStatisticsCount($newsletter, StatisticsClicksEntity::class);
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return int
   */
  function getStatisticsOpenCount(NewsletterEntity $newsletter) {
    return $this->getStatisticsCount($newsletter, StatisticsOpensEntity::class);
  }

  /**
   * @param NewsletterEntity $newsletter
   * @return int
   */
  function getStatisticsUnsubscribeCount(NewsletterEntity $newsletter) {
    return $this->getStatisticsCount($newsletter, StatisticsUnsubscribesEntity::class);
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
    } catch (UnexpectedResultExceptionAlias $e) {
      return 0;
    }
  }
}
