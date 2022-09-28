<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * @extends Repository<SendingQueueEntity>
 */
class SendingQueuesRepository extends Repository {
  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    EntityManager $entityManager,
    WPFunctions $wp,
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository
  ) {
    parent::__construct($entityManager);
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
    $this->wp = $wp;
  }

  protected function getEntityClassName() {
    return SendingQueueEntity::class;
  }

  public function findOneByNewsletterAndTaskStatus(NewsletterEntity $newsletter, string $status): ?SendingQueueEntity {
    return $this->entityManager->createQueryBuilder()
      ->select('s')
      ->from(SendingQueueEntity::class, 's')
      ->join('s.task', 't')
      ->where('t.status = :status')
      ->andWhere('s.newsletter = :newsletter')
      ->setParameter('status', $status)
      ->setParameter('newsletter', $newsletter)
      ->getQuery()
      ->getOneOrNullResult();
  }

  public function countAllByNewsletterAndTaskStatus(NewsletterEntity $newsletter, string $status): int {
    return intval($this->entityManager->createQueryBuilder()
      ->select('count(s.task)')
      ->from(SendingQueueEntity::class, 's')
      ->join('s.task', 't')
      ->where('t.status = :status')
      ->andWhere('s.newsletter = :newsletter')
      ->setParameter('status', $status)
      ->setParameter('newsletter', $newsletter)
      ->getQuery()
      ->getSingleScalarResult());
  }

  public function getTaskIdsByNewsletterId(int $newsletterId): array {
    $results = $this->entityManager->createQueryBuilder()
      ->select('IDENTITY(s.task) as task_id')
      ->from(SendingQueueEntity::class, 's')
      ->andWhere('s.newsletter = :newsletter')
      ->setParameter('newsletter', $newsletterId)
      ->getQuery()
      ->getArrayResult();
    return array_map('intval', array_column($results, 'task_id'));
  }

  public function isSubscriberProcessed(SendingQueueEntity $queue, SubscriberEntity $subscriber): bool {
    $task = $queue->getTask();
    if (is_null($task)) return false;
    return $this->scheduledTaskSubscribersRepository->isSubscriberProcessed($task, $subscriber);
  }

  /**
   * @return SendingQueueEntity[]
   */
  public function findAllForSubscriberSentBetween(
    SubscriberEntity $subscriber,
    ?\DateTimeInterface $dateTo,
    ?\DateTimeInterface $dateFrom
  ): array {
    $qb = $this->entityManager->createQueryBuilder()
      ->select('s, n')
      ->from(SendingQueueEntity::class, 's')
      ->join('s.task', 't')
      ->join('t.subscribers', 'tsub')
      ->join('s.newsletter', 'n')
      ->where('t.status = :status')
      ->setParameter('status', ScheduledTaskEntity::STATUS_COMPLETED)
      ->andWhere('t.type = :sendingType')
      ->setParameter('sendingType', 'sending')
      ->andWhere('tsub.subscriber = :subscriber')
      ->setParameter('subscriber', $subscriber);
    if ($dateTo) {
      $qb->andWhere('t.updatedAt < :dateTo')
        ->setParameter('dateTo', $dateTo);
    }
    if ($dateFrom) {
      $qb->andWhere('t.updatedAt > :dateFrom')
        ->setParameter('dateFrom', $dateFrom);
    }
    return $qb->getQuery()->getResult();
  }

  public function pause(SendingQueueEntity $queue): void {
    if ($queue->getCountProcessed() !== $queue->getCountTotal()) {
      $task = $queue->getTask();
      if ($task instanceof ScheduledTaskEntity) {
        $task->setStatus(ScheduledTaskEntity::STATUS_PAUSED);
        $this->flush();
      }
    }
  }

  public function resume(SendingQueueEntity $queue): void {
    $task = $queue->getTask();
    if (!$task instanceof ScheduledTaskEntity) return;

    if ($queue->getCountProcessed() === $queue->getCountTotal()) {
      $processedAt = Carbon::createFromTimestamp($this->wp->currentTime('mysql'));
      $task->setProcessedAt($processedAt);
      $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
      // Update also status of newsletter if necessary
      $newsletter = $queue->getNewsletter();
      if ($newsletter instanceof NewsletterEntity && $newsletter->canBeSetSent()) {
        $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
      }
      $this->flush();
    } else {
      $newsletter = $queue->getNewsletter();
      if (!$newsletter instanceof NewsletterEntity) return;
      $newsletter->setStatus(NewsletterEntity::STATUS_SENDING);
      $task->setStatus(null);
      $this->flush();
    }
  }

  public function deleteByTask(ScheduledTaskEntity $scheduledTask): void {
    $this->entityManager->createQueryBuilder()
      ->delete(SendingQueueEntity::class, 'sq')
      ->where('sq.task = :task')
      ->setParameter('task', $scheduledTask)
      ->getQuery()
      ->execute();
  }
}
