<?php
namespace MailPoet\Tasks\Subscribers;

use MailPoet\Models\ScheduledTaskSubscriber;
use function MailPoet\Util\array_column;

if (!defined('ABSPATH')) exit;

class BatchIterator implements \Iterator, \Countable {
  private $task_id;
  private $batch_size;
  private $last_processed_id = 0;
  private $batch_last_id;

  function __construct($task_id, $batch_size) {
    if ($task_id <= 0) {
      throw new \Exception('Task ID must be greater than zero');
    } elseif ($batch_size <= 0) {
      throw new \Exception('Batch size must be greater than zero');
    }
    $this->task_id = (int)$task_id;
    $this->batch_size = (int)$batch_size;
  }

  function rewind() {
    $this->last_processed_id = 0;
  }

  function current() {
    $subscribers = $this->getSubscribers()
      ->orderByAsc('subscriber_id')
      ->limit($this->batch_size)
      ->findArray();
    $subscribers = array_column($subscribers, 'subscriber_id');
    $this->batch_last_id = end($subscribers);
    return $subscribers;
  }

  function key() {
    return null;
  }

  function next() {
    $this->last_processed_id = $this->batch_last_id;
  }

  function valid() {
    return $this->count() > 0;
  }

  function count() {
    return $this->getSubscribers()->count();
  }

  private function getSubscribers() {
    return ScheduledTaskSubscriber::select('subscriber_id')
      ->where('task_id', $this->task_id)
      ->whereGt('subscriber_id', $this->last_processed_id)
      ->where('processed', ScheduledTaskSubscriber::STATUS_UNPROCESSED);
  }
}
