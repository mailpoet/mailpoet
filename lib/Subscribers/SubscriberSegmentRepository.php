<?php

namespace MailPoet\Subscribers;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SubscriberSegmentEntity;

/**
 * @extends Repository<SubscriberSegmentEntity>
 */
class SubscriberSegmentRepository extends Repository {
  protected function getEntityClassName() {
    return SubscriberSegmentEntity::class;
  }
}
