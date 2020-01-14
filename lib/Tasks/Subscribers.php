<?php

namespace MailPoet\Tasks;

use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;

class Subscribers {
  private $task;

  public function __construct(ScheduledTask $task) {
    $this->task = $task;
  }

  public function setSubscribers(array $subscriberIds) {
    ScheduledTaskSubscriber::setSubscribers($this->task->id, $subscriberIds);
  }

  public function getSubscribers() {
    return ScheduledTaskSubscriber::where('task_id', $this->task->id);
  }

  public function isSubscriberProcessed($subscriberId) {
    $subscriber = $this->getSubscribers()
      ->where('subscriber_id', $subscriberId)
      ->where('processed', ScheduledTaskSubscriber::STATUS_PROCESSED)
      ->findOne();
    return !empty($subscriber);
  }

  public function removeSubscribers(array $subscribersToRemove) {
    $this->getSubscribers()
      ->whereIn('subscriber_id', $subscribersToRemove)
      ->deleteMany();
    $this->checkCompleted();
  }

  public function removeAllSubscribers() {
    $this->getSubscribers()
      ->deleteMany();
    $this->checkCompleted();
  }

  public function updateProcessedSubscribers(array $processedSubscribers) {
    if (!empty($processedSubscribers)) {
      $this->getSubscribers()
        ->whereIn('subscriber_id', $processedSubscribers)
        ->findResultSet()
        ->set('processed', ScheduledTaskSubscriber::STATUS_PROCESSED)
        ->save();
    }
    $this->checkCompleted();
  }

  public function saveSubscriberError($subcriberId, $errorMessage) {
    $this->getSubscribers()
      ->where('subscriber_id', $subcriberId)
      ->findResultSet()
      ->set('failed', ScheduledTaskSubscriber::FAIL_STATUS_FAILED)
      ->set('error', $errorMessage)
      ->save();
  }

  private function checkCompleted($count = null) {
    if (!$count && !ScheduledTaskSubscriber::getUnprocessedCount($this->task->id)) {
      $this->task->complete();
    }
  }
}
