<?php

namespace MailPoet\NewsletterTemplates;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterTemplateEntity;

/**
 * @method NewsletterTemplateEntity[] findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null)
 * @method NewsletterTemplateEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method NewsletterTemplateEntity|null findOneById(mixed $id)
 * @method void persist(NewsletterTemplateEntity $entity)
 * @method void remove(NewsletterTemplateEntity $entity)
 */
class NewsletterTemplatesRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterTemplateEntity::class;
  }

  /**
   * @return NewsletterTemplateEntity[]
   */
  public function findAllForListing(): array {
    return $this->doctrineRepository->createQueryBuilder('nt')
      ->select('PARTIAL nt.{id,categories,thumbnail,name,description,readonly}')
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
      $template = new NewsletterTemplateEntity();
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
}
