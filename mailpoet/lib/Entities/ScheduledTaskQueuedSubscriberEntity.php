<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\SafeToOneAssociationLoadTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="scheduled_task_queued_subscribers")
 */
class ScheduledTaskQueuedSubscriberEntity {
  use CreatedAtTrait;
  use SafeToOneAssociationLoadTrait;

  /**
   * @ORM\Id @ORM\ManyToOne(targetEntity="MailPoet\Entities\ScheduledTaskEntity", inversedBy="queuedSubscribers")
   * @var ScheduledTaskEntity|null
   */
  private $task;

  /**
   * @ORM\Id @ORM\ManyToOne(targetEntity="MailPoet\Entities\SubscriberEntity")
   * @var SubscriberEntity|null
   */
  private $subscriber;

  public function __construct(
    ScheduledTaskEntity $task,
    SubscriberEntity $subscriber
  ) {
    $this->task = $task;
    $this->subscriber = $subscriber;
  }

  /**
   * @return ScheduledTaskEntity|null
   */
  public function getTask() {
    $this->safelyLoadToOneAssociation('task');
    return $this->task;
  }

  public function setTask(ScheduledTaskEntity $task) {
    $this->task = $task;
  }

  /**
   * @return SubscriberEntity|null
   */
  public function getSubscriber() {
    $this->safelyLoadToOneAssociation('subscriber');
    return $this->subscriber;
  }

  /**
   * Get the ID of the subscriber without querying wp_mailpoet_subscribers.
   * $this->getSubscriber->getId() queries wp_mailpoet_subscribers because of
   * the way the SafeToOneAssociationLoadTrait works.
   *
   * @return int|null
   */
  public function getSubscriberId() {
    if ($this->subscriber instanceof SubscriberEntity) {
      return $this->subscriber->getId();
    }

    return null;
  }

  public function setSubscriber(SubscriberEntity $subscriber) {
    $this->subscriber = $subscriber;
  }
}
