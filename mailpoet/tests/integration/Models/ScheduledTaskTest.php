<?php declare(strict_types = 1);

namespace MailPoet\Test\Models;

use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

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
    $outdatedTask = ScheduledTask::createOrUpdate([
      'status' => ScheduledTask::STATUS_PAUSED,
      'scheduled_at' => Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))->subDays(31)->format('Y-m-d H:i:s'),
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
    SendingQueue::createOrUpdate([
      'newsletter_id' => $newsletter->id(),
      'task_id' => $outdatedTask->id(),
    ]);
    ScheduledTask::setScheduledAllByNewsletter($newsletter);
    $task1Found = ScheduledTask::findOne($task1->id());
    $this->assertInstanceOf(ScheduledTask::class, $task1Found);
    expect($task1Found->status)->equals(ScheduledTask::STATUS_SCHEDULED);
    $task2Found = ScheduledTask::findOne($task2->id());
    $this->assertInstanceOf(ScheduledTask::class, $task2Found);
    expect($task2Found->status)->equals(ScheduledTask::STATUS_COMPLETED);
    $task3Found = ScheduledTask::findOne($task3->id());
    $this->assertInstanceOf(ScheduledTask::class, $task3Found);
    expect($task3Found->status)->equals(ScheduledTask::STATUS_SCHEDULED);
    $outdatedTaskFound = ScheduledTask::findOne($outdatedTask->id());
    $this->assertInstanceOf(ScheduledTask::class, $outdatedTaskFound);
    expect($outdatedTaskFound->status)->equals(ScheduledTask::STATUS_PAUSED);
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
    $task1Found = ScheduledTask::findOne($task1->id());
    $this->assertInstanceOf(ScheduledTask::class, $task1Found);
    expect($task1Found->status)->equals(ScheduledTask::STATUS_COMPLETED);
    $task2Found = ScheduledTask::findOne($task2->id());
    $this->assertInstanceOf(ScheduledTask::class, $task2Found);
    expect($task2Found->status)->equals(ScheduledTask::STATUS_PAUSED);
  }

  public function testItDeletesRelatedScheduledTaskSubscriber() {
    $taskId = $this->task->id;
    ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $taskId,
      'subscriber_id' => 1,
    ]);
    ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $taskId,
      'subscriber_id' => 2,
    ]);
    ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $taskId,
      'subscriber_id' => 3,
    ]);
    $count = ScheduledTaskSubscriber::where('task_id', $taskId)->count();
    expect($count)->equals(3);

    $this->task->delete();
    $count = ScheduledTaskSubscriber::where('task_id', $taskId)->count();
    expect($count)->equals(0);
  }

  public function testItJsonEncodesMetaWhenSaving() {
    $task = ScheduledTask::create();
    $this->assertInstanceOf(ScheduledTask::class, $task);
    $meta = [
      'some' => 'value',
    ];
    $task->meta = $meta;
    $task->save();

    $task = ScheduledTask::findOne($task->id);
    $this->assertInstanceOf(ScheduledTask::class, $task);

    /** @var string $taskMeta */
    $taskMeta = $task->meta;
    expect(Helpers::isJson($taskMeta))->true();
    expect(json_decode($taskMeta, true))->equals($meta);
  }

  public function testItDoesNotJsonEncodesMetaEqualToNull() {
    $task = ScheduledTask::create();
    $meta = null;
    $task->meta = $meta;
    $task->save();

    $task = ScheduledTask::findOne($task->id);
    $this->assertInstanceOf(ScheduledTask::class, $task);

    expect(Helpers::isJson($task->meta))->false();
    expect($task->meta)->equals($meta);
  }
}
