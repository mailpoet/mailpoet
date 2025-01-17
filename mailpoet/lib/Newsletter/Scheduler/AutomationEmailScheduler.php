<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class AutomationEmailScheduler {
  /** @var EntityManager */
  private $entityManager;

  private ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository;

  public function __construct(
    EntityManager $entityManager,
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository
  ) {
    $this->entityManager = $entityManager;
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
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

    $taskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber);
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

  public function getScheduledTaskSubscriber(NewsletterEntity $email, SubscriberEntity $subscriber, AutomationRun $run): ?ScheduledTaskSubscriberEntity {
    $results = $this->entityManager->createQueryBuilder()
      ->select('sts')
      ->from(ScheduledTaskSubscriberEntity::class, 'sts')
      ->join('sts.task', 'st')
      ->join('st.sendingQueue', 'sq')
      ->where('sq.newsletter = :newsletter')
      ->andWhere('sts.subscriber = :subscriber')
      ->andWhere('st.createdAt >= :runCreatedAt')
      ->setParameter('newsletter', $email)
      ->setParameter('subscriber', $subscriber)
      ->setParameter('runCreatedAt', $run->getCreatedAt())
      ->getQuery()
      ->getResult();
    $result = null;
    foreach ($results as $scheduledTaskSubscriber) {
      $task = $scheduledTaskSubscriber->getTask();
      if (!$task instanceof ScheduledTaskEntity) {
        continue;
      }
      $meta = $task->getMeta();
      if (($meta['automation']['run_id'] ?? null) === $run->getId()) {
        $result = $scheduledTaskSubscriber;
        break;
      }
    }
    return $result instanceof ScheduledTaskSubscriberEntity ? $result : null;
  }

  public function saveError(ScheduledTaskSubscriberEntity $scheduledTaskSubscriber, string $error): void {
    $task = $scheduledTaskSubscriber->getTask();
    $subscriber = $scheduledTaskSubscriber->getSubscriber();
    if (!$task || !$subscriber || !$subscriber->getId()) {
      return;
    }
    $this->scheduledTaskSubscribersRepository->saveError($task, $subscriber->getId(), $error);
  }
}
