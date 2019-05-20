<?php

namespace MailPoet\Tasks;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use function MailPoet\Util\array_column;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * A facade class containing all necessary models to work with a sending queue
 * @property string|null $status
 * @property int $task_id
 * @property int $id
 */
class Sending {
  const TASK_TYPE = 'sending';
  const RESULT_BATCH_SIZE = 5;

  private $task;
  private $queue;
  private $task_subscribers;

  private $queue_fields = [
    'id',
    'task_id',
    'newsletter_id',
    'newsletter_rendered_subject',
    'newsletter_rendered_body',
    'count_total',
    'count_processed',
    'count_to_process',
    'meta',
  ];

  private $common_fields = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  private function __construct(ScheduledTask $task = null, SendingQueue $queue = null) {
    if (is_null($task) && is_null($queue)) {
      $task = ScheduledTask::create();
      $task->type = self::TASK_TYPE;
      $task->save();

      $queue = SendingQueue::create();
      $queue->newsletter_id = 0;
      $queue->task_id = $task->id;
      $queue->save();
    }

    if ($task->type !== self::TASK_TYPE) {
      throw new \Exception('Only tasks of type "' . self::TASK_TYPE . '" are accepted by this class');
    }

    $this->task = $task;
    $this->queue = $queue;
    $this->task_subscribers = new Subscribers($task);
  }

  static function create(ScheduledTask $task = null, SendingQueue $queue = null) {
    return new self($task, $queue);
  }

  static function createManyFromTasks($tasks) {
    if (empty($tasks)) {
      return [];
    }

    $tasks_ids = array_map(function($task) {
      return $task->id;
    }, $tasks);

    $queues = SendingQueue::whereIn('task_id', $tasks_ids)->findMany();
    $queues_index = [];
    foreach ($queues as $queue) {
      $queues_index[$queue->task_id] = $queue;
    }

    $result = [];
    foreach ($tasks as $task) {
      if (!empty($queues_index[$task->id])) {
        $result[] = self::create($task, $queues_index[$task->id]);
      }
    }
    return $result;
  }

  static function createFromQueue(SendingQueue $queue) {
    $task = $queue->task()->findOne();
    if (!$task) {
      return false;
    }

    return self::create($task, $queue);
  }

  static function getByNewsletterId($newsletter_id) {
    $queue = SendingQueue::where('newsletter_id', $newsletter_id)
      ->orderByDesc('updated_at')
      ->findOne();
    if (!$queue) {
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
    if (empty($queue_errors) && empty($task_errors)) {
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
    if (!is_null($processed)) {
      $status = ($processed) ? ScheduledTaskSubscriber::STATUS_PROCESSED : ScheduledTaskSubscriber::STATUS_UNPROCESSED;
      $subscribers->where('processed', $status);
    }
    $subscribers = $subscribers->findArray();
    return array_column($subscribers, 'subscriber_id');
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

  public function saveSubscriberError($subcriber_id, $error_message) {
    $this->task_subscribers->saveSubscriberError($subcriber_id, $error_message);
    return $this->updateCount()->getErrors() === false;
  }

  function updateCount() {
    $this->queue->count_processed = ScheduledTaskSubscriber::getProcessedCount($this->task->id);
    $this->queue->count_to_process = ScheduledTaskSubscriber::getUnprocessedCount($this->task->id);
    $this->queue->count_total = $this->queue->count_processed + $this->queue->count_to_process;
    return $this->queue->save();
  }

  public function hydrate(array $data) {
    foreach ($data as $k => $v) {
      $this->__set($k, $v);
    }
  }

  public function validate() {
    return $this->queue->validate() && $this->task->validate();
  }

  public function __isset($prop) {
    if ($this->isQueueProperty($prop)) {
      return isset($this->queue->$prop);
    } else {
      return isset($this->task->$prop);
    }
  }

  public function __get($prop) {
    if ($this->isQueueProperty($prop)) {
      return $this->queue->$prop;
    } else {
      return $this->task->$prop;
    }
  }

  public function __set($prop, $value) {
    if ($this->isCommonProperty($prop)) {
      $this->queue->$prop = $value;
      $this->task->$prop = $value;
    } elseif ($this->isQueueProperty($prop)) {
      $this->queue->$prop = $value;
    } else {
      $this->task->$prop = $value;
    }
  }

  public function __call($name, $args) {
    $obj = method_exists($this->queue, $name) ? $this->queue : $this->task;
    $callback = [$obj, $name];
    if (is_callable($callback)) {
      return call_user_func_array($callback, $args);
    }
  }

  private function isQueueProperty($prop) {
    return in_array($prop, $this->queue_fields);
  }

  private function isCommonProperty($prop) {
    return in_array($prop, $this->common_fields);
  }

  static function getScheduledQueues($amount = self::RESULT_BATCH_SIZE) {
    $wp = new WPFunctions();
    $tasks = ScheduledTask::tableAlias('tasks')
      ->select('tasks.*')
      ->join(SendingQueue::$_table, 'tasks.id = queues.task_id', 'queues')
      ->whereNull('tasks.deleted_at')
      ->where('tasks.status', ScheduledTask::STATUS_SCHEDULED)
      ->whereLte('tasks.scheduled_at', Carbon::createFromTimestamp($wp->currentTime('timestamp')))
      ->where('tasks.type', 'sending')
      ->orderByAsc('tasks.updated_at')
      ->limit($amount)
      ->findMany();
    return static::createManyFromTasks($tasks);
  }

  static function getRunningQueues($amount = self::RESULT_BATCH_SIZE) {
    $tasks = ScheduledTask::orderByAsc('priority')
      ->orderByAsc('updated_at')
      ->whereNull('deleted_at')
      ->whereNull('status')
      ->where('type', 'sending')
      ->limit($amount)
      ->findMany();
    return static::createManyFromTasks($tasks);
  }
}
