<?php

namespace MailPoet\Tasks;

use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;

class Subscribers {
  private $task;

  public function __construct(ScheduledTask $task) {
    $this->task = $task;
  }

  public function setSubscribers(array $subscriber_ids) {
    ScheduledTaskSubscriber::setSubscribers($this->task->id, $subscriber_ids);
  }

  public function getSubscribers() {
    return ScheduledTaskSubscriber::where('task_id', $this->task->id);
  }

  public function isSubscriberProcessed($subscriber_id) {
    $subscriber = $this->getSubscribers()
      ->where('subscriber_id', $subscriber_id)
      ->where('processed', ScheduledTaskSubscriber::STATUS_PROCESSED)
      ->findOne();
    return !empty($subscriber);
  }

  public function removeSubscribers(array $subscribers_to_remove) {
    $this->getSubscribers()
      ->whereIn('subscriber_id', $subscribers_to_remove)
      ->deleteMany();
    $this->checkCompleted();
  }

  public function removeAllSubscribers() {
    $this->getSubscribers()
      ->deleteMany();
    $this->checkCompleted();
  }

  public function updateProcessedSubscribers(array $processed_subscribers) {
    if (!empty($processed_subscribers)) {
      $this->getSubscribers()
        ->whereIn('subscriber_id', $processed_subscribers)
        ->findResultSet()
        ->set('processed', ScheduledTaskSubscriber::STATUS_PROCESSED)
        ->save();
    }
    $this->checkCompleted();
  }

  public function saveSubscriberError($subcriber_id, $error_message) {
    $this->getSubscribers()
      ->where('subscriber_id', $subcriber_id)
      ->findResultSet()
      ->set('failed', ScheduledTaskSubscriber::FAIL_STATUS_FAILED)
      ->set('error', $error_message)
      ->save();
  }

  private function checkCompleted($count = null) {
    if (!$count && !ScheduledTaskSubscriber::getUnprocessedCount($this->task->id)) {
      $this->task->complete();
    }
  }
}
