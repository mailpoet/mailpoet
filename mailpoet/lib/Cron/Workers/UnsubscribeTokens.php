<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Util\Security;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class UnsubscribeTokens extends SimpleWorker {
  const TASK_TYPE = 'unsubscribe_tokens';
  const BATCH_SIZE = 1000;
  const AUTOMATIC_SCHEDULING = false;

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $meta = $task->getMeta();

    if (!isset($meta['last_subscriber_id'])) {
      $meta['last_subscriber_id'] = 0;
    }

    if (!isset($meta['last_newsletter_id'])) {
      $meta['last_newsletter_id'] = 0;
    }

    do {
      $this->cronHelper->enforceExecutionLimit($timer);
      $subscribersCount = $this->addTokens(SubscriberEntity::class, $meta['last_subscriber_id']);
      $task->setMeta($meta);
      $this->scheduledTasksRepository->persist($task);
      $this->scheduledTasksRepository->flush();
    } while ($subscribersCount === self::BATCH_SIZE);
    do {
      $this->cronHelper->enforceExecutionLimit($timer);
      $newslettersCount = $this->addTokens(NewsletterEntity::class, $meta['last_newsletter_id']);
      $task->setMeta($meta);
      $this->scheduledTasksRepository->persist($task);
      $this->scheduledTasksRepository->flush();
    } while ($newslettersCount === self::BATCH_SIZE);
    if ($subscribersCount > 0 || $newslettersCount > 0) {
      return false;
    }
    return true;
  }

  private function addTokens($entityClass, &$lastProcessedId = 0) {
    $security = ContainerWrapper::getInstance()->get(Security::class);
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);

    $queryBuilder = $entityManager->createQueryBuilder();

    $entities = $queryBuilder
      ->select('e')
      ->from($entityClass, 'e')
      ->where('e.unsubscribeToken IS NULL')
      ->andWhere('e.id > :lastProcessedId')
      ->orderBy('e.id', 'ASC')
      ->setMaxResults(self::BATCH_SIZE)
      ->setParameter('lastProcessedId', $lastProcessedId)
      ->getQuery()
      ->getResult();

    if (!is_iterable($entities) || !is_countable($entities)) {
      throw new InvalidStateException('Entities must be iterable');
    }

    foreach ($entities as $entity) {
      $lastProcessedId = $entity->getId();
      $entity->setUnsubscribeToken($security->generateUnsubscribeTokenByEntity($entity));
      $entityManager->persist($entity);
    }

    $entityManager->flush();

    return count($entities);
  }

  public function getNextRunDate() {
    return Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
  }
}
