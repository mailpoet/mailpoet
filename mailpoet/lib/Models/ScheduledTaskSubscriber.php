<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Models;

use MailPoet\Entities\ScheduledTaskSubscriberEntity;

/**
 * @property int $taskId
 * @property int $subscriberId
 * @property int $processed
 * @property int $failed
 * @property string $error
 */
class ScheduledTaskSubscriber extends Model {
  const STATUS_UNPROCESSED = ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED;
  const STATUS_PROCESSED = ScheduledTaskSubscriberEntity::STATUS_PROCESSED;

  const FAIL_STATUS_OK = ScheduledTaskSubscriberEntity::FAIL_STATUS_OK;
  const FAIL_STATUS_FAILED = ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED;

  const SENDING_STATUS_SENT = ScheduledTaskSubscriberEntity::SENDING_STATUS_SENT;
  const SENDING_STATUS_FAILED = ScheduledTaskSubscriberEntity::SENDING_STATUS_FAILED;
  const SENDING_STATUS_UNPROCESSED = ScheduledTaskSubscriberEntity::SENDING_STATUS_UNPROCESSED;

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

  private static function getCount($taskId, $processed = null) {
    $orm = self::where('task_id', $taskId);
    if (!is_null($processed)) {
      $orm->where('processed', $processed);
    }
    return $orm->count();
  }
}
