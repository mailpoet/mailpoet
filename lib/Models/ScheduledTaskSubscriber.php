<?php
namespace MailPoet\Models;

use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * @property int $task_id
 * @property int $subscriber_id
 * @property int $processed
 * @property int $failed
 * @property string $error
 */
class ScheduledTaskSubscriber extends Model {
  const STATUS_UNPROCESSED = 0;
  const STATUS_PROCESSED = 1;

  const FAIL_STATUS_OK = 0;
  const FAIL_STATUS_FAILED = 1;

  const SENDING_STATUS_SENT = 'sent';
  const SENDING_STATUS_FAILED = 'failed';
  const SENDING_STATUS_UNPROCESSED = 'unprocessed';

  public static $_table = MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE;
  public static $_id_column = ['task_id', 'subscriber_id'];

  function task() {
    return $this->hasOne(__NAMESPACE__ . '\ScheduledTask', 'id', 'task_id');
  }

  static function createOrUpdate($data = []) {
    if (!is_array($data) || empty($data['task_id']) || empty($data['subscriber_id'])) {
      return;
    }
    $data['processed'] = !empty($data['processed']) ? self::STATUS_PROCESSED : self::STATUS_UNPROCESSED;
    $data['failed'] = !empty($data['failed']) ? self::FAIL_STATUS_FAILED : self::FAIL_STATUS_OK;
    return parent::_createOrUpdate($data, [
      'subscriber_id' => $data['subscriber_id'],
      'task_id' => $data['task_id'],
    ]);
  }

  static function setSubscribers($task_id, array $subscriber_ids) {
    static::clearSubscribers($task_id);
    return static::addSubscribers($task_id, $subscriber_ids);
  }

  /**
   * For large batches use MailPoet\Segments\SubscribersFinder::addSubscribersToTaskFromSegments()
   */
  static function addSubscribers($task_id, array $subscriber_ids) {
    foreach ($subscriber_ids as $subscriber_id) {
      self::createOrUpdate([
        'task_id' => $task_id,
        'subscriber_id' => $subscriber_id,
      ]);
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

  static function listingQuery($data) {
    $group = isset($data['group']) ? $data['group'] : 'all';
    return self::join(Subscriber::$_table, ["subscriber_id", "=", "subscribers.id"], "subscribers")
      ->filter($group, $data['params'])
      ->select('error', 'error')
      ->select('failed', 'failed')
      ->select('task_id', 'taskId')
      ->select('processed', 'processed')
      ->select('subscribers.email', 'email')
      ->select('subscribers.id', 'subscriberId')
      ->select('subscribers.last_name', 'lastName')
      ->select('subscribers.first_name', 'firstName');
  }

  static function groups($data) {
    $params = $data['params'];
    return [
      [
        'name' => 'all',
        'label' => WPFunctions::get()->__('All', 'mailpoet'),
        'count' => self::filter('all', $params)->count(),
      ],
      [
        'name' => self::SENDING_STATUS_SENT,
        'label' => WPFunctions::get()->_x('Sent', 'status when a newsletter has been sent', 'mailpoet'),
        'count' => self::filter(self::SENDING_STATUS_SENT, $params)->count(),
      ],
      [
        'name' => self::SENDING_STATUS_FAILED,
        'label' => WPFunctions::get()->_x('Failed', 'status when the sending of a newsletter has failed', 'mailpoet'),
        'count' => self::filter(self::SENDING_STATUS_FAILED, $params)->count(),
      ],
      [
        'name' => self::SENDING_STATUS_UNPROCESSED,
        'label' => WPFunctions::get()->_x('Unprocessed', 'status when the sending of a newsletter has not been processed', 'mailpoet'),
        'count' => self::filter(self::SENDING_STATUS_UNPROCESSED, $params)->count(),
      ],
    ];
  }

  static function all($orm, $params) {
    return $orm->whereIn('task_id', $params['task_ids']);
  }

  static function sent($orm, $params) {
    return $orm->filter('all', $params)
      ->where('processed', self::STATUS_PROCESSED)
      ->where('failed', self::FAIL_STATUS_OK);
  }

  static function failed($orm, $params) {
    return $orm->filter('all', $params)
      ->where('processed', self::STATUS_PROCESSED)
      ->where('failed', self::FAIL_STATUS_FAILED);
  }

  static function unprocessed($orm, $params) {
    return $orm->filter('all', $params)
      ->where('processed', self::STATUS_UNPROCESSED);
  }

  private static function getCount($task_id, $processed = null) {
    $orm = self::where('task_id', $task_id);
    if (!is_null($processed)) {
      $orm->where('processed', $processed);
    }
    return $orm->count();
  }
}
