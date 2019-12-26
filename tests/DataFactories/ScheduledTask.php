<?php

namespace MailPoet\Test\DataFactories;

class ScheduledTask {
  public function deleteAll() {
    $tasks = \MailPoet\Models\ScheduledTask::findMany();
    foreach ($tasks as $task) {
      $task->delete();
    }
  }
}
