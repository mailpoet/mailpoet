<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\NewsletterTemplates;

use MailPoet\Config\Env;
use MailPoet\Config\PopulatorData\Templates\NewsletterBlank1Column;
use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterTemplateEntity;

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
      ->select('PARTIAL nt.{id,categories,thumbnail,name,readonly,updatedAt}')
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
      // Backward compatibility for importing templates exported from older versions
      if (strpos($data['thumbnail'], 'data:image') === 0) {
        $data['thumbnail_data'] = $data['thumbnail'];
      } else {
        $template->setThumbnail($data['thumbnail']);
      }
    }

    if (isset($data['thumbnail_data'])) {
      $template->setThumbnailData($data['thumbnail_data']);
    }

    if (isset($data['body']) && is_string($data['body'])) {
      $decodedBody = json_decode($data['body'], true);
      $template->setBody(is_array($decodedBody) ? $decodedBody : null);
    }

    if (isset($data['categories'])) {
      $template->setCategories($data['categories']);
    }

    $this->entityManager->flush();
    return $template;
  }

  public function duplicate(NewsletterTemplateEntity $template): NewsletterTemplateEntity {
    $duplicate = new NewsletterTemplateEntity(
      // translators: %s is the name of the template that is copied.
      sprintf(__('Copy of %s', 'mailpoet'), $template->getName())
    );
    $duplicate->setBody($template->getBody());
    $duplicate->setCategories($this->getSavedCategories($template));
    $duplicate->setThumbnail($template->getThumbnail());
    $thumbnailData = $template->getThumbnailData();
    if ($thumbnailData !== null) {
      $duplicate->setThumbnailData($thumbnailData);
    }
    $this->entityManager->persist($duplicate);
    $this->entityManager->flush();
    return $duplicate;
  }

  public function createFromBlank(string $name): NewsletterTemplateEntity {
    $blank = (new NewsletterBlank1Column(Env::$assetsUrl))->get();
    $template = new NewsletterTemplateEntity($name);
    $body = json_decode($blank['body'], true);
    $template->setBody(is_array($body) ? $body : null);
    $template->setCategories(json_encode(['saved', NewsletterEntity::TYPE_STANDARD]) ?: '[]');
    $template->setThumbnail($blank['thumbnail']);
    $this->entityManager->persist($template);
    $this->entityManager->flush();
    return $template;
  }

  /**
   * Built-in templates use [type, "all"|"blank"], saved ones use ["saved", type],
   * and the old importer added "saved" last. Copies always get ["saved", type].
   */
  private function getSavedCategories(NewsletterTemplateEntity $template): string {
    $categories = json_decode($template->getCategories(), true);
    $type = NewsletterEntity::TYPE_STANDARD;
    foreach (is_array($categories) ? $categories : [] as $category) {
      // Recently sent templates use ["recent"] and are deleted by cleanRecentlySent(), so their copies become standard.
      if (is_string($category) && !in_array($category, ['saved', 'all', 'blank', 'recent'], true)) {
        $type = $category;
        break;
      }
    }
    return json_encode(['saved', $type]) ?: '[]';
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

    // delete was done via DQL, make sure the entities are also detached from the entity manager
    $this->detachAll(function (NewsletterTemplateEntity $entity) use ($recentIds) {
      return $entity->getCategories() === self::RECENTLY_SENT_CATEGORIES && !in_array($entity->getId(), $recentIds, true);
    });
  }

  public function getRecentlySentCount(): int {
    return (int)$this->doctrineRepository->createQueryBuilder('nt')
      ->select('COUNT(nt.id)')
      ->where('nt.categories = :categories')
      ->setParameter('categories', self::RECENTLY_SENT_CATEGORIES)
      ->getQuery()
      ->getSingleScalarResult();
  }

  public function getIdsOfEditableTemplates(): array {
    $result = $this->doctrineRepository->createQueryBuilder('nt')
      ->select('nt.id')
      ->where('nt.readonly = :readonly')
      ->setParameter('readonly', false)
      ->getQuery()
      ->getArrayResult();
    return array_column($result, 'id');
  }
}
