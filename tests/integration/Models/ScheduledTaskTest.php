<?php
namespace MailPoet\Test\Models;

use Carbon\Carbon;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Util\Helpers;

class ScheduledTaskTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    $this->task = ScheduledTask::create();
    $this->task->hydrate(array(
      'status' => ScheduledTask::STATUS_SCHEDULED
    ));
    $this->task->save();
  }

  function testItCanBeCompleted() {
    $this->task->complete();
    expect($this->task->status)->equals(ScheduledTask::STATUS_COMPLETED);
  }

  function testItSetsDefaultPriority() {
    expect($this->task->priority)->equals(ScheduledTask::PRIORITY_MEDIUM);
  }

  function testItUnPauseAllByNewsletters() {
    $newsletter = Newsletter::createOrUpdate(array(
      'type' => Newsletter::TYPE_NOTIFICATION
    ));
    $task1 = ScheduledTask::createOrUpdate(array(
      'status' => ScheduledTask::STATUS_PAUSED,
      'scheduled_at' => Carbon::createFromTimestamp(current_time('timestamp'))->addDays(10)->format('Y-m-d H:i:s'),
    ));
    $task2 = ScheduledTask::createOrUpdate(array(
      'status' => ScheduledTask::STATUS_COMPLETED,
      'scheduled_at' => Carbon::createFromTimestamp(current_time('timestamp'))->addDays(10)->format('Y-m-d H:i:s'),
    ));
    $task3 = ScheduledTask::createOrUpdate(array(
      'status' => ScheduledTask::STATUS_PAUSED,
      'scheduled_at' => Carbon::createFromTimestamp(current_time('timestamp'))->subDays(10)->format('Y-m-d H:i:s'),
    ));
    SendingQueue::createOrUpdate(array(
      'newsletter_id' => $newsletter->id(),
      'task_id' => $task1->id(),
    ));
    SendingQueue::createOrUpdate(array(
      'newsletter_id' => $newsletter->id(),
      'task_id' => $task2->id(),
    ));
    SendingQueue::createOrUpdate(array(
      'newsletter_id' => $newsletter->id(),
      'task_id' => $task3->id(),
    ));
    ScheduledTask::setScheduledAllByNewsletter($newsletter);
    $task1_found = ScheduledTask::findOne($task1->id());
    expect($task1_found->status)->equals(ScheduledTask::STATUS_SCHEDULED);
    $task2_found = ScheduledTask::findOne($task2->id());
    expect($task2_found->status)->equals(ScheduledTask::STATUS_COMPLETED);
    $task3_found = ScheduledTask::findOne($task3->id());
    expect($task3_found->status)->equals(ScheduledTask::STATUS_PAUSED);
  }

  function testItPauseAllByNewsletters() {
    $newsletter = Newsletter::createOrUpdate(array(
      'type' => Newsletter::TYPE_NOTIFICATION
    ));
    $task1 = ScheduledTask::createOrUpdate(array(
      'status' => ScheduledTask::STATUS_COMPLETED,
    ));
    $task2 = ScheduledTask::createOrUpdate(array(
      'status' => ScheduledTask::STATUS_SCHEDULED,
    ));
    SendingQueue::createOrUpdate(array(
      'newsletter_id' => $newsletter->id(),
      'task_id' => $task1->id(),
    ));
    SendingQueue::createOrUpdate(array(
      'newsletter_id' => $newsletter->id(),
      'task_id' => $task2->id(),
    ));
    ScheduledTask::pauseAllByNewsletter($newsletter);
    $task1_found = ScheduledTask::findOne($task1->id());
    expect($task1_found->status)->equals(ScheduledTask::STATUS_COMPLETED);
    $task2_found = ScheduledTask::findOne($task2->id());
    expect($task2_found->status)->equals(ScheduledTask::STATUS_PAUSED);
  }

  function testItDeletesRelatedScheduledTaskSubscriber() {
    $task_id = $this->task->id;
    ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $task_id,
      'subscriber_id' => 1
    ]);
    ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $task_id,
      'subscriber_id' => 2
    ]);
    ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $task_id,
      'subscriber_id' => 3
    ]);
    $count = ScheduledTaskSubscriber::where('task_id', $task_id)->count();
    expect($count)->equals(3);

    $this->task->delete();
    $count = ScheduledTaskSubscriber::where('task_id', $task_id)->count();
    expect($count)->equals(0);
  }

  function testItJsonEncodesMetaWhenSaving() {
    $task = ScheduledTask::create();
    $meta = array(
      'some' => 'value'
    );
    $task->meta = $meta;
    $task->save();

    $task = ScheduledTask::findOne($task->id);

    expect(Helpers::isJson($task->meta))->true();
    expect(json_decode($task->meta, true))->equals($meta);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
  }
}
