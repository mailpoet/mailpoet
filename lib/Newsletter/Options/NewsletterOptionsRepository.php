<?php

namespace MailPoet\Newsletter\Options;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterOptionEntity;

/**
 * @extends Repository<NewsletterOptionEntity>
 */
class NewsletterOptionsRepository extends Repository {
  protected function getEntityClassName() {
    return NewsletterOptionEntity::class;
  }
}
