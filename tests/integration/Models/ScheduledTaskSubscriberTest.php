<?php

namespace MailPoet\Test\Models;

use Codeception\Util\Fixtures;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

class ScheduledTaskSubscriberTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    $task = ScheduledTask::create();
    $task->hydrate([
      'status' => ScheduledTask::STATUS_SCHEDULED,
    ]);
    $this->task = $task->save();

    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $this->subscriber = $subscriber->save();

    $this->task_subscriber = ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $this->task->id,
      'subscriber_id' => $this->subscriber->id,
    ]);
    $this->subscribers_counter = 0;
  }

  function testItCanBeCreated() {
    expect($this->task_subscriber->task_id)->equals($this->task->id);
    expect($this->task_subscriber->subscriber_id)->equals($this->subscriber->id);
    expect($this->task_subscriber->processed)->equals(ScheduledTaskSubscriber::STATUS_UNPROCESSED);
  }

  function testItCanBeUpdated() {
    $task_subscriber = ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $this->task->id,
      'subscriber_id' => $this->subscriber->id,
      'processed' => ScheduledTaskSubscriber::STATUS_PROCESSED,
    ]);
    expect($task_subscriber->processed)->equals(ScheduledTaskSubscriber::STATUS_PROCESSED);
  }

  function testItCanAddMultipleSubscribers() {
    ScheduledTaskSubscriber::deleteMany();
    $subscriber_ids = [321, 654, 987]; // sorted random ids
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
    ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $this->task->id,
      'subscriber_id' => 555, // random new ID
      'processed' => ScheduledTaskSubscriber::STATUS_PROCESSED,
    ]);
    $count = ScheduledTaskSubscriber::getTotalCount($this->task->id);
    expect($count)->equals(2);
  }

  function testItCanQueryListing() {
    $task_ids = $this->makeTasksWithSubscribers();

    $all = ScheduledTaskSubscriber::listingQuery([
      'params' => ['task_ids' => $task_ids],
    ])->findMany();
    expect(count($all))->equals(12);

    $sent = ScheduledTaskSubscriber::listingQuery([
      'group' => ScheduledTaskSubscriber::SENDING_STATUS_SENT,
      'params' => ['task_ids' => $task_ids],
    ])->findMany();
    expect(count($sent))->equals(4);
    foreach ($sent as $task) {
      expect($task->processed)->equals(1);
      expect($task->failed)->equals(0);
    }

    $unprocessed = ScheduledTaskSubscriber::listingQuery([
      'group' => ScheduledTaskSubscriber::SENDING_STATUS_UNPROCESSED,
      'params' => ['task_ids' => $task_ids],
    ])->findMany();
    expect(count($unprocessed))->equals(4);
    foreach ($unprocessed as $task) {
      expect($task->processed)->equals(0);
      expect($task->failed)->equals(0);
    }

    $failed = ScheduledTaskSubscriber::listingQuery([
      'group' => ScheduledTaskSubscriber::SENDING_STATUS_FAILED,
      'params' => ['task_ids' => $task_ids],
    ])->findMany();
    expect(count($failed))->equals(4);
    foreach ($failed as $task) {
      expect($task->processed)->equals(1);
      expect($task->failed)->equals(1);
    }

  }

  function testItCanGetGroupsWithCounts() {
    $task_ids = $this->makeTasksWithSubscribers();
    $groups = ScheduledTaskSubscriber::groups([
      'params' => ['task_ids' => $task_ids],
    ]);
    expect($groups)->equals([
      [
        'name' => 'all',
        'label' => 'All',
        'count' => 12,
      ],
      [
        'name' => ScheduledTaskSubscriber::SENDING_STATUS_SENT,
        'label' => 'Sent',
        'count' => 4,
      ],
      [
        'name' => ScheduledTaskSubscriber::SENDING_STATUS_FAILED,
        'label' => 'Failed',
        'count' => 4,
      ],
      [
        'name' => ScheduledTaskSubscriber::SENDING_STATUS_UNPROCESSED,
        'label' => 'Unprocessed',
        'count' => 4,
      ],
    ]);
  }

  /**
   * Creates completed, scheduled, paued and running tasks.
   * Each one with unprocessed, sent and failed subscriber tasks.
   * @return array the ids of the 4 tasks.
   */
  private function makeTasksWithSubscribers() {
    $tasks = [
      ScheduledTask::createOrUpdate(['status' => ScheduledTask::STATUS_COMPLETED]),
      ScheduledTask::createOrUpdate(['status' => ScheduledTask::STATUS_SCHEDULED]),
      ScheduledTask::createOrUpdate(['status' => ScheduledTask::STATUS_PAUSED]),
      ScheduledTask::createOrUpdate(['status' => null]), // running
    ];
    foreach ($tasks as $task) {
      ScheduledTaskSubscriber::createOrUpdate([
        'task_id' => $task->id,
        'subscriber_id' => $this->makeSubscriber()->id,
        'processed' => 0,
        'failed' => 0,
      ]);
      ScheduledTaskSubscriber::createOrUpdate([
        'task_id' => $task->id,
        'subscriber_id' => $this->makeSubscriber()->id,
        'processed' => 1,
        'failed' => 0,
      ]);
      ScheduledTaskSubscriber::createOrUpdate([
        'task_id' => $task->id,
        'subscriber_id' => $this->makeSubscriber()->id,
        'processed' => 1,
        'failed' => 1,
        'error' => 'Something went wrong!',
      ]);
    }

    return array_map(function($task) {
      return $task->id;
    }, $tasks);
  }

  private function makeSubscriber() {
    $number = $this->subscribers_counter ++;
    return $subscriber = Subscriber::createOrUpdate([
      'last_name' => 'Last Name ' . $number,
      'first_name' => 'First Name ' . $number,
      'email' => 'john.doe.' . $number . '@example.com',
    ]);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}
