<?php

namespace MailPoet\Statistics;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Carbon\Carbon;

/**
 * @extends Repository<StatisticsUnsubscribeEntity>
 */
class StatisticsUnsubscribesRepository extends Repository {
  protected function getEntityClassName() {
    return StatisticsUnsubscribeEntity::class;
  }

  /**
   * @param array $data
   * @return StatisticsUnsubscribeEntity
   */
  public function createOrUpdate($data) {
    if (isset($data['id'])) {
      $entity = $this->findOneById((int)$data['id']);
    }
    if (!isset($entity)) {
      if (!isset($data['newsletter'], $data['queue'], $data['subscriber'])) {
        throw new \Exception('Newsletter, SendingQueue and Subscriber entities are required for creation');
      }
      assert($data['newsletter'] instanceof NewsletterEntity);
      assert($data['queue'] instanceof SendingQueueEntity);
      assert($data['subscriber'] instanceof SubscriberEntity);
      $entity = new StatisticsUnsubscribeEntity($data['newsletter'], $data['queue'], $data['subscriber']);
      $this->entityManager->persist($entity);
    }
    if (isset($data['created_at'])) $entity->setCreatedAt(new Carbon($data['created_at']));
    $this->entityManager->flush();
    return $entity;
  }
}
