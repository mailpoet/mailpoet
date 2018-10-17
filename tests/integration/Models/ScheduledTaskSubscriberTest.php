<?php
namespace MailPoet\Test\Models;

use Codeception\Util\Fixtures;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Subscriber;

class ScheduledTaskSubscriberTest extends \MailPoetTest {
  function _before() {
    $task = ScheduledTask::create();
    $task->hydrate(array(
      'status' => ScheduledTask::STATUS_SCHEDULED
    ));
    $this->task = $task->save();

    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $this->subscriber = $subscriber->save();

    $this->task_subscriber = ScheduledTaskSubscriber::createOrUpdate(array(
      'task_id' => $this->task->id,
      'subscriber_id' => $this->subscriber->id
    ));
  }

  function testItCanBeCreated() {
    expect($this->task_subscriber->task_id)->equals($this->task->id);
    expect($this->task_subscriber->subscriber_id)->equals($this->subscriber->id);
    expect($this->task_subscriber->processed)->equals(ScheduledTaskSubscriber::STATUS_UNPROCESSED);
  }

  function testItCanBeUpdated() {
    $task_subscriber = ScheduledTaskSubscriber::createOrUpdate(array(
      'task_id' => $this->task->id,
      'subscriber_id' => $this->subscriber->id,
      'processed' => ScheduledTaskSubscriber::STATUS_PROCESSED
    ));
    expect($task_subscriber->processed)->equals(ScheduledTaskSubscriber::STATUS_PROCESSED);
  }

  function testItCanAddMultipleSubscribers() {
    ScheduledTaskSubscriber::deleteMany();
    $subscriber_ids = array(321, 654, 987); // sorted random ids
    ScheduledTaskSubscriber::addSubscribers($this->task->id, $subscriber_ids);
    $task_subscribers = ScheduledTaskSubscriber::where('task_id', $this->task->id)
      ->orderByAsc('subscriber_id')
      ->findMany();
    expect(count($task_subscribers))->equals(count($subscriber_ids));
    expect($task_subscribers[0]->subscriber_id)->equals($subscriber_ids[0]);
    expect($task_subscribers[1]->subscriber_id)->equals($subscriber_ids[1]);
    expect($task_subscribers[2]->subscriber_id)->equals($subscriber_ids[2]);
  }

  function testItCangetUnprocessedCount() {
    $count = ScheduledTaskSubscriber::getUnprocessedCount($this->task->id);
    expect($count)->equals(1);
    $this->task_subscriber->processed = ScheduledTaskSubscriber::STATUS_PROCESSED;
    $this->task_subscriber->save();
    $count = ScheduledTaskSubscriber::getUnprocessedCount($this->task->id);
    expect($count)->equals(0);
  }

  function testItCanGetProcessedCount() {
    $count = ScheduledTaskSubscriber::getProcessedCount($this->task->id);
    expect($count)->equals(0);
    $this->task_subscriber->processed = ScheduledTaskSubscriber::STATUS_PROCESSED;
    $this->task_subscriber->save();
    $count = ScheduledTaskSubscriber::getProcessedCount($this->task->id);
    expect($count)->equals(1);
  }

  function testItCanGetTotalCount() {
    ScheduledTaskSubscriber::createOrUpdate(array(
      'task_id' => $this->task->id,
      'subscriber_id' => 555, // random new ID
      'processed' => ScheduledTaskSubscriber::STATUS_PROCESSED
    ));
    $count = ScheduledTaskSubscriber::getTotalCount($this->task->id);
    expect($count)->equals(2);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}
