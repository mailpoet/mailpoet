<?php

namespace MailPoet\NewsletterTemplates;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterTemplateEntity;

use function MailPoetVendor\array_column;

/**
 * @extends Repository<NewsletterTemplateEntity>
 */
class NewsletterTemplatesRepository extends Repository {
  const RECENTLY_SENT_CATEGORIES = '["recent"]';
  const RECENTLY_SENT_COUNT = 12;

  protected function getEntityClassName() {
    return NewsletterTemplateEntity::class;
  }

  /**
   * @return NewsletterTemplateEntity[]
   */
  public function findAllForListing(): array {
    return $this->doctrineRepository->createQueryBuilder('nt')
      ->select('PARTIAL nt.{id,categories,thumbnail,name,readonly}')
      ->addOrderBy('nt.readonly', 'ASC')
      ->addOrderBy('nt.createdAt', 'DESC')
      ->addOrderBy('nt.id', 'DESC')
      ->getQuery()
      ->getResult();
  }

  public function createOrUpdate(array $data): NewsletterTemplateEntity {
    $template = !empty($data['newsletter_id'])
      ? $this->findOneBy(['newsletter' => (int)$data['newsletter_id']])
      : null;

    if (!$template) {
      $template = new NewsletterTemplateEntity($data['name'] ?? '');
      $this->entityManager->persist($template);
    }

    if (isset($data['newsletter_id'])) {
      $template->setNewsletter($this->entityManager->getReference(NewsletterEntity::class, (int)$data['newsletter_id']));
    }

    if (isset($data['name'])) {
      $template->setName($data['name']);
    }

    if (isset($data['thumbnail'])) {
      $template->setThumbnail($data['thumbnail']);
    }

    if (isset($data['body'])) {
      $template->setBody(json_decode($data['body'], true));
    }

    if (isset($data['categories'])) {
      $template->setCategories($data['categories']);
    }

    $this->entityManager->flush();
    return $template;
  }

  public function cleanRecentlySent() {
    // fetch 'RECENTLY_SENT_COUNT' of most recent template IDs in 'RECENTLY_SENT_CATEGORIES'
    $recentIds = $this->doctrineRepository->createQueryBuilder('nt')
      ->select('nt.id')
      ->where('nt.categories = :categories')
      ->setParameter('categories', self::RECENTLY_SENT_CATEGORIES)
      ->orderBy('nt.id', 'DESC')
      ->setMaxResults(self::RECENTLY_SENT_COUNT)
      ->getQuery()
      ->getResult();

    // delete all 'RECENTLY_SENT_CATEGORIES' templates except the latest ones selected above
    $this->entityManager->createQueryBuilder()
      ->delete(NewsletterTemplateEntity::class, 'nt')
      ->where('nt.categories = :categories')
      ->andWhere('nt.id NOT IN (:recentIds)')
      ->setParameter('categories', self::RECENTLY_SENT_CATEGORIES)
      ->setParameter('recentIds', array_column($recentIds, 'id'))
      ->getQuery()
      ->execute();
  }
}
