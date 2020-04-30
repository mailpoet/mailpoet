<?php

namespace MailPoet\Newsletter\Sending;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SendingQueueEntity;

/**
 * @method SendingQueueEntity[] findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null)
 * @method SendingQueueEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method SendingQueueEntity|null findOneById(mixed $id)
 * @method void persist(SendingQueueEntity $entity)
 * @method void remove(SendingQueueEntity $entity)
 */
class SendingQueuesRepository extends Repository {
  protected function getEntityClassName() {
    return SendingQueueEntity::class;
  }
}
