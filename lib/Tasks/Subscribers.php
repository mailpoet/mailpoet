<?php
namespace MailPoet\Tasks;

use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;

if(!defined('ABSPATH')) exit;

class Subscribers {
  private $task;

  public function __construct(ScheduledTask $task) {
    $this->task = $task;
  }

  function getSubscribers() {
    return ScheduledTaskSubscriber::where('task_id', $this->task->id);
  }

  function isSubscriberProcessed($subscriber_id) {
    $subscriber = $this->getSubscribers()
      ->where('subscriber_id', $subscriber_id)
      ->findOne();
    return !empty($subscriber);
  }

  function removeSubscribers($subscribers_to_remove) {
    $this->getSubscribers()
      ->whereIn('subscriber_id', $subscribers_to_remove)
      ->deleteMany();
    $this->checkCompleted();
  }

  function updateProcessedSubscribers(array $processed_subscribers) {
    $this->getSubscribers()
      ->whereIn('subscriber_id', $processed_subscribers)
      ->findResultSet()
      ->set('processed', ScheduledTaskSubscriber::STATUS_PROCESSED)
      ->save();
    $this->checkCompleted();
  }

  private function checkCompleted() {
    if(!ScheduledTaskSubscriber::getUnprocessedCount($this->task->id)) {
      $this->task->complete();
    }
  }
}
