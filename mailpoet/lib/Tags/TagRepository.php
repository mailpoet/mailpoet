<?php declare(strict_types = 1);

namespace MailPoet\Tags;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\TagEntity;

/**
 * @extends Repository<TagEntity>
 */
class TagRepository extends Repository {
  protected function getEntityClassName() {
    return TagEntity::class;
  }
}
