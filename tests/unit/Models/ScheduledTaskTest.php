<?php
namespace MailPoet\Test\Models;

use MailPoet\Models\ScheduledTask;

class ScheduledTaskTest extends \MailPoetTest {
  function _before() {
    $this->task = ScheduledTask::create();
    $this->task->hydrate(array(
      'status' => ScheduledTask::STATUS_SCHEDULED
    ));
    $this->task->save();
  }

  function testItCanBeCompleted() {
    $this->task->complete();
    expect($this->task->status)->equals(ScheduledTask::STATUS_COMPLETED);
  }

  function testItSetsDefaultPriority() {
    expect($this->task->priority)->equals(ScheduledTask::PRIORITY_MEDIUM);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
