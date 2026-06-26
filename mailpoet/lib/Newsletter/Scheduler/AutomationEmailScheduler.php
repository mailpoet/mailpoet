<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\Sending\ScheduledTaskQueuedSubscriberRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscriber;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscriberMover;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class AutomationEmailScheduler {
  /** @var EntityManager */
  private $entityManager;

  private ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository;

  private ScheduledTaskSubscriberMover $scheduledTaskSubscriberMover;

  private ScheduledTaskQueuedSubscriberRepository $scheduledTaskQueuedSubscriberRepository;

  public function __construct(
    EntityManager $entityManager,
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository,
    ScheduledTaskSubscriberMover $scheduledTaskSubscriberMover,
    ScheduledTaskQueuedSubscriberRepository $scheduledTaskQueuedSubscriberRepository
  ) {
    $this->entityManager = $entityManager;
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
    $this->scheduledTaskSubscriberMover = $scheduledTaskSubscriberMover;
    $this->scheduledTaskQueuedSubscriberRepository = $scheduledTaskQueuedSubscriberRepository;
  }

  public function createSendingTask(NewsletterEntity $email, SubscriberEntity $subscriber, array $meta): ScheduledTaskEntity {
    if (!in_array($email->getType(), [NewsletterEntity::TYPE_AUTOMATION, NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL], true)) {
      throw InvalidStateException::create()->withMessage(
        // translators: %s is the type which was given.
        sprintf(__("Email with type 'automation' or 'automation_transactional' expected, '%s' given.", 'mailpoet'), $email->getType())
      );
    }

    $task = new ScheduledTaskEntity();
    $task->setType(SendingQueue::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setScheduledAt(Carbon::now()->millisecond(0));
    $task->setPriority(ScheduledTaskEntity::PRIORITY_MEDIUM);
    $task->setMeta($meta);
    $this->entityManager->persist($task);

    $taskSubscriber = new ScheduledTaskQueuedSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($taskSubscriber);

    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $queue->setMeta($meta);
    $queue->setNewsletter($email);
    $queue->setCountToProcess(1);
    $queue->setCountTotal(1);
    $this->entityManager->persist($queue);

    $this->entityManager->flush();
    return $task;
  }

  public function getScheduledTaskSubscriber(NewsletterEntity $email, SubscriberEntity $subscriber, AutomationRun $run): ?ScheduledTaskSubscriber {
    // a finished send (sent or failed) was moved to the log
    $processed = $this->findRunTaskSubscriber(ScheduledTaskSubscriberEntity::class, 'sts', $email, $subscriber, $run);
    if ($processed instanceof ScheduledTaskSubscriberEntity) {
      return ScheduledTaskSubscriber::fromProcessed($processed);
    }

    // a still-pending send sits in the queue
    $queued = $this->findRunTaskSubscriber(ScheduledTaskQueuedSubscriberEntity::class, 'stsq', $email, $subscriber, $run);
    if ($queued instanceof ScheduledTaskQueuedSubscriberEntity) {
      return ScheduledTaskSubscriber::fromQueued($queued);
    }

    return null;
  }

  public function saveError(ScheduledTaskSubscriber $scheduledTaskSubscriber, string $error): void {
    $task = $scheduledTaskSubscriber->getTask();
    $subscriberId = $scheduledTaskSubscriber->getSubscriberId();
    if (!$task || !$subscriberId) {
      return;
    }
    if ($scheduledTaskSubscriber->isPending()) {
      $this->scheduledTaskSubscriberMover->moveFailedToLog($task, $subscriberId, $error);
    } else {
      $this->scheduledTaskSubscribersRepository->saveError($task, $subscriberId, $error);
    }
    $this->scheduledTaskQueuedSubscriberRepository->checkCompleted($task);
  }

  /**
   * @param class-string $entityClass
   * @return ScheduledTaskSubscriberEntity|ScheduledTaskQueuedSubscriberEntity|null
   */
  private function findRunTaskSubscriber(string $entityClass, string $alias, NewsletterEntity $email, SubscriberEntity $subscriber, AutomationRun $run) {
    $results = $this->entityManager->createQueryBuilder()
      ->select($alias)
      ->from($entityClass, $alias)
      ->join("$alias.task", 'st')
      ->join('st.sendingQueue', 'sq')
      ->where('sq.newsletter = :newsletter')
      ->andWhere("$alias.subscriber = :subscriber")
      ->andWhere('st.createdAt >= :runCreatedAt')
      ->setParameter('newsletter', $email)
      ->setParameter('subscriber', $subscriber)
      ->setParameter('runCreatedAt', $run->getCreatedAt())
      ->getQuery()
      ->getResult();

    foreach ($results as $taskSubscriber) {
      if (
        !$taskSubscriber instanceof ScheduledTaskSubscriberEntity
        && !$taskSubscriber instanceof ScheduledTaskQueuedSubscriberEntity
      ) {
        continue;
      }
      $task = $taskSubscriber->getTask();
      if (!$task instanceof ScheduledTaskEntity) {
        continue;
      }
      $meta = $task->getMeta();
      if (($meta['automation']['run_id'] ?? null) === $run->getId()) {
        return $taskSubscriber;
      }
    }
    return null;
  }
}
