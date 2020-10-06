<?php

namespace MailPoet\Test\Tasks;

use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Tasks\Subscribers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class SendingTest extends \MailPoetTest {
  public $sending;
  public $queue;
  public $task;
  public $newsletter;

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
    expect($sendings)->notEmpty();
    $queue = $sendings[0]->queue();
    expect($queue->taskId)->equals($this->task->id);
  }

  public function testItCanBeCreatedFromScheduledTask() {
    $sending = SendingTask::createFromScheduledTask($this->task);
    $queue = $sending->queue();
    expect($queue->taskId)->equals($this->task->id);
  }

  public function testItCanBeCreatedFromQueue() {
    $sending = SendingTask::createFromQueue($this->queue);
    $task = $sending->task();
    expect($task->id)->equals($this->queue->task_id);
  }

  public function testItCanBeInitializedByNewsletterId() {
    $sending = SendingTask::getByNewsletterId($this->newsletter->id);
    $queue = $sending->queue();
    $task = $sending->task();
    expect($queue->id)->equals($this->newsletter->id);
    expect($task->id)->equals($queue->taskId);
  }

  public function testItCanBeConvertedToArray() {
    $sending = $this->sending->asArray();
    expect($sending['id'])->equals($this->queue->id);
    expect($sending['task_id'])->equals($this->task->id);
  }

  public function testItSavesDataForUnderlyingModels() {
    $newsletterRenderedSubject = 'Abc';
    $status = ScheduledTask::STATUS_PAUSED;
    $this->sending->status = $status;
    $this->sending->newsletter_rendered_subject = $newsletterRenderedSubject;
    $this->sending->save();
    $task = ScheduledTask::findOne($this->task->id);
    $queue = SendingQueue::findOne($this->queue->id);
    expect($task->status)->equals($status);
    expect($queue->newsletterRenderedSubject)->equals($newsletterRenderedSubject);
  }

  public function testItDeletesUnderlyingModels() {
    $this->sending->delete();
    expect(ScheduledTask::findOne($this->task->id))->equals(false);
    expect(SendingQueue::findOne($this->queue->id))->equals(false);
    expect(ScheduledTaskSubscriber::where('task_id', $this->task->id)->findMany())->isEmpty();
  }

  public function testItGetsSubscribers() {
    expect($this->sending->getSubscribers())->equals([123, 456]);
  }

  public function testItSetsSubscribers() {
    $subscriberIds = [1, 2, 3];
    $this->sending->setSubscribers($subscriberIds);
    expect($this->sending->getSubscribers())->equals($subscriberIds);
    expect($this->sending->count_total)->equals(count($subscriberIds));
  }

  public function testItRemovesSubscribers() {
    $subscriberIds = [456];
    $this->sending->removeSubscribers($subscriberIds);
    expect($this->sending->getSubscribers())->equals([123]);
    expect($this->sending->count_total)->equals(1);
  }

  public function testItRemovesAllSubscribers() {
    $this->sending->removeAllSubscribers();
    expect($this->sending->getSubscribers())->equals([]);
    expect($this->sending->count_total)->equals(0);
  }

  public function testItUpdatesProcessedSubscribers() {
    expect($this->sending->count_to_process)->equals(2);
    expect($this->sending->count_processed)->equals(0);
    $subscriberIds = [456];
    $this->sending->updateProcessedSubscribers($subscriberIds);
    expect($this->sending->count_to_process)->equals(1);
    expect($this->sending->count_processed)->equals(1);
  }

  public function testItGetsScheduledQueues() {
    $this->sending->status = ScheduledTask::STATUS_SCHEDULED;
    $this->sending->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(1);
    $this->sending->save();
    $tasks = SendingTask::getScheduledQueues();
    expect($tasks)->notEmpty();
    foreach ($tasks as $task) {
      expect($task)->isInstanceOf('MailPoet\Tasks\Sending');
    }

    // if task exists but sending queue is missing, results should not contain empty (false) values
    $this->queue->delete();
    $tasks = SendingTask::getRunningQueues();
    expect($tasks)->isEmpty();
  }

  public function testItGetsBatchOfScheduledQueues() {
    $this->_after();
    $amount = 5;
    for ($i = 0; $i < $amount + 3; $i += 1) {
      $this->createNewSendingTask(['status' => ScheduledTask::STATUS_SCHEDULED]);
    }
    expect(SendingTask::getScheduledQueues($amount))->count($amount);
  }

  public function testItDoesNotGetPaused() {
    $this->_after();
    $this->createNewSendingTask(['status' => ScheduledTask::STATUS_PAUSED]);
    expect(SendingTask::getScheduledQueues())->count(0);
  }

  public function testItGetsRunningQueues() {
    $this->sending->status = null;
    $this->sending->scheduled_at = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subHours(1);
    $this->sending->save();
    $tasks = SendingTask::getRunningQueues();
    expect($tasks)->notEmpty();
    foreach ($tasks as $task) {
      expect($task)->isInstanceOf('MailPoet\Tasks\Sending');
    }

    // if task exists but sending queue is missing, results should not contain empty (false) values
    $this->queue->delete();
    $tasks = SendingTask::getRunningQueues();
    expect($tasks)->isEmpty();
  }

  public function testItGetsBatchOfRunningQueues() {
    $this->_after();
    $amount = 5;
    for ($i = 0; $i < $amount + 3; $i += 1) {
      $this->createNewSendingTask(['status' => null]);
    }
    expect(SendingTask::getRunningQueues($amount))->count($amount);
  }

  public function testItGetsBatchOfRunningQueuesSortedByUpdatedTime() {
    $this->_after();

    $sending1 = $this->createNewSendingTask(['status' => ScheduledTask::STATUS_SCHEDULED]);
    $sending1->updatedAt = '2017-05-04 14:00:00';
    $sending1->save();
    $sending2 = $this->createNewSendingTask(['status' => ScheduledTask::STATUS_SCHEDULED]);
    $sending2->updatedAt = '2017-05-04 16:00:00';
    $sending2->save();
    $sending3 = $this->createNewSendingTask(['status' => ScheduledTask::STATUS_SCHEDULED]);
    $sending3->updatedAt = '2017-05-04 15:00:00';
    $sending3->save();

    $queues = SendingTask::getScheduledQueues(3);
    expect($queues[0]->task_id)->equals($sending1->id());
    expect($queues[1]->task_id)->equals($sending3->id());
    expect($queues[2]->task_id)->equals($sending2->id());
  }

  public function testItGetsBatchOfScheduledQueuesSortedByUpdatedTime() {
    $this->_after();

    $sending1 = $this->createNewSendingTask(['status' => null]);
    $sending1->updatedAt = '2017-05-04 14:00:00';
    $sending1->save();
    $sending2 = $this->createNewSendingTask(['status' => null]);
    $sending2->updatedAt = '2017-05-04 16:00:00';
    $sending2->save();
    $sending3 = $this->createNewSendingTask(['status' => null]);
    $sending3->updatedAt = '2017-05-04 15:00:00';
    $sending3->save();

    $queues = SendingTask::getRunningQueues(3);
    expect($queues[0]->task_id)->equals($sending1->id());
    expect($queues[1]->task_id)->equals($sending3->id());
    expect($queues[2]->task_id)->equals($sending2->id());
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

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
