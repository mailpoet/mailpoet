<?php

namespace MailPoet\Subscribers;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SubscriberIPEntity;

/**
 * @extends Repository<SubscriberIPEntity>
 */
class SubscriberIPsRepository extends Repository {
  protected function getEntityClassName() {
    return SubscriberIPEntity::class;
  }
}
