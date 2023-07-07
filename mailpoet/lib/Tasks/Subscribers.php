<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Tasks;

use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;

class Subscribers {
  private $task;

  public function __construct(
    ScheduledTask $task
  ) {
    $this->task = $task;
  }

  public function getSubscribers() {
    return ScheduledTaskSubscriber::where('task_id', $this->task->id);
  }
}
