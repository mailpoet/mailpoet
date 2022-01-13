<?php

namespace MailPoet\Test\Tasks\Subscribers;

use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Tasks\Subscribers\BatchIterator;
use MailPoetVendor\Idiorm\ORM;

class BatchIteratorTest extends \MailPoetTest {
  public $iterator;
  public $subscriberCount;
  public $batchSize;
  public $taskId;

  public function _before() {
    parent::_before();
    $this->taskId = 123; // random ID
    $this->batchSize = 2;
    $this->subscriberCount = 10;
    for ($i = 0; $i < $this->subscriberCount; $i++) {
      ScheduledTaskSubscriber::createOrUpdate([
        'task_id' => $this->taskId,
        'subscriber_id' => $i + 1,
      ]);
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

      // process subscribers
      // @phpstan-ignore-next-line
      ScheduledTaskSubscriber::where('task_id', $this->taskId) 
        ->whereIn('subscriber_id', $batch)
        ->findResultSet()
        ->set('processed', ScheduledTaskSubscriber::STATUS_PROCESSED)
        ->save();

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

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
  }
}
