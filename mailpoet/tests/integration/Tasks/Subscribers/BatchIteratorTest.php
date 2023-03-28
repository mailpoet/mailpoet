<?php declare(strict_types = 1);

namespace MailPoet\Test\Tasks\Subscribers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Tasks\Subscribers\BatchIterator;
use MailPoet\Test\DataFactories\ScheduledTask as ScheduledTaskFactory;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber as ScheduledTaskSubscriberFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;

class BatchIteratorTest extends \MailPoetTest {
  public $iterator;
  public $subscriberCount;
  public $batchSize;
  public $taskId;

  public function _before() {
    parent::_before();
    $this->batchSize = 2;
    $this->subscriberCount = 10;

    $scheduledTaskFactory = new ScheduledTaskFactory();
    $task = $scheduledTaskFactory->create('some_task_type', ScheduledTaskEntity::STATUS_SCHEDULED, new Carbon());
    $this->taskId = $task->getId();

    $scheduledTaskSubscriberFactory = new ScheduledTaskSubscriberFactory();

    for ($i = 0; $i < $this->subscriberCount; $i++) {
      $subscriberFactory = new SubscriberFactory();
      $subscriber = $subscriberFactory->create();
      $scheduledTaskSubscriberFactory->createUnprocessed($task, $subscriber);
    }
    $this->iterator = new BatchIterator($this->taskId, $this->batchSize);
  }

  public function testItFailsToConstructWithWrongArguments() {
    try {
      $iterator = new BatchIterator(0, 0);
      $this->fail('Exception was not thrown');
    } catch (\Exception $e) {
      // No exception handling necessary
    }
  }

  public function testItConstructs() {
    $iterator = new BatchIterator(123, 456); // random IDs
    expect_that($iterator instanceof BatchIterator);
  }

  public function testItIterates() {
    $iterations = ceil($this->subscriberCount / $this->batchSize);
    $i = 0;
    foreach ($this->iterator as $batch) {
      $i++;

      if ($i < $iterations) {
        expect(count($batch))->equals($this->batchSize);
      } else {
        expect(count($batch))->lessOrEquals($this->batchSize);
      }
    }
    expect($i)->equals($iterations);
  }

  public function testItCanBeCounted() {
    expect(count($this->iterator))->equals($this->subscriberCount);
  }
}
