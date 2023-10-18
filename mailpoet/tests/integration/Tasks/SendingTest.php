<?php declare(strict_types = 1);

namespace MailPoet\Test\Tasks;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Tasks\Subscribers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SendingTest extends \MailPoetTest {
  public $sending;
  public $queue;
  public $task;
  public $newsletter;

  /** @var ScheduledTasksRepository */
  private $scheduledTaskRepository;

  public function _before() {
    parent::_before();
    $this->newsletter = $this->createNewNewsletter();
    $this->task = $this->createNewScheduledTask();
    $this->queue = $this->createNewSendingQueue([
      'newsletter' => $this->newsletter,
      'task' => $this->task,
    ]);
    $this->sending = $this->createNewSendingTask([
      'status' => null,
      'task' => $this->task,
      'queue' => $this->queue,
    ]);
    $this->scheduledTaskRepository = $this->diContainer->get(ScheduledTasksRepository::class);
  }

  public function testItCanBeConstructed() {
    $sending = SendingTask::create();
    expect_that($sending instanceof SendingTask);
    expect_that($sending->queue() instanceof SendingQueue);
    expect_that($sending->task() instanceof ScheduledTask);
    expect_that($sending->taskSubscribers() instanceof Subscribers);
  }

  public function testItCanBeConstructedOnlyWithAProperTaskType() {
    $this->task->type = 'wrong_type';
    try {
      $sending = SendingTask::create($this->task, $this->queue);
      $this->fail('Exception was not thrown');
    } catch (\Exception $e) {
      // No exception handling necessary
    }
  }

  public function testItCanCreateManyFromTasks() {
    $sendings = SendingTask::createManyFromTasks([$this->task]);
    verify($sendings)->notEmpty();
    $queue = $sendings[0]->queue();
    verify($queue->taskId)->equals($this->task->id);
  }

  public function testItDeletesInvalidTasksWhenCreatingManyFromTasks() {
    $this->queue->delete();
    $sendings = SendingTask::createManyFromTasks([$this->task]);
    verify($sendings)->empty();
    $task = ScheduledTask::findOne($this->task->id);
    $this->assertInstanceOf(ScheduledTask::class, $task);
    verify($task->status)->equals(ScheduledTask::STATUS_INVALID);
  }

  public function testItCanBeCreatedFromScheduledTask() {
    $sending = SendingTask::createFromScheduledTask($this->task);
    $queue = $sending->queue();
    verify($queue->taskId)->equals($this->task->id);
  }

  public function testItCanBeCreatedFromQueue() {
    $sending = SendingTask::createFromQueue($this->queue);
    $task = $sending->task();
    verify($task->id)->equals($this->queue->task_id);
  }

  public function testItCanBeInitializedByNewsletterId() {
    $sending = SendingTask::getByNewsletterId($this->newsletter->id);
    $queue = $sending->queue();
    $task = $sending->task();
    verify($task->id)->equals($queue->taskId);
  }

  public function testItCanBeConvertedToArray() {
    $sending = $this->sending->asArray();
    verify($sending['id'])->equals($this->queue->id);
    verify($sending['task_id'])->equals($this->task->id);
  }

  public function testItSavesDataForUnderlyingModels() {
    $newsletterRenderedSubject = 'Abc';
    $status = ScheduledTask::STATUS_PAUSED;
    $this->sending->status = $status;
    $this->sending->newsletter_rendered_subject = $newsletterRenderedSubject;
    $this->sending->save();
    $task = ScheduledTask::findOne($this->task->id);
    $queue = SendingQueue::findOne($this->queue->id);
    $this->assertInstanceOf(ScheduledTask::class, $task);
    $this->assertInstanceOf(SendingQueue::class, $queue);
    verify($task->status)->equals($status);
    verify($queue->newsletterRenderedSubject)->equals($newsletterRenderedSubject);
  }

  public function testItDeletesUnderlyingModels() {
    $this->sending->delete();
    verify(ScheduledTask::findOne($this->task->id))->equals(false);
    verify(SendingQueue::findOne($this->queue->id))->equals(false);
    verify(ScheduledTaskSubscriber::where('task_id', $this->task->id)->findMany())->empty();
  }

  public function testItGetsSubscribers() {
    verify($this->sending->getSubscribers())->equals([123, 456]);
  }

  public function testItSetsSubscribers() {
    $subscriberIds = [1, 2, 3];
    $this->sending->setSubscribers($subscriberIds);
    verify($this->sending->getSubscribers())->equals($subscriberIds);
    verify($this->sending->count_total)->equals(count($subscriberIds));
  }

  public function testItRemovesSubscribers() {
    $subscriberIds = [456];
    $this->sending->removeSubscribers($subscriberIds);
    verify($this->sending->getSubscribers())->equals([123]);
    verify($this->sending->count_total)->equals(1);
  }

  public function testItRemovesAllSubscribers() {
    $this->sending->removeAllSubscribers();
    verify($this->sending->getSubscribers())->equals([]);
    verify($this->sending->count_total)->equals(0);
  }

  public function testItUpdatesProcessedSubscribers() {
    $subscriberId = 456;
    $taskSubscriber = $this->getTaskSubscriber($this->task->id, $subscriberId);
    verify($taskSubscriber->processed)->equals(ScheduledTaskSubscriber::STATUS_UNPROCESSED);

    verify($this->sending->count_to_process)->equals(2);
    verify($this->sending->count_processed)->equals(0);
    $subscriberIds = [$subscriberId];
    $this->sending->updateProcessedSubscribers($subscriberIds);
    verify($this->sending->count_to_process)->equals(1);
    verify($this->sending->count_processed)->equals(1);

    $taskSubscriber = $this->getTaskSubscriber($this->task->id, $subscriberId);
    verify($taskSubscriber->processed)->equals(ScheduledTaskSubscriber::STATUS_PROCESSED);
  }

