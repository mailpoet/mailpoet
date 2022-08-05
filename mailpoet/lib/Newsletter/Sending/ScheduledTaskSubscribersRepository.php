<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoetVendor\Doctrine\ORM\QueryBuilder;

/**
 * @extends Repository<ScheduledTaskSubscriberEntity>
 */
class ScheduledTaskSubscribersRepository extends Repository {
  protected function getEntityClassName() {
    return ScheduledTaskSubscriberEntity::class;
  }

  public function isSubscriberProcessed(ScheduledTaskEntity $task, SubscriberEntity $subscriber): bool {
    $scheduledTaskSubscriber = $this
      ->doctrineRepository
      ->createQueryBuilder('sts')
      ->andWhere('sts.processed = 1')
      ->andWhere('sts.task = :task')
      ->andWhere('sts.subscriber = :subscriber')
      ->setParameter('subscriber', $subscriber)
      ->setParameter('task', $task)
      ->getQuery()
      ->getOneOrNullResult();
    return !empty($scheduledTaskSubscriber);
  }

  public function createOrUpdate(array $data): ?ScheduledTaskSubscriberEntity {
    if (!isset($data['task_id'], $data['subscriber_id'])) {
      return null;
    }

    $taskSubscriber = $this->findOneBy(['task' => $data['task_id'], 'subscriber' => $data['subscriber_id']]);
    if (!$taskSubscriber) {
      $task = $this->entityManager->getReference(ScheduledTaskEntity::class, (int)$data['task_id']);
      $subscriber = $this->entityManager->getReference(SubscriberEntity::class, (int)$data['subscriber_id']);
      if (!$task || !$subscriber) throw new InvalidStateException();

      $taskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber);
      $this->persist($taskSubscriber);
    }

    $processed = $data['processed'] ?? ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED;
    $failed = $data['failed'] ?? ScheduledTaskSubscriberEntity::FAIL_STATUS_OK;

    $taskSubscriber->setProcessed($processed);
    $taskSubscriber->setFailed($failed);
    $this->flush();
    return $taskSubscriber;
  }

  public function countSubscriberIdsBatchForTask(int $taskId, int $lastProcessedSubscriberId): int {
    $queryBuilder = $this->getBaseSubscribersIdsBatchForTaskQuery($taskId, $lastProcessedSubscriberId);
    $countSubscribers = $queryBuilder
      ->select('count(sts.subscriber)')
      ->getQuery()
      ->getSingleScalarResult();

    return intval($countSubscribers);
  }

  public function getSubscriberIdsBatchForTask(int $taskId, int $lastProcessedSubscriberId, int $limit): array {
    $queryBuilder = $this->getBaseSubscribersIdsBatchForTaskQuery($taskId, $lastProcessedSubscriberId);
    $subscribersIds = $queryBuilder
      ->select('IDENTITY(sts.subscriber) AS subscriber_id')
      ->orderBy('sts.subscriber', 'asc')
      ->setMaxResults($limit)
      ->getQuery()
      ->getSingleColumnResult();

    return $subscribersIds;
  }

  private function getBaseSubscribersIdsBatchForTaskQuery(int $taskId, int $lastProcessedSubscriberId): QueryBuilder {
    return $this->entityManager
      ->createQueryBuilder()
      ->from(ScheduledTaskSubscriberEntity::class, 'sts')
      ->andWhere('sts.task = :taskId')
      ->andWhere('sts.subscriber > :lastProcessedSubscriberId')
      ->andWhere('sts.processed = :status')
      ->setParameter('taskId', $taskId)
      ->setParameter('lastProcessedSubscriberId', $lastProcessedSubscriberId)
      ->setParameter('status', ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED);
  }
}
