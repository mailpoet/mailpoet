<?php

namespace MailPoet\Test\DataFactories;

class ScheduledTask {
  function deleteAll() {
    $tasks = \MailPoet\Models\ScheduledTask::findMany();
    foreach ($tasks as $task) {
      $task->delete();
    }
  }
}
