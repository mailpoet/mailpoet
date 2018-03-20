<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class ScheduledTaskSubscriber extends Model {
  const STATUS_UNPROCESSED = 0;
  const STATUS_PROCESSED = 1;

  public static $_table = MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE;
  public static $_id_column = array('task_id', 'subscriber_id');

  function task() {
    return $this->hasOne(__NAMESPACE__ . '\ScheduledTask', 'id', 'task_id');
  }

  static function createOrUpdate($data = array()) {
    if(!is_array($data) || empty($data['task_id']) || empty($data['subscriber_id'])) {
      return;
    }
    $data['processed'] = !empty($data['processed']) ? self::STATUS_PROCESSED : self::STATUS_UNPROCESSED;
    return parent::_createOrUpdate($data, array(
      'subscriber_id' => $data['subscriber_id'],
      'task_id' => $data['task_id']
    ));
  }

  static function setSubscribers($task_id, array $subscriber_ids) {
    static::clearSubscribers($task_id);
    return static::addSubscribers($task_id, $subscriber_ids);
  }

  /**
   * For large batches use MailPoet\Segments\SubscribersFinder::addSubscribersToTaskFromSegments()
   */
  static function addSubscribers($task_id, array $subscriber_ids) {
    foreach($subscriber_ids as $subscriber_id) {
      self::createOrUpdate(array(
        'task_id' => $task_id,
        'subscriber_id' => $subscriber_id
      ));
    }
  }

  static function clearSubscribers($task_id) {
    return self::where('task_id', $task_id)->deleteMany();
  }

  static function getUnprocessedCount($task_id) {
    return self::getCount($task_id, self::STATUS_UNPROCESSED);
  }

  static function getProcessedCount($task_id) {
    return self::getCount($task_id, self::STATUS_PROCESSED);
  }

  static function getTotalCount($task_id) {
    return self::getCount($task_id);
  }

  private static function getCount($task_id, $processed = null) {
    $orm = self::where('task_id', $task_id);
    if(!is_null($processed)) {
      $orm->where('processed', $processed);
    }
    return $orm->count();
  }
}
