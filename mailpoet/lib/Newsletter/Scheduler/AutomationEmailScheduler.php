<?php declare(strict_types=1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Tasks\Sending;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class AutomationEmailScheduler {
  /** @var EntityManager */
  private $entityManager;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    EntityManager $entityManager,
    WPFunctions $wp
  ) {
    $this->entityManager = $entityManager;
    $this->wp = $wp;
  }

  public function createSendingTask(NewsletterEntity $email, SubscriberEntity $subscriber): ScheduledTaskEntity {
    if ($email->getType() !== NewsletterEntity::TYPE_AUTOMATION) {
      throw InvalidStateException::create()->withMessage(
        // translators: %s is the type which was given.
        sprintf(__("Email with type 'automation' expected, '%s' given.", 'mailpoet'), $email->getType())
      );
    }

    $task = new ScheduledTaskEntity();
    $task->setType(Sending::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setScheduledAt(Carbon::createFromTimestamp($this->wp->currentTime('timestamp')));
    $task->setPriority(ScheduledTaskEntity::PRIORITY_MEDIUM);
    $this->entityManager->persist($task);

    $taskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber);
    $this->entityManager->persist($taskSubscriber);

    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $queue->setNewsletter($email);
    $queue->setCountToProcess(1);
    $queue->setCountTotal(1);
    $this->entityManager->persist($queue);

    $this->entityManager->flush();
    return $task;
  }
}
