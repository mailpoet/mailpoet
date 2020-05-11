<?php

namespace MailPoet\Newsletter\Segment;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterSegmentEntity;

/**
 * @extends Repository<NewsletterSegmentEntity>
 */
class NewsletterSegmentRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterSegmentEntity::class;
  }
}
