<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * @extends Repository<NewsletterPostEntity>
 */
class NewsletterPostsRepository extends Repository {
  private const NEWEST_POST_ID_OPTION_PREFIX = 'mailpoet_notification_newest_post_id_';

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    EntityManager $entityManager,
    WPFunctions $wp
  ) {
    parent::__construct($entityManager);
    $this->wp = $wp;
  }

  protected function getEntityClassName() {
    return NewsletterPostEntity::class;
  }

  public function getNewestSitePostId(): int {
    global $wpdb;
    return (int)$wpdb->get_var("SELECT MAX(ID) FROM {$wpdb->posts}");
  }

  /**
   * Saves the highest post ID on the site when a post notification is sent.
   * Posts with a higher ID were created after that email.
   */
  public function saveNewestPostIdAtSend(int $newsletterId, int $postId): void {
    $this->wp->updateOption(self::NEWEST_POST_ID_OPTION_PREFIX . $newsletterId, $postId, false);
  }

  public function getNewestPostIdAtLastSend(int $newsletterId): ?int {
    $postId = $this->wp->getOption(self::NEWEST_POST_ID_OPTION_PREFIX . $newsletterId, null);
    if (is_numeric($postId)) {
      return (int)$postId;
    }
    // Emails sent before this option existed: use the newest post they included
    $sentPostId = $this->entityManager->createQueryBuilder()
      ->select('MAX(p.postId)')
      ->from(NewsletterPostEntity::class, 'p')
      ->where('p.newsletter = :newsletterId')
      ->setParameter('newsletterId', $newsletterId)
      ->getQuery()
      ->getSingleScalarResult();
    return is_numeric($sentPostId) ? (int)$sentPostId : null;
  }

  /** @param int[] $ids */
  public function deleteByNewsletterIds(array $ids): void {
    $this->entityManager->createQueryBuilder()
      ->delete(NewsletterPostEntity::class, 'p')
      ->where('p.newsletter IN (:ids)')
      ->setParameter('ids', $ids)
      ->getQuery()
      ->execute();
    foreach ($ids as $id) {
      $this->wp->deleteOption(self::NEWEST_POST_ID_OPTION_PREFIX . $id);
    }

    // delete was done via DQL, make sure the entities are also detached from the entity manager
    $this->detachAll(function (NewsletterPostEntity $entity) use ($ids) {
      $newsletter = $entity->getNewsletter();
      return $newsletter && in_array($newsletter->getId(), $ids, true);
    });
  }
}
