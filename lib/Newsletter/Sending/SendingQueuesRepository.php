<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Doctrine\Repository;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\ORM\EntityManager;

/**
 * @extends Repository<SendingQueueEntity>
 */
class SendingQueuesRepository extends Repository {
  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  public function __construct(
    EntityManager $entityManager,
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository
  ) {
    parent::__construct($entityManager);
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
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
      ->join('tsub.subscriber', 'sub')
      ->join('s.newsletter', 'n')
      ->where('t.status = :status')
      ->setParameter('status', ScheduledTaskEntity::STATUS_COMPLETED)
      ->andWhere('t.type = :sendingType')
      ->setParameter('sendingType', 'sending')
      ->andWhere('sub.id = :subscriber')
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
}
