<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class ScheduledTaskTest extends \MailPoetTest {
  public $task;
  public function _before() {
    parent::_before();
    $this->task = ScheduledTask::create();
    $this->task->hydrate([
      'status' => ScheduledTask::STATUS_SCHEDULED,
    ]);
    $this->task->save();
  }

  public function testItCanBeCompleted() {
    $this->task->complete();
    expect($this->task->status)->equals(ScheduledTask::STATUS_COMPLETED);
  }

  public function testItSetsDefaultPriority() {
    expect($this->task->priority)->equals(ScheduledTask::PRIORITY_MEDIUM);
  }

  public function testItUnPauseAllByNewsletters() {
    $newsletter = Newsletter::createOrUpdate([
      'type' => Newsletter::TYPE_NOTIFICATION,
    ]);
    $task1 = ScheduledTask::createOrUpdate([
      'status' => ScheduledTask::STATUS_PAUSED,
      'scheduled_at' => Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addDays(10)->format('Y-m-d H:i:s'),
    ]);
    $task2 = ScheduledTask::createOrUpdate([
      'status' => ScheduledTask::STATUS_COMPLETED,
      'scheduled_at' => Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->addDays(10)->format('Y-m-d H:i:s'),
    ]);
    $task3 = ScheduledTask::createOrUpdate([
      'status' => ScheduledTask::STATUS_PAUSED,
      'scheduled_at' => Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subDays(10)->format('Y-m-d H:i:s'),
    ]);
    SendingQueue::createOrUpdate([
      'newsletter_id' => $newsletter->id(),
      'task_id' => $task1->id(),
    ]);
    SendingQueue::createOrUpdate([
      'newsletter_id' => $newsletter->id(),
      'task_id' => $task2->id(),
    ]);
    SendingQueue::createOrUpdate([
      'newsletter_id' => $newsletter->id(),
      'task_id' => $task3->id(),
    ]);
    ScheduledTask::setScheduledAllByNewsletter($newsletter);
    $task1_found = ScheduledTask::findOne($task1->id());
    expect($task1_found->status)->equals(ScheduledTask::STATUS_SCHEDULED);
    $task2_found = ScheduledTask::findOne($task2->id());
    expect($task2_found->status)->equals(ScheduledTask::STATUS_COMPLETED);
    $task3_found = ScheduledTask::findOne($task3->id());
    expect($task3_found->status)->equals(ScheduledTask::STATUS_PAUSED);
  }

  public function testItPauseAllByNewsletters() {
    $newsletter = Newsletter::createOrUpdate([
      'type' => Newsletter::TYPE_NOTIFICATION,
    ]);
    $task1 = ScheduledTask::createOrUpdate([
      'status' => ScheduledTask::STATUS_COMPLETED,
    ]);
    $task2 = ScheduledTask::createOrUpdate([
      'status' => ScheduledTask::STATUS_SCHEDULED,
    ]);
    SendingQueue::createOrUpdate([
      'newsletter_id' => $newsletter->id(),
      'task_id' => $task1->id(),
    ]);
    SendingQueue::createOrUpdate([
      'newsletter_id' => $newsletter->id(),
      'task_id' => $task2->id(),
    ]);
    ScheduledTask::pauseAllByNewsletter($newsletter);
    $task1_found = ScheduledTask::findOne($task1->id());
    expect($task1_found->status)->equals(ScheduledTask::STATUS_COMPLETED);
    $task2_found = ScheduledTask::findOne($task2->id());
    expect($task2_found->status)->equals(ScheduledTask::STATUS_PAUSED);
  }

  public function testItDeletesRelatedScheduledTaskSubscriber() {
    $task_id = $this->task->id;
    ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $task_id,
      'subscriber_id' => 1,
    ]);
    ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $task_id,
      'subscriber_id' => 2,
    ]);
    ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $task_id,
      'subscriber_id' => 3,
    ]);
    $count = ScheduledTaskSubscriber::where('task_id', $task_id)->count();
    expect($count)->equals(3);

    $this->task->delete();
    $count = ScheduledTaskSubscriber::where('task_id', $task_id)->count();
    expect($count)->equals(0);
  }

  public function testItJsonEncodesMetaWhenSaving() {
    $task = ScheduledTask::create();
    $meta = [
      'some' => 'value',
    ];
    $task->meta = $meta;
    $task->save();

    $task = ScheduledTask::findOne($task->id);

    /** @var string $task_meta */
    $task_meta = $task->meta;
    expect(Helpers::isJson($task_meta))->true();
    expect(json_decode($task_meta, true))->equals($meta);
  }

  public function testItDoesNotJsonEncodesMetaEqualToNull() {
    $task = ScheduledTask::create();
    $meta = null;
    $task->meta = $meta;
    $task->save();

    $task = ScheduledTask::findOne($task->id);

    expect(Helpers::isJson($task->meta))->false();
    expect($task->meta)->equals($meta);
  }

  public function testItCanRescheduleTasksProgressively() {
    $task = $this->task;
    $task->status = null;
    $scheduled_at = $task->scheduled_at;

    $timeout = $task->rescheduleProgressively();
    expect($timeout)->equals(ScheduledTask::BASIC_RESCHEDULE_TIMEOUT);
    expect($scheduled_at < $task->scheduled_at)->true();
    expect($task->status)->equals(ScheduledTask::STATUS_SCHEDULED);

    $timeout = $task->rescheduleProgressively();
    expect($timeout)->equals(ScheduledTask::BASIC_RESCHEDULE_TIMEOUT * 2);

    $task->reschedule_count = 123456; // too many
    $timeout = $task->rescheduleProgressively();
    expect($timeout)->equals(ScheduledTask::MAX_RESCHEDULE_TIMEOUT);
  }

  public function testItCanGetDueTasks() {
    // due (scheduled in past)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => Carbon::now()->subDay(),
    ]);

    // deleted (should not be fetched)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => Carbon::now()->subDay(),
      'deleted_at' => Carbon::now(),
    ]);

    // scheduled in future (should not be fetched)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => Carbon::now()->addDay(),
    ]);

    // wrong status (should not be fetched)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => null,
      'scheduled_at' => Carbon::now()->subDay(),
    ]);

    $tasks = ScheduledTask::findDueByType('test', 10);
    expect($tasks)->count(1);
  }

  public function testItCanGetRunningTasks() {
    // running (scheduled in past)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => null,
      'scheduled_at' => Carbon::now()->subDay(),
    ]);

    // deleted (should not be fetched)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => null,
      'scheduled_at' => Carbon::now()->subDay(),
      'deleted_at' => Carbon::now(),
    ]);

    // scheduled in future (should not be fetched)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => null,
      'scheduled_at' => Carbon::now()->addDay(),
    ]);

    // wrong status (should not be fetched)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => ScheduledTask::STATUS_COMPLETED,
      'scheduled_at' => Carbon::now()->subDay(),
    ]);

    $tasks = ScheduledTask::findRunningByType('test', 10);
    expect($tasks)->count(1);
  }

  public function testItCanGetCompletedTasks() {
    // completed (scheduled in past)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => ScheduledTask::STATUS_COMPLETED,
      'scheduled_at' => Carbon::now()->subDay(),
    ]);

    // deleted (should not be fetched)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => ScheduledTask::STATUS_COMPLETED,
      'scheduled_at' => Carbon::now()->subDay(),
      'deleted_at' => Carbon::now(),
    ]);

    // scheduled in future (should not be fetched)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => ScheduledTask::STATUS_COMPLETED,
      'scheduled_at' => Carbon::now()->addDay(),
    ]);

    // wrong status (should not be fetched)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => Carbon::now()->subDay(),
    ]);

    $tasks = ScheduledTask::findCompletedByType('test', 10);
    expect($tasks)->count(1);
  }

  public function testItCanGetFutureScheduledTasks() {
    // scheduled (in future)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => Carbon::now()->addDay(),
    ]);

    // deleted (should not be fetched)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => Carbon::now()->addDay(),
      'deleted_at' => Carbon::now(),
    ]);

    // scheduled in past (should not be fetched)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'scheduled_at' => Carbon::now()->subDay(),
    ]);

    // wrong status (should not be fetched)
    ScheduledTask::createOrUpdate([
      'type' => 'test',
      'status' => null,
      'scheduled_at' => Carbon::now()->addDay(),
    ]);

    $tasks = ScheduledTask::findDueByType('test', 10);
    expect($tasks)->count(1);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
  }
}
