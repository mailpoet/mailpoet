<?php

namespace MailPoet\Tasks\Subscribers;

use MailPoet\Models\ScheduledTaskSubscriber;

use function MailPoetVendor\array_column;

class BatchIterator implements \Iterator, \Countable {
  private $task_id;
  private $batch_size;
  private $last_processed_id = 0;
  private $batch_last_id;

  public function __construct($task_id, $batch_size) {
    if ($task_id <= 0) {
      throw new \Exception('Task ID must be greater than zero');
    } elseif ($batch_size <= 0) {
      throw new \Exception('Batch size must be greater than zero');
    }
    $this->task_id = (int)$task_id;
    $this->batch_size = (int)$batch_size;
  }

  public function rewind() {
    $this->last_processed_id = 0;
  }

  public function current() {
    $subscribers = $this->getSubscribers()
      ->orderByAsc('subscriber_id')
      ->limit($this->batch_size)
      ->findArray();
    $subscribers = array_column($subscribers, 'subscriber_id');
    $this->batch_last_id = end($subscribers);
    return $subscribers;
  }

  public function key() {
    return null;
  }

  public function next() {
    $this->last_processed_id = $this->batch_last_id;
  }

  public function valid() {
    return $this->count() > 0;
  }

  public function count() {
    return $this->getSubscribers()->count();
  }

  private function getSubscribers() {
    return ScheduledTaskSubscriber::select('subscriber_id')
      ->where('task_id', $this->task_id)
      ->whereGt('subscriber_id', $this->last_processed_id)
      ->where('processed', ScheduledTaskSubscriber::STATUS_UNPROCESSED);
  }
}
