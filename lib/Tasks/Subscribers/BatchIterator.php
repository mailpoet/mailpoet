<?php

namespace MailPoet\Tasks\Subscribers;

use MailPoet\Models\ScheduledTaskSubscriber;

use function MailPoetVendor\array_column;

class BatchIterator implements \Iterator, \Countable {
  private $task_id;
  private $batch_size;
  private $last_processed_id = 0;
  private $batch_last_id;

  public function __construct($taskId, $batchSize) {
    if ($taskId <= 0) {
      throw new \Exception('Task ID must be greater than zero');
    } elseif ($batchSize <= 0) {
      throw new \Exception('Batch size must be greater than zero');
    }
    $this->taskId = (int)$taskId;
    $this->batchSize = (int)$batchSize;
  }

  public function rewind() {
    $this->lastProcessedId = 0;
  }

  public function current() {
    $subscribers = $this->getSubscribers()
      ->orderByAsc('subscriber_id')
      ->limit($this->batchSize)
      ->findArray();
    $subscribers = array_column($subscribers, 'subscriber_id');
    $this->batchLastId = end($subscribers);
    return $subscribers;
  }

  public function key() {
    return null;
  }

  public function next() {
    $this->lastProcessedId = $this->batchLastId;
  }

  public function valid() {
    return $this->count() > 0;
  }

  public function count() {
    return $this->getSubscribers()->count();
  }

  private function getSubscribers() {
    return ScheduledTaskSubscriber::select('subscriber_id')
      ->where('task_id', $this->taskId)
      ->whereGt('subscriber_id', $this->lastProcessedId)
      ->where('processed', ScheduledTaskSubscriber::STATUS_UNPROCESSED);
  }
}
