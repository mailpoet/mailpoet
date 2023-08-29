<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Tasks;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue as SendingQueueAlias;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\InvalidStateException;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Util\Helpers;

/**
 * A facade class containing all necessary models to work with a sending queue
 * @property string|null $status
 * @property int $taskId
 * @property int $id
 * @property int $newsletterId
 * @property string $newsletterRenderedSubject
 * @property string|array $newsletterRenderedBody
 * @property bool $nonExistentColumn
 * @property string $scheduledAt
 * @property int $priority
 */
class Sending {
  const TASK_TYPE = SendingQueueAlias::TASK_TYPE;
  const RESULT_BATCH_SIZE = 5;

  /** @var ScheduledTask */
  private $task;

  /** @var SendingQueue */
  private $queue;

  /** @var Subscribers */
  private $taskSubscribers;

  private $queueFields = [
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

  private $commonFields = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  private function __construct(
    ScheduledTask $task = null,
    SendingQueue $queue = null
  ) {
    if (!$task instanceof ScheduledTask) {
      $task = ScheduledTask::create();
      $task->type = self::TASK_TYPE;
      $task->save();
    }
    if (!$queue instanceof SendingQueue) {
      $queue = SendingQueue::create();
      $queue->newsletterId = 0;
      $queue->taskId = $task->id;
      $queue->save();
    }

    if ($task->type !== self::TASK_TYPE) {
      throw new \Exception('Only tasks of type "' . self::TASK_TYPE . '" are accepted by this class');
    }

    $this->task = $task;
    $this->queue = $queue;
    $this->taskSubscribers = new Subscribers($task);
  }

  public static function create(ScheduledTask $task = null, SendingQueue $queue = null) {
    return new self($task, $queue);
  }

  public static function createManyFromTasks($tasks) {
    if (empty($tasks)) {
      return [];
    }

    $tasksIds = array_map(function($task) {
      return $task->id;
    }, $tasks);

    $queues = SendingQueue::whereIn('task_id', $tasksIds)->findMany();
    $queuesIndex = [];
    foreach ($queues as $queue) {
      $queuesIndex[$queue->taskId] = $queue;
    }

    $result = [];
    foreach ($tasks as $task) {
      if (!empty($queuesIndex[$task->id])) {
        $result[] = self::create($task, $queuesIndex[$task->id]);
      } else {
        static::handleInvalidTask($task);
      }
    }
    return $result;
  }

  public static function handleInvalidTask(ScheduledTask $task) {
    $loggerFactory = LoggerFactory::getInstance();
    $loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->error(
      'invalid sending task found',
      ['task_id' => $task->id]
    );
    $task->status = ScheduledTask::STATUS_INVALID;
    $task->save();
  }

  public static function createFromScheduledTask(ScheduledTask $task) {
    $queue = SendingQueue::where('task_id', $task->id)->findOne();
    if (!$queue) {
      return false;
    }

    return self::create($task, $queue);
  }

  public static function createFromQueue(SendingQueue $queue) {
    $task = $queue->task()->findOne();
    if (!$task) {
      return false;
    }

    return self::create($task, $queue);
  }

  public static function getByNewsletterId($newsletterId) {
    $queue = SendingQueue::where('newsletter_id', $newsletterId)
      ->orderByDesc('updated_at')
      ->findOne();
    if (!$queue instanceof SendingQueue) {
      return false;
    }

    return self::createFromQueue($queue);
  }

  public function asArray() {
    $queue = array_intersect_key(
      $this->queue->asArray(),
      array_flip($this->queueFields)
    );
    $task = $this->task->asArray();
    return array_merge($task, $queue);
  }

  public function getErrors() {
    $queueErrors = $this->queue->getErrors();
    $taskErrors = $this->task->getErrors();
    if (empty($queueErrors) && empty($taskErrors)) {
      return false;
    }
    return array_merge((array)$queueErrors, (array)$taskErrors);
  }

  public function save() {
    $this->queue->save();
    $this->task->save();
    $errors = $this->getErrors();
    if ($errors) {
      $loggerFactory = LoggerFactory::getInstance();
      $loggerFactory->getLogger(LoggerFactory::TOPIC_NEWSLETTERS)->error(
        'error saving sending task',
        ['task_id' => $this->task->id, 'queue_id' => $this->queue->id, 'errors' => $errors]
      );
    }
    return $this;
  }

  public function delete() {
    $this->taskSubscribers->removeAllSubscribers();
    $this->task->delete();
    $this->queue->delete();
  }

  public function queue() {
    return $this->queue;
  }

  public function getSendingQueueEntity(): SendingQueueEntity {
    $sendingQueuesRepository = ContainerWrapper::getInstance()->get(SendingQueuesRepository::class);
    $sendingQueueEntity = $sendingQueuesRepository->findOneById($this->queue->id);
    if (!$sendingQueueEntity) {
      throw new InvalidStateException();
    }
    $sendingQueuesRepository->refresh($sendingQueueEntity);

    return $sendingQueueEntity;
  }

  public function task() {
    return $this->task;
  }

  public function taskSubscribers() {
    return $this->taskSubscribers;
  }

  public function getSubscribers($processed = null) {
    $subscribers = $this->taskSubscribers->getSubscribers();
    if (!is_null($processed)) {
      $status = ($processed) ? ScheduledTaskSubscriber::STATUS_PROCESSED : ScheduledTaskSubscriber::STATUS_UNPROCESSED;
      $subscribers->where('processed', $status);
    }
    $subscribers = $subscribers->findArray();
    return array_column($subscribers, 'subscriber_id');
  }

  public function setSubscribers(array $subscriberIds) {
    $this->taskSubscribers->setSubscribers($subscriberIds);
    $this->updateCount();
  }

  public function removeSubscribers(array $subscriberIds) {
    $this->taskSubscribers->removeSubscribers($subscriberIds);
    $this->updateCount();
  }

  public function removeAllSubscribers() {
    $this->taskSubscribers->removeAllSubscribers();
    $this->updateCount();
  }

  public function updateProcessedSubscribers(array $processedSubscribers) {
    $this->taskSubscribers->updateProcessedSubscribers($processedSubscribers);
    return $this->updateCount(count($processedSubscribers))->getErrors() === false;
  }

  public function saveSubscriberError($subcriberId, $errorMessage) {
    $this->taskSubscribers->saveSubscriberError($subcriberId, $errorMessage);
    return $this->updateCount()->getErrors() === false;
  }

  public function updateCount(?int $count = null) {
    if ($count) {
      // increment/decrement counts based on known subscriber count, don't exceed the bounds
      $this->queue->countProcessed = min($this->queue->countProcessed + $count, $this->queue->countTotal);
      $this->queue->countToProcess = max($this->queue->countToProcess - $count, 0);
    } else {
      // query DB to update counts, slower but more accurate, to be used if count isn't known
      $this->queue->countProcessed = ScheduledTaskSubscriber::getProcessedCount($this->task->id);
      $this->queue->countToProcess = ScheduledTaskSubscriber::getUnprocessedCount($this->task->id);
      $this->queue->countTotal = $this->queue->countProcessed + $this->queue->countToProcess;
    }
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

  public function getMeta() {
    return $this->queue->getMeta();
  }

  public function __isset($prop) {
    $prop = Helpers::camelCaseToUnderscore($prop);
    if ($this->isQueueProperty($prop)) {
      return isset($this->queue->$prop);
    } else {
      return isset($this->task->$prop);
    }
  }

  public function __get($prop) {
    $prop = Helpers::camelCaseToUnderscore($prop);
    if ($this->isQueueProperty($prop)) {
      return $this->queue->$prop;
    } else {
      return $this->task->$prop;
    }
  }

  public function __set($prop, $value) {
    $prop = Helpers::camelCaseToUnderscore($prop);
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
    return in_array($prop, $this->queueFields);
  }

  private function isCommonProperty($prop) {
    return in_array($prop, $this->commonFields);
  }
}
