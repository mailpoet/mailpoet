<?php

namespace MailPoet\Newsletter\Sending;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\SendingQueueEntity;

/**
 * @extends Repository<SendingQueueEntity>
 */
class SendingQueuesRepository extends Repository {
  protected function getEntityClassName() {
    return SendingQueueEntity::class;
  }
}
