<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterLinkEntity;

/**
 * @extends Repository<NewsletterLinkEntity>
 */
class NewsletterLinkRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterLinkEntity::class;
  }

  /**
   * @param int $newsletterId
   * @return NewsletterLinkEntity|null
   */
  public function findTopLinkForNewsletter($newsletterId) {
    return $this->doctrineRepository
      ->createQueryBuilder('nl')
      ->join('nl.clicks', 'c')
      ->addSelect('COUNT(c.id) AS HIDDEN counter')
      ->where('nl.newsletter = :newsletterId')
      ->setParameter('newsletterId', $newsletterId)
      ->groupBy('nl.id')
      ->orderBy('counter', 'desc')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();
  }
}
