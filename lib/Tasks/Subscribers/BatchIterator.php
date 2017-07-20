<?php
namespace MailPoet\Tasks\Subscribers;

use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class BatchIterator implements \Iterator, \Countable {
  private $task_id;
  private $batch_size;
  private $offset = 0;

  function __construct($task_id, $batch_size) {
    if($task_id <= 0) {
      throw new \Exception('Task ID must be greater than zero');
    } elseif($batch_size <= 0) {
      throw new \Exception('Batch size must be greater than zero');
    }
    $this->task_id = (int)$task_id;
    $this->batch_size = (int)$batch_size;
  }

  function rewind() {
    $this->offset = 0;
  }

  function current() {
    $subscribers = $this->getSubscribers()
      ->orderByAsc('subscriber_id')
      ->limit($this->batch_size)
      ->offset($this->offset)
      ->findArray();
    $subscribers = Helpers::arrayColumn($subscribers, 'subscriber_id');
    return $subscribers;
  }

  function key() {
    return $this->offset;
  }

  function next() {
    $this->offset += $this->batch_size;
  }

  function valid() {
    return $this->offset < $this->count();
  }

  function count() {
    return $this->getSubscribers()->count();
  }

  private function getSubscribers() {
    return ScheduledTaskSubscriber::select('subscriber_id')
      ->where('task_id', $this->task_id)
      ->where('processed', ScheduledTaskSubscriber::STATUS_UNPROCESSED);
  }
}
