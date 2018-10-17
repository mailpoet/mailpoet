<?php
namespace MailPoet\Test\Tasks\Subscribers;

use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Tasks\Subscribers\BatchIterator;

class BatchIteratorTest extends \MailPoetTest {
  function _before() {
    $this->task_id = 123; // random ID
    $this->batch_size = 2;
    $this->subscriber_count = 10;
    for($i = 0; $i < $this->subscriber_count; $i++) {
      ScheduledTaskSubscriber::createOrUpdate(array(
        'task_id' => $this->task_id,
        'subscriber_id' => $i + 1,
      ));
    }
    $this->iterator = new BatchIterator($this->task_id, $this->batch_size);
  }

  function testItFailsToConstructWithWrongArguments() {
    try {
      $iterator = new BatchIterator(0, 0);
      $this->fail('Exception was not thrown');
    } catch(\Exception $e) {
      // No exception handling necessary
    }
  }

  function testItConstructs() {
    $iterator = new BatchIterator(123, 456); // random IDs
    expect_that($iterator instanceof BatchIterator);
  }

  function testItIterates() {
    $iterations = ceil($this->subscriber_count / $this->batch_size);
    $i = 0;
    foreach($this->iterator as $batch) {
      $i++;

      // process subscribers
      ScheduledTaskSubscriber::where('task_id', $this->task_id)
        ->whereIn('subscriber_id', $batch)
        ->findResultSet()
        ->set('processed', ScheduledTaskSubscriber::STATUS_PROCESSED)
        ->save();

      if($i < $iterations) {
        expect(count($batch))->equals($this->batch_size);
      } else {
        expect(count($batch))->lessOrEquals($this->batch_size);
      }
    }
    expect($i)->equals($iterations);
  }

  function testItCanBeCounted() {
    expect(count($this->iterator))->equals($this->subscriber_count);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
  }
}
