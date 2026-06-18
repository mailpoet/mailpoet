<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\ScheduledTaskQueuedSubscriberEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;

class ScheduledTaskSubscriberResponseBuilder {
  public function build(ScheduledTaskSubscriberEntity $scheduledSubscriber) {
    $subscriber = $scheduledSubscriber->getSubscriber();
    $task = $scheduledSubscriber->getTask();
    return [
      'processed' => $scheduledSubscriber->getProcessed(),
      'failed' => $scheduledSubscriber->getFailed(),
      'error' => $scheduledSubscriber->getError(),
      'taskId' => $task ? $task->getId() : null,
      'email' => $subscriber ? $subscriber->getEmail() : null,
      'subscriberId' => $subscriber ? $subscriber->getId() : null,
      'firstName' => $subscriber ? $subscriber->getFirstName() : null,
      'lastName' => $subscriber ? $subscriber->getLastName() : null,
    ];
  }

  public function buildForListing(array $scheduledSubscribers) {
    $data = [];
    foreach ($scheduledSubscribers as $scheduledSubscriber) {
      $data[] = $this->build($scheduledSubscriber);
    }
    return $data;
  }

  /**
   * The queue table is lean (no processed/failed/error columns) — every row in
   * it is, by definition, a pending recipient. Project those columns as a
   * synthetic "unprocessed" status so the Unprocessed tab shares the item shape
   * the React listing expects.
   */
  public function buildQueued(ScheduledTaskQueuedSubscriberEntity $queuedSubscriber) {
    $subscriber = $queuedSubscriber->getSubscriber();
    $task = $queuedSubscriber->getTask();
    return [
      'processed' => ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED,
      'failed' => ScheduledTaskSubscriberEntity::FAIL_STATUS_OK,
      'error' => null,
      'taskId' => $task ? $task->getId() : null,
      'email' => $subscriber ? $subscriber->getEmail() : null,
      'subscriberId' => $subscriber ? $subscriber->getId() : null,
      'firstName' => $subscriber ? $subscriber->getFirstName() : null,
      'lastName' => $subscriber ? $subscriber->getLastName() : null,
    ];
  }

  public function buildForQueuedListing(array $queuedSubscribers) {
    $data = [];
    foreach ($queuedSubscribers as $queuedSubscriber) {
      $data[] = $this->buildQueued($queuedSubscriber);
    }
    return $data;
  }
}
