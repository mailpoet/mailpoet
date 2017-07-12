<?php
namespace MailPoet\Test\Models;

use MailPoet\Models\ScheduledTaskSubscriber;

class ScheduledTaskSubscriberTest extends \MailPoetTest {
  function _before() {
    $this->task_id = 123;
    $this->subscriber_id = 456;
    $this->task_subscriber = ScheduledTaskSubscriber::createOrUpdate(array(
      'task_id' => $this->task_id,
      'subscriber_id' => $this->subscriber_id
    ));
  }

  function testItCanBeCreated() {
    expect($this->task_subscriber->task_id)->equals($this->task_id);
    expect($this->task_subscriber->subscriber_id)->equals($this->subscriber_id);
    expect($this->task_subscriber->processed)->equals(ScheduledTaskSubscriber::STATUS_TO_PROCESS);
  }

  function testItCanBeUpdated() {
    $task_subscriber = ScheduledTaskSubscriber::createOrUpdate(array(
      'task_id' => $this->task_id,
      'subscriber_id' => $this->subscriber_id,
      'processed' => ScheduledTaskSubscriber::STATUS_PROCESSED
    ));
    expect($task_subscriber->processed)->equals(ScheduledTaskSubscriber::STATUS_PROCESSED);
  }

  function testItCanAddMultipleSubscribers() {
    ScheduledTaskSubscriber::deleteMany();
    $subscriber_ids = array(321, 654, 987);
    ScheduledTaskSubscriber::addSubscribers($this->task_id, $subscriber_ids);
    $task_subscribers = ScheduledTaskSubscriber::where('task_id', $this->task_id)
      ->orderByAsc('subscriber_id')
      ->findMany();
    expect(count($task_subscribers))->equals(count($subscriber_ids));
    expect($task_subscribers[0]->subscriber_id)->equals($subscriber_ids[0]);
    expect($task_subscribers[1]->subscriber_id)->equals($subscriber_ids[1]);
    expect($task_subscribers[2]->subscriber_id)->equals($subscriber_ids[2]);
  }

  function testItCanGetToProcessCount() {
    $count = ScheduledTaskSubscriber::getToProcessCount($this->task_id);
    expect($count)->equals(1);
    $this->task_subscriber->processed = ScheduledTaskSubscriber::STATUS_PROCESSED;
    $this->task_subscriber->save();
    $count = ScheduledTaskSubscriber::getToProcessCount($this->task_id);
    expect($count)->equals(0);
  }

  function testItCanGetProcessedCount() {
    $count = ScheduledTaskSubscriber::getProcessedCount($this->task_id);
    expect($count)->equals(0);
    $this->task_subscriber->processed = ScheduledTaskSubscriber::STATUS_PROCESSED;
    $this->task_subscriber->save();
    $count = ScheduledTaskSubscriber::getProcessedCount($this->task_id);
    expect($count)->equals(1);
  }

  function testItCanGetTotalCount() {
    ScheduledTaskSubscriber::createOrUpdate(array(
      'task_id' => $this->task_id,
      'subscriber_id' => 555,
      'processed' => ScheduledTaskSubscriber::STATUS_PROCESSED
    ));
    $count = ScheduledTaskSubscriber::getTotalCount($this->task_id);
    expect($count)->equals(2);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
  }
}
