<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoetVendor\Doctrine\DBAL\Result;

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
    if (!$topIdQuery instanceof Result) {
      return null;
    }
    $topId = $topIdQuery->fetch();
    if (is_array($topId) && isset($topId['link_id']) && is_numeric($topId['link_id'])) {
      return $this->findOneById((int)$topId['link_id']);
    }
    return null;
  }

  /**
   * @return string[]
   */
  public function findUrlsByNewsletterId(int $newsletterId): array {
    $urls = $this->entityManager->createQueryBuilder()
      ->select('l.url')
      ->from(NewsletterLinkEntity::class, 'l')
      ->where('l.newsletter = :newsletterId')
      ->setParameter('newsletterId', $newsletterId)
      ->groupBy('l.url')
      ->orderBy('l.url', 'ASC')
      ->getQuery()
      ->getSingleColumnResult();

    $result = [];
    foreach ($urls as $url) {
      if (is_string($url) && $url !== '') {
        $result[] = $url;
      }
    }
    return $result;
  }

  /** @param int[] $ids */
  public function deleteByNewsletterIds(array $ids): void {
    $this->entityManager->createQueryBuilder()
      ->delete(NewsletterLinkEntity::class, 'l')
      ->where('l.newsletter IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();

    // delete was done via DQL, make sure the entities are also detached from the entity manager
    $this->detachAll(function (NewsletterLinkEntity $entity) use ($ids) {
      $newsletter = $entity->getNewsletter();
      return $newsletter && in_array($newsletter->getId(), $ids, true);
    });
  }
}
