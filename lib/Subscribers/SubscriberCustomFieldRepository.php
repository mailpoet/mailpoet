<?php

namespace MailPoet\Subscribers;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SubscriberCustomFieldEntity;

/**
 * @extends Repository<SubscriberCustomFieldEntity>
 */
class SubscriberCustomFieldRepository extends Repository {
  protected function getEntityClassName() {
    return SubscriberCustomFieldEntity::class;
  }
}
