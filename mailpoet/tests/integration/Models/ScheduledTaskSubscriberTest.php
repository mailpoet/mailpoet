<?php

namespace MailPoet\Test\Models;

use Codeception\Util\Fixtures;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

class ScheduledTaskSubscriberTest extends \MailPoetTest {
  public $subscribersCounter;
  public $taskSubscriber;
  public $subscriber;
  public $task;

  public function _before() {
    parent::_before();
    $task = ScheduledTask::create();
    $task->hydrate([
      'status' => ScheduledTask::STATUS_SCHEDULED,
    ]);
    $this->task = $task->save();

    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $this->subscriber = $subscriber->save();

    $this->taskSubscriber = ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $this->task->id,
      'subscriber_id' => $this->subscriber->id,
    ]);
    $this->subscribersCounter = 0;
  }

  public function testItCanBeCreated() {
    expect($this->taskSubscriber->task_id)->equals($this->task->id);
    expect($this->taskSubscriber->subscriber_id)->equals($this->subscriber->id);
    expect($this->taskSubscriber->processed)->equals(ScheduledTaskSubscriber::STATUS_UNPROCESSED);
  }

  public function testItCanBeUpdated() {
    $taskSubscriber = ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $this->task->id,
      'subscriber_id' => $this->subscriber->id,
      'processed' => ScheduledTaskSubscriber::STATUS_PROCESSED,
    ]);
    expect($taskSubscriber->processed)->equals(ScheduledTaskSubscriber::STATUS_PROCESSED);
  }

  public function testItCanAddMultipleSubscribers() {
    ScheduledTaskSubscriber::deleteMany();
    $subscriberIds = [321, 654, 987]; // sorted random ids
    ScheduledTaskSubscriber::addSubscribers($this->task->id, $subscriberIds);
    $taskSubscribers = ScheduledTaskSubscriber::where('task_id', $this->task->id)
      ->orderByAsc('subscriber_id')
      ->findMany();
    expect(count($taskSubscribers))->equals(count($subscriberIds));
    expect($taskSubscribers[0]->subscriber_id)->equals($subscriberIds[0]);
    expect($taskSubscribers[1]->subscriber_id)->equals($subscriberIds[1]);
    expect($taskSubscribers[2]->subscriber_id)->equals($subscriberIds[2]);
  }

  public function testItCangetUnprocessedCount() {
    $count = ScheduledTaskSubscriber::getUnprocessedCount($this->task->id);
    expect($count)->equals(1);
    $this->taskSubscriber->processed = ScheduledTaskSubscriber::STATUS_PROCESSED;
    $this->taskSubscriber->save();
    $count = ScheduledTaskSubscriber::getUnprocessedCount($this->task->id);
    expect($count)->equals(0);
  }

  public function testItCanGetProcessedCount() {
    $count = ScheduledTaskSubscriber::getProcessedCount($this->task->id);
    expect($count)->equals(0);
    $this->taskSubscriber->processed = ScheduledTaskSubscriber::STATUS_PROCESSED;
    $this->taskSubscriber->save();
    $count = ScheduledTaskSubscriber::getProcessedCount($this->task->id);
    expect($count)->equals(1);
  }

  public function testItCanGetTotalCount() {
    ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $this->task->id,
      'subscriber_id' => 555, // random new ID
      'processed' => ScheduledTaskSubscriber::STATUS_PROCESSED,
    ]);
    $count = ScheduledTaskSubscriber::getTotalCount($this->task->id);
    expect($count)->equals(2);
  }

  public function testItCanQueryListing() {
    $taskIds = $this->makeTasksWithSubscribers();

    $all = ScheduledTaskSubscriber::listingQuery([
      'params' => ['task_ids' => $taskIds],
    ])->findMany();
    expect(count($all))->equals(12);

    $sent = ScheduledTaskSubscriber::listingQuery([
      'group' => ScheduledTaskSubscriber::SENDING_STATUS_SENT,
      'params' => ['task_ids' => $taskIds],
    ])->findMany();
    expect(count($sent))->equals(4);
    foreach ($sent as $task) {
      expect($task->processed)->equals(1);
      expect($task->failed)->equals(0);
    }

    $unprocessed = ScheduledTaskSubscriber::listingQuery([
      'group' => ScheduledTaskSubscriber::SENDING_STATUS_UNPROCESSED,
      'params' => ['task_ids' => $taskIds],
    ])->findMany();
    expect(count($unprocessed))->equals(4);
    foreach ($unprocessed as $task) {
      expect($task->processed)->equals(0);
      expect($task->failed)->equals(0);
    }

    $failed = ScheduledTaskSubscriber::listingQuery([
      'group' => ScheduledTaskSubscriber::SENDING_STATUS_FAILED,
      'params' => ['task_ids' => $taskIds],
    ])->findMany();
    expect(count($failed))->equals(4);
    foreach ($failed as $task) {
      expect($task->processed)->equals(1);
      expect($task->failed)->equals(1);
    }

  }

  public function testItCanGetGroupsWithCounts() {
    $taskIds = $this->makeTasksWithSubscribers();
    $groups = ScheduledTaskSubscriber::groups([
      'params' => ['task_ids' => $taskIds],
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
    $number = $this->subscribersCounter ++;
    return $subscriber = Subscriber::createOrUpdate([
      'last_name' => 'Last Name ' . $number,
      'first_name' => 'First Name ' . $number,
      'email' => 'john.doe.' . $number . '@example.com',
    ]);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}
