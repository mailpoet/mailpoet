<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;

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
    $statisticsClicksTable = $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName();
    $topIdQuery = $this->entityManager->getConnection()->createQueryBuilder()
      ->select('c.link_id')
      ->addSelect('count(c.id) AS counter')
      ->from($statisticsClicksTable, 'c')
      ->where('c.newsletter_id = :newsletterId')
      ->setParameter('newsletterId', $newsletterId)
      ->groupBy('c.link_id')
      ->orderBy('counter', 'desc')
      ->setMaxResults(1)
      ->execute();
    if (!$topIdQuery instanceof Statement) {
      return null;
    }
    $topId = $topIdQuery->fetch();
    if (is_array($topId) && isset($topId['link_id'])) {
      return $this->findOneById((int)$topId['link_id']);
    }
    return null;
  }
}
