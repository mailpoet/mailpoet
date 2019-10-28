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
   * @return NewsletterLinkEntity
   * @throws \MailPoetVendor\Doctrine\ORM\NoResultException
   * @throws \MailPoetVendor\Doctrine\ORM\NonUniqueResultException
   */
  public function findTopLinkForNewsletter($newsletter_id) {
    return $this->doctrine_repository
      ->createQueryBuilder('nlr')
      ->join('nlr.clicks', 'c')
      ->addSelect('COUNT(c.id) AS HIDDEN counter')
      ->where('nlr.newsletter_id = :newsletterId')
      ->setParameter('newsletterId', $newsletter_id)
      ->groupBy('nlr.id')
      ->orderBy('counter', 'desc')
      ->setMaxResults(1)
      ->getQuery()
      ->getSingleResult();
  }

}
