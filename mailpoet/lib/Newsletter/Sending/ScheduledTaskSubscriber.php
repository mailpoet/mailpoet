<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;

/**
 * A single sending-task recipient lives in one of two tables depending on its
 * state: pending recipients sit in the queue (`scheduled_task_queued_subscribers`),
 * processed ones are moved to the log (`scheduled_task_subscribers`). This wraps
 * whichever entity represents the recipient and exposes a state-oriented API so
 * callers polling for completion don't have to know which table it came from.
 */
class ScheduledTaskSubscriber {
  private ?ScheduledTaskSubscriberEntity $processed;

  private ?ScheduledTaskQueuedSubscriberEntity $queued;

  private function __construct(
    ?ScheduledTaskSubscriberEntity $processed,
    ?ScheduledTaskQueuedSubscriberEntity $queued
  ) {
    $this->processed = $processed;
    $this->queued = $queued;
  }

  public static function fromProcessed(ScheduledTaskSubscriberEntity $entity): self {
    return new self($entity, null);
  }

  public static function fromQueued(ScheduledTaskQueuedSubscriberEntity $entity): self {
    return new self(null, $entity);
  }

  public function isPending(): bool {
    return $this->queued !== null;
  }

  public function wasProcessed(): bool {
    return $this->processed !== null
      && $this->processed->getProcessed() === ScheduledTaskSubscriberEntity::STATUS_PROCESSED;
  }

  public function hasFailed(): bool {
    return $this->processed !== null
      && $this->processed->getFailed() === ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED;
  }

  public function getError(): ?string {
    return $this->processed !== null ? $this->processed->getError() : null;
  }

  public function getTask(): ?ScheduledTaskEntity {
    if ($this->processed !== null) {
      return $this->processed->getTask();
    }
    if ($this->queued !== null) {
      return $this->queued->getTask();
    }
    return null;
  }

  public function getSubscriberId(): ?int {
    if ($this->processed !== null) {
      return $this->processed->getSubscriberId();
    }
    if ($this->queued !== null) {
      return $this->queued->getSubscriberId();
    }
    return null;
  }
}
