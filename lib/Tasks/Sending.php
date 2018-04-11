<?php

namespace MailPoet\Tasks;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

if(!defined('ABSPATH')) exit;

/**
 * A facade class containing all necessary models to work with a sending queue
 */
class Sending {
  const TASK_TYPE = 'sending';

  private $task;
  private $queue;
  private $task_subscribers;

  private $queue_fields = array(
    'id',
    'task_id',
    'newsletter_id',
    'newsletter_rendered_subject',
    'newsletter_rendered_body',
    'count_total',
    'count_processed',
    'count_to_process'
  );

  private $common_fields = array(
    'created_at',
    'updated_at',
    'deleted_at'
  );

  private function __construct(ScheduledTask $task = null, SendingQueue $queue = null) {
    if(is_null($task) && is_null($queue)) {
      $task = ScheduledTask::create();
      $task->type = self::TASK_TYPE;
      $task->save();

      $queue = SendingQueue::create();
      $queue->newsletter_id = 0;
      $queue->task_id = $task->id;
      $queue->save();
    }

    if($task->type !== self::TASK_TYPE) {
      throw new \Exception('Only tasks of type "' . self::TASK_TYPE . '" are accepted by this class');
    }

    $this->task = $task;
    $this->queue = $queue;
    $this->task_subscribers = new Subscribers($task);
  }

  static function create(ScheduledTask $task = null, SendingQueue $queue = null) {
    return new self($task, $queue);
  }

  static function createFromTask(ScheduledTask $task) {
    $queue = SendingQueue::where('task_id', $task->id)->findOne();
    if(!$queue) {
      return false;
    }

    return self::create($task, $queue);
  }

  static function createFromQueue(SendingQueue $queue) {
    $task = $queue->task()->findOne();
    if(!$task) {
      return false;
    }

    return self::create($task, $queue);
  }

  static function getByNewsletterId($newsletter_id) {
    $queue = SendingQueue::where('newsletter_id', $newsletter_id)
      ->orderByDesc('updated_at')
      ->findOne();
    if(!$queue) {
      return false;
    }

    return self::createFromQueue($queue);
  }

  public function asArray() {
    $queue = array_intersect_key(
      $this->queue->asArray(),
      array_flip($this->queue_fields)
    );
    $task = $this->task->asArray();
    return array_merge($task, $queue);
  }

  public function getErrors() {
    $queue_errors = $this->queue->getErrors();
    $task_errors = $this->task->getErrors();
    if(empty($queue_errors) && empty($task_errors)) {
      return false;
    }
    return array_merge((array)$queue_errors, (array)$task_errors);
  }

  public function save() {
    $this->task->save();
    $this->queue->save();
    return $this;
  }

  public function delete() {
    $this->task_subscribers->removeAllSubscribers();
    $this->task->delete();
    $this->queue->delete();
  }

  public function queue() {
    return $this->queue;
  }

  public function task() {
    return $this->task;
  }

  public function taskSubscribers() {
    return $this->task_subscribers;
  }

  public function getSubscribers($processed = null) {
    $subscribers = $this->task_subscribers->getSubscribers();
    if(!is_null($processed)) {
      $status = ($processed) ? ScheduledTaskSubscriber::STATUS_PROCESSED : ScheduledTaskSubscriber::STATUS_UNPROCESSED;
      $subscribers->where('processed', $status);
    }
    $subscribers = $subscribers->findArray();
    return Helpers::arrayColumn($subscribers, 'subscriber_id');
  }

  public function setSubscribers(array $subscriber_ids) {
    $this->task_subscribers->setSubscribers($subscriber_ids);
    $this->updateCount();
  }

  public function removeSubscribers(array $subscriber_ids) {
    $this->task_subscribers->removeSubscribers($subscriber_ids);
    $this->updateCount();
  }

  public function removeAllSubscribers() {
    $this->task_subscribers->removeAllSubscribers();
    $this->updateCount();
  }

  public function updateProcessedSubscribers(array $processed_subscribers) {
    $this->task_subscribers->updateProcessedSubscribers($processed_subscribers);
    return $this->updateCount()->getErrors() === false;
  }

  function updateCount() {
    $this->queue->count_processed = ScheduledTaskSubscriber::getProcessedCount($this->task->id);
    $this->queue->count_to_process = ScheduledTaskSubscriber::getUnprocessedCount($this->task->id);
    $this->queue->count_total = $this->queue->count_processed + $this->queue->count_to_process;
    return $this->queue->save();
  }

  public function hydrate(array $data) {
    foreach($data as $k => $v) {
      $this->__set($k, $v);
    }
  }

  public function validate() {
    return $this->queue->validate() && $this->task->validate();
  }

  public function __isset($prop) {
    if($this->isQueueProperty($prop)) {
      return isset($this->queue->$prop);
    } else {
      return isset($this->task->$prop);
    }
  }

  public function __get($prop) {
    if($this->isQueueProperty($prop)) {
      return $this->queue->$prop;
    } else {
      return $this->task->$prop;
    }
  }

  public function __set($prop, $value) {
    if($this->isCommonProperty($prop)) {
      $this->queue->$prop = $value;
      $this->task->$prop = $value;
    } elseif($this->isQueueProperty($prop)) {
      $this->queue->$prop = $value;
    } else {
      $this->task->$prop = $value;
    }
  }

  public function __call($name, $args) {
    $obj = method_exists($this->queue, $name) ? $this->queue : $this->task;
    return call_user_func_array(array($obj, $name), $args);
  }

  private function isQueueProperty($prop) {
    return in_array($prop, $this->queue_fields);
  }

  private function isCommonProperty($prop) {
    return in_array($prop, $this->common_fields);
  }

  static function getScheduledQueues() {
    $tasks = ScheduledTask::where('status', ScheduledTask::STATUS_SCHEDULED)
      ->whereLte('scheduled_at', Carbon::createFromTimestamp(WPFunctions::currentTime('timestamp')))
      ->where('type', 'sending')
      ->findMany();
    $result = array();
    foreach($tasks as $task) {
      $result[] = static::createFromTask($task);
    }
    return array_filter($result);
  }

  static function getRunningQueues() {
    $tasks = ScheduledTask::orderByAsc('priority')
      ->orderByAsc('created_at')
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->where('type', 'sending')
      ->findMany();
    $result = array();
    foreach($tasks as $task) {
      $result[] = static::createFromTask($task);
    }
    return array_filter($result);
  }
}
