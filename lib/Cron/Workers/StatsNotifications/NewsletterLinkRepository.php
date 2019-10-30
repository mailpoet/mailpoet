<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterLinkEntity;

class NewsletterLinkRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterLinkEntity::class;
  }

  /**
   * @param int $newsletter_id
   * @return NewsletterLinkEntity|null
   */
  public function findTopLinkForNewsletter($newsletter_id) {
    return $this->doctrine_repository
      ->createQueryBuilder('nl')
      ->join('nl.clicks', 'c')
      ->addSelect('COUNT(c.id) AS HIDDEN counter')
      ->where('nl.newsletter = :newsletterId')
      ->setParameter('newsletterId', $newsletter_id)
      ->groupBy('nl.id')
      ->orderBy('counter', 'desc')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();
  }

}
