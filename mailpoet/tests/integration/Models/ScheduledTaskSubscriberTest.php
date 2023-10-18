<?php declare(strict_types = 1);

namespace MailPoet\Test\Models;

use Codeception\Util\Fixtures;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Subscriber;

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
    verify($this->taskSubscriber->task_id)->equals($this->task->id);
    verify($this->taskSubscriber->subscriber_id)->equals($this->subscriber->id);
    verify($this->taskSubscriber->processed)->equals(ScheduledTaskSubscriber::STATUS_UNPROCESSED);
  }

  public function testItCanBeUpdated() {
    $taskSubscriber = ScheduledTaskSubscriber::createOrUpdate([
      'task_id' => $this->task->id,
      'subscriber_id' => $this->subscriber->id,
      'processed' => ScheduledTaskSubscriber::STATUS_PROCESSED,
    ]);
    verify($taskSubscriber->processed)->equals(ScheduledTaskSubscriber::STATUS_PROCESSED);
  }

  public function testItCanAddMultipleSubscribers() {
    ScheduledTaskSubscriber::deleteMany();
    $subscriberIds = [321, 654, 987]; // sorted random ids
    ScheduledTaskSubscriber::addSubscribers($this->task->id, $subscriberIds);
    $taskSubscribers = ScheduledTaskSubscriber::where('task_id', $this->task->id)
      ->orderByAsc('subscriber_id')
      ->findMany();
    verify(count($taskSubscribers))->equals(count($subscriberIds));
    verify($taskSubscribers[0]->subscriber_id)->equals($subscriberIds[0]);
    verify($taskSubscribers[1]->subscriber_id)->equals($subscriberIds[1]);
    verify($taskSubscribers[2]->subscriber_id)->equals($subscriberIds[2]);
  }

  public function testItCangetUnprocessedCount() {
    $count = ScheduledTaskSubscriber::getUnprocessedCount($this->task->id);
    verify($count)->equals(1);
    $this->taskSubscriber->processed = ScheduledTaskSubscriber::STATUS_PROCESSED;
    $this->taskSubscriber->save();
    $count = ScheduledTaskSubscriber::getUnprocessedCount($this->task->id);
    verify($count)->equals(0);
  }

  public function testItCanGetProcessedCount() {
    $count = ScheduledTaskSubscriber::getProcessedCount($this->task->id);
    verify($count)->equals(0);
    $this->taskSubscriber->processed = ScheduledTaskSubscriber::STATUS_PROCESSED;
    $this->taskSubscriber->save();
    $count = ScheduledTaskSubscriber::getProcessedCount($this->task->id);
    verify($count)->equals(1);
  }
}