  public function testItGetsScheduledQueues() {
    $this->sending->status = ScheduledTask::STATUS_SCHEDULED;
    $this->sending->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(1);
    $this->sending->save();
    $tasks = $this->scheduledTaskRepository->findScheduledSendingTasks();
    verify($tasks)->notEmpty();
    foreach ($tasks as $task) {
      verify($task)->instanceOf(ScheduledTaskEntity::class);
    }

    // if task exists but sending queue is missing, results should not contain empty (false) values
    $this->queue->delete();
    $tasks = $this->scheduledTaskRepository->findRunningSendingTasks();
    verify($tasks)->empty();
  }

  public function testItGetsBatchOfScheduledQueues() {
    $amount = 5;
    for ($i = 0; $i < $amount + 3; $i += 1) {
      $this->createNewSendingTask(['status' => ScheduledTask::STATUS_SCHEDULED]);
    }
    verify($this->scheduledTaskRepository->findScheduledSendingTasks($amount))->arrayCount($amount);
  }

  public function testItDoesNotGetPaused() {
    $this->createNewSendingTask(['status' => ScheduledTask::STATUS_PAUSED]);
    verify($this->scheduledTaskRepository->findScheduledSendingTasks())->arrayCount(0);
  }

  public function testItGetsRunningQueues() {
    $this->sending->status = null;
    $this->sending->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(1);
    $this->sending->save();
    $tasks = $this->scheduledTaskRepository->findRunningSendingTasks();
    verify($tasks)->notEmpty();
    foreach ($tasks as $task) {
      verify($task)->instanceOf(ScheduledTaskEntity::class);
    }

    // if task exists but sending queue is missing, results should not contain empty (false) values
    $this->queue->delete();
    $tasks = $this->scheduledTaskRepository->findRunningSendingTasks();
    verify($tasks)->empty();
  }

  public function testItGetsBatchOfRunningQueues() {
    $amount = 5;
    for ($i = 0; $i < $amount + 3; $i += 1) {
      $this->createNewSendingTask(['status' => null]);
    }
    verify($this->scheduledTaskRepository->findRunningSendingTasks($amount))->arrayCount($amount);
  }

  public function testItGetsBatchOfRunningQueuesSortedByUpdatedTime() {
    $sending1 = $this->createNewSendingTask(['status' => ScheduledTask::STATUS_SCHEDULED]);
    $sending1->updatedAt = '2017-05-04 14:00:00';
    $sending1->save();
    $sending2 = $this->createNewSendingTask(['status' => ScheduledTask::STATUS_SCHEDULED]);
    $sending2->updatedAt = '2017-05-04 16:00:00';
    $sending2->save();
    $sending3 = $this->createNewSendingTask(['status' => ScheduledTask::STATUS_SCHEDULED]);
    $sending3->updatedAt = '2017-05-04 15:00:00';
    $sending3->save();

    $tasks = $this->scheduledTaskRepository->findScheduledSendingTasks(3);
    verify($tasks[0]->getId())->equals($sending1->taskId);
    verify($tasks[1]->getId())->equals($sending3->taskId);
    verify($tasks[2]->getId())->equals($sending2->taskId);
  }

  public function testItGetsBatchOfScheduledQueuesSortedByUpdatedTime() {
    $sending1 = $this->createNewSendingTask(['status' => null]);
    $sending1->updatedAt = '2017-05-04 14:00:00';
    $sending1->save();
    $sending2 = $this->createNewSendingTask(['status' => null]);
    $sending2->updatedAt = '2017-05-04 16:00:00';
    $sending2->save();
    $sending3 = $this->createNewSendingTask(['status' => null]);
    $sending3->updatedAt = '2017-05-04 15:00:00';
    $sending3->save();

    $tasks = $this->scheduledTaskRepository->findRunningSendingTasks(3);
    verify($tasks[0]->getId())->equals($sending1->taskId);
    verify($tasks[1]->getId())->equals($sending3->taskId);
    verify($tasks[2]->getId())->equals($sending2->taskId);
  }

  public function createNewNewsletter() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_STANDARD;
    return $newsletter->save();
  }

  public function createNewScheduledTask() {
    $task = ScheduledTask::create();
    $task->type = SendingTask::TASK_TYPE;
    return $task->save();
  }

  public function createNewSendingQueue($args = []) {
    $newsletter = isset($args['newsletter']) ? $args['newsletter'] : $this->createNewNewsletter();
    $task = isset($args['task']) ? $args['task'] : $this->createNewScheduledTask();

    $queue = SendingQueue::create();
    $queue->newsletterId = $newsletter->id;
    $queue->taskId = $task->id;
    return $queue->save();
  }

  public function createNewSendingTask($args = []) {
    $task = isset($args['task']) ? $args['task'] : $this->createNewScheduledTask();
    $queue = isset($args['queue']) ? $args['queue'] : $this->createNewSendingQueue(['task' => $task]);
    $status = isset($args['status']) ? $args['status'] : null;

    $sending = SendingTask::create($task, $queue);
    $sending->setSubscribers([123, 456]); // random IDs
    $sending->status = $status;
    $sending->scheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(1);
    return $sending->save();
  }

  private function getTaskSubscriber($taskId, $subscriberId) {
    return ScheduledTaskSubscriber::where(['task_id' => $taskId, 'subscriber_id' => $subscriberId])->findOne();
  }
}
