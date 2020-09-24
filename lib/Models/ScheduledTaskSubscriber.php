<?php

namespace MailPoet\Models;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @property int $taskId
 * @property int $subscriberId
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

  public static $_table = MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration
  public static $_id_column = ['task_id', 'subscriber_id']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps,PSR2.Classes.PropertyDeclaration

  public function task() {
    return $this->hasOne(__NAMESPACE__ . '\ScheduledTask', 'id', 'task_id');
  }

  public static function createOrUpdate($data = []) {
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

  public static function setSubscribers($taskId, array $subscriberIds) {
    static::clearSubscribers($taskId);
    return static::addSubscribers($taskId, $subscriberIds);
  }

  /**
   * For large batches use MailPoet\Segments\SubscribersFinder::addSubscribersToTaskFromSegments()
   */
  public static function addSubscribers($taskId, array $subscriberIds) {
    foreach ($subscriberIds as $subscriberId) {
      self::createOrUpdate([
        'task_id' => $taskId,
        'subscriber_id' => $subscriberId,
      ]);
    }
  }

  public static function clearSubscribers($taskId) {
    return self::where('task_id', $taskId)->deleteMany();
  }

  public static function getUnprocessedCount($taskId) {
    return self::getCount($taskId, self::STATUS_UNPROCESSED);
  }

  public static function getProcessedCount($taskId) {
    return self::getCount($taskId, self::STATUS_PROCESSED);
  }

  public static function getTotalCount($taskId) {
    return self::getCount($taskId);
  }

  public static function listingQuery($data) {
    $group = isset($data['group']) ? $data['group'] : 'all';
    $query = self::join(Subscriber::$_table, ["subscriber_id", "=", "subscribers.id"], "subscribers")
      ->filter($group, $data['params'])
      ->select('error', 'error')
      ->select('failed', 'failed')
      ->select('task_id', 'taskId')
      ->select('processed', 'processed')
      ->select('subscribers.email', 'email')
      ->select('subscribers.id', 'subscriberId')
      ->select('subscribers.last_name', 'lastName')
      ->select('subscribers.first_name', 'firstName');
    if (isset($data['search'])) {
      $search = trim($data['search']);
      $search = Helpers::escapeSearch($search);
      if (strlen($search) === 0) {
        return $query;
      }
      $search = '%' . $search . '%';
      return $query->whereRaw(
        '(`subscribers`.`email` LIKE ? OR `subscribers`.`first_name` LIKE ? OR `subscribers`.`last_name` LIKE ?)',
        [$search, $search, $search]
      );
    }
    return $query;
  }

  public static function groups($data) {
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

  public static function all($orm, $params) {
    return $orm->whereIn('task_id', $params['task_ids']);
  }

  public static function sent($orm, $params) {
    return $orm->filter('all', $params)
      ->where('processed', self::STATUS_PROCESSED)
      ->where('failed', self::FAIL_STATUS_OK);
  }

  public static function failed($orm, $params) {
    return $orm->filter('all', $params)
      ->where('processed', self::STATUS_PROCESSED)
      ->where('failed', self::FAIL_STATUS_FAILED);
  }

  public static function unprocessed($orm, $params) {
    return $orm->filter('all', $params)
      ->where('processed', self::STATUS_UNPROCESSED);
  }

  private static function getCount($taskId, $processed = null) {
    $orm = self::where('task_id', $taskId);
    if (!is_null($processed)) {
      $orm->where('processed', $processed);
    }
    return $orm->count();
  }
}
