<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class ScheduledTask extends Model {
  public static $_table = MP_SCHEDULED_TASKS_TABLE;
  const STATUS_COMPLETED = 'completed';
  const STATUS_SCHEDULED = 'scheduled';
  const PRIORITY_HIGH = 1;
  const PRIORITY_MEDIUM = 5;
  const PRIORITY_LOW = 10;

  function taskSubscribers() {
    return $this->has_many(__NAMESPACE__ . '\ScheduledTaskSubscriber', 'task_id', 'id');
  }

  function complete() {
    $this->set('status', self::STATUS_COMPLETED);
    $this->save();
    return ($this->getErrors() === false && $this->id() > 0);
  }

  function save() {
    // set the default priority to medium
    if(!$this->priority) {
      $this->priority = self::PRIORITY_MEDIUM;
    }
    parent::save();
    return $this;
  }

  function updateProcessedSubscribers(array $processed_subscribers) {
    $this->taskSubscribers()
      ->whereIn('subscriber_id', $processed_subscribers)
      ->findResultSet()
      ->set('processed', ScheduledTaskSubscriber::STATUS_PROCESSED)
      ->save();
    $this->checkCompleted();
  }

  private function checkCompleted() {
    if(!ScheduledTaskSubscriber::getToProcessCount($this->id)) {
      $this->processed_at = current_time('mysql');
      $this->status = self::STATUS_COMPLETED;
      return $this->save();
    }
  }
}
