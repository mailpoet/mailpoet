<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterPostEntity;

/**
 * @extends Repository<NewsletterPostEntity>
 */
class NewsletterPostsRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterPostEntity::class;
  }
}
