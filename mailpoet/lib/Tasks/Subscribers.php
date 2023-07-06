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

  public function removeAllSubscribers() {
    $this->getSubscribers()
      ->deleteMany();
    $this->checkCompleted();
  }

  public function updateProcessedSubscribers(array $processedSubscribers) {
    if (!empty($processedSubscribers)) {
      ScheduledTaskSubscriber::rawExecute(sprintf(
        'UPDATE %1$s SET processed = %2$s WHERE task_id = %3$s AND subscriber_id IN (%4$s)',
        ScheduledTaskSubscriber::$_table,
        ScheduledTaskSubscriber::STATUS_PROCESSED,
        $this->task->id,
        join(', ', array_map('intval', $processedSubscribers))
      ));
    }
    $this->checkCompleted();
  }

  public function saveSubscriberError($subcriberId, $errorMessage) {
    $this->getSubscribers()
      ->where('subscriber_id', $subcriberId)
      ->findResultSet()
      ->set('failed', ScheduledTaskSubscriber::FAIL_STATUS_FAILED)
      ->set('processed', ScheduledTaskSubscriber::STATUS_PROCESSED)
      ->set('error', $errorMessage)
      ->save();
    $this->checkCompleted();
  }

  private function checkCompleted($count = null) {
    if (!$count && !ScheduledTaskSubscriber::getUnprocessedCount($this->task->id)) {
      $this->task->complete();
    }
  }
}
