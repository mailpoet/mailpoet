<?php

namespace MailPoet\Test\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Migration;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Newsletter\Url;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Tasks\State;
use MailPoetVendor\Idiorm\ORM;

class StateTest extends \MailPoetTest {

  /** @var State */
  private $tasksState;

  public function _before() {
    parent::_before();
    $this->tasksState = new State(
      $this->diContainer->get(Url::class),
      $this->diContainer->get(SendingQueuesRepository::class)
    );
  }

  public function testItCanFetchBasicTasksData() {
    $this->createNewScheduledTask(SendingTask::TASK_TYPE);
    $this->createNewScheduledTask(Migration::TASK_TYPE);
    $data = $this->tasksState->getLatestTasks();
    expect(count($data))->equals(2);
    expect($data[1]['id'])->equals(1);
    expect($data[1]['type'])->equals(SendingTask::TASK_TYPE);
    expect(is_int($data[1]['priority']))->true();
    expect(is_string($data[1]['updated_at']))->true();
    expect($data[1])->hasKey('scheduled_at');
    expect($data[1]['status'])->notEmpty();
    expect($data[1])->hasKey('newsletter');
  }

  public function testItCanFilterTasksByType() {
    $this->createNewScheduledTask(SendingTask::TASK_TYPE);
    $this->createNewScheduledTask(Migration::TASK_TYPE);
    $data = $this->tasksState->getLatestTasks(Migration::TASK_TYPE);
    expect(count($data))->equals(1);
    expect($data[0]['type'])->equals(Migration::TASK_TYPE);
  }

  public function testItCanFilterTasksByStatus() {
    $this->createNewScheduledTask(SendingTask::TASK_TYPE, ScheduledTask::STATUS_COMPLETED);
    $this->createNewScheduledTask(SendingTask::TASK_TYPE, ScheduledTask::STATUS_PAUSED);
    $data = $this->tasksState->getLatestTasks(null, [ScheduledTask::STATUS_COMPLETED]);
    expect(count($data))->equals(1);
    expect($data[0]['status'])->equals(ScheduledTask::STATUS_COMPLETED);
  }

  public function testItFetchesNewsletterDataForSendingTasks() {
    $task = $this->createNewScheduledTask(SendingTask::TASK_TYPE);
    $newsletter = $this->createNewNewsletter();
    $this->createNewSendingQueue($task->id, $newsletter->id, 'Rendered Subject');
    $data = $this->tasksState->getLatestTasks();
    expect($data[0]['newsletter']['newsletter_id'])->equals(1);
    expect($data[0]['newsletter']['queue_id'])->equals(1);
    expect($data[0]['newsletter']['subject'])->equals('Rendered Subject');
    expect($data[0]['newsletter']['preview_url'])->notEmpty();
  }

  public function testItDoesNotFailForSendingTaskWithMissingNewsletterInconsistentData() {
    $task = $this->createNewScheduledTask(SendingTask::TASK_TYPE);
    $this->createNewSendingQueue($task->id);
    $data = $this->tasksState->getLatestTasks();
    expect($data[0]['newsletter']['newsletter_id'])->equals(null);
    expect($data[0]['newsletter']['queue_id'])->equals(null);
    expect($data[0]['newsletter']['subject'])->equals(null);
    expect($data[0]['newsletter']['preview_url'])->equals(null);
  }

  public function testItDoesNotFailForSendingTaskWithoutQueue() {
    $this->createNewScheduledTask(SendingTask::TASK_TYPE);
    $data = $this->tasksState->getLatestTasks();
    expect(count($data))->equals(1);
  }

  public function createNewScheduledTask($type, $status = ScheduledTask::STATUS_COMPLETED) {
    $task = ScheduledTask::create();
    $task->type = $type;
    $task->status = $status;
    return $task->save();
  }

  public function createNewNewsletter($subject = 'Test Subject') {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_STANDARD;
    $newsletter->subject = $subject;
    return $newsletter->save();
  }

  public function createNewSendingQueue($taskId, $newsletterId = null, $renderedSubject = null) {
    $queue = SendingQueue::create();
    $queue->newsletterId = $newsletterId;
    $queue->taskId = $taskId;
    $queue->newsletterRenderedSubject = $renderedSubject;
    return $queue->save();
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
