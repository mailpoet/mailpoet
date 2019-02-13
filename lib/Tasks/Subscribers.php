<?php
namespace MailPoet\Tasks;

use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;

if (!defined('ABSPATH')) exit;

class Subscribers {
  private $task;

  public function __construct(ScheduledTask $task) {
    $this->task = $task;
  }

  function setSubscribers(array $subscriber_ids) {
    ScheduledTaskSubscriber::setSubscribers($this->task->id, $subscriber_ids);
  }

  function getSubscribers() {
    return ScheduledTaskSubscriber::where('task_id', $this->task->id);
  }

  function isSubscriberProcessed($subscriber_id) {
    $subscriber = $this->getSubscribers()
      ->where('subscriber_id', $subscriber_id)
      ->where('processed', ScheduledTaskSubscriber::STATUS_PROCESSED)
      ->findOne();
    return !empty($subscriber);
  }

  function removeSubscribers(array $subscribers_to_remove) {
    $this->getSubscribers()
      ->whereIn('subscriber_id', $subscribers_to_remove)
      ->deleteMany();
    $this->checkCompleted();
  }

  function removeAllSubscribers() {
    $this->getSubscribers()
      ->deleteMany();
    $this->checkCompleted();
  }

  function updateProcessedSubscribers(array $processed_subscribers) {
    if (!empty($processed_subscribers)) {
      $this->getSubscribers()
        ->whereIn('subscriber_id', $processed_subscribers)
        ->findResultSet()
        ->set('processed', ScheduledTaskSubscriber::STATUS_PROCESSED)
        ->save();
    }
    $this->checkCompleted();
  }

  function saveSubscriberError($subcriber_id, $error_message) {
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
