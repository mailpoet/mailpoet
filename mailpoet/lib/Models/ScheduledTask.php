<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Models;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;

/**
 * @property int $id
 * @property string $processedAt
 * @property string|null $status
 * @property string|null $type
 * @property int $priority
 * @property string|null $scheduledAt
 * @property bool|null $inProgress
 * @property int $rescheduleCount
 * @property string|array|null $meta
 */
class ScheduledTask extends Model {
  public static $_table = MP_SCHEDULED_TASKS_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration
  const STATUS_COMPLETED = ScheduledTaskEntity::STATUS_COMPLETED;
  const STATUS_SCHEDULED = ScheduledTaskEntity::STATUS_SCHEDULED;
  const STATUS_PAUSED = ScheduledTaskEntity::STATUS_PAUSED;
  const STATUS_INVALID = ScheduledTaskEntity::STATUS_INVALID;
  const VIRTUAL_STATUS_RUNNING = ScheduledTaskEntity::VIRTUAL_STATUS_RUNNING; // For historical reasons this is stored as null in DB
  const PRIORITY_HIGH = ScheduledTaskEntity::PRIORITY_HIGH;
  const PRIORITY_MEDIUM = ScheduledTaskEntity::PRIORITY_MEDIUM;
  const PRIORITY_LOW = ScheduledTaskEntity::PRIORITY_LOW;

  const BASIC_RESCHEDULE_TIMEOUT = ScheduledTaskEntity::BASIC_RESCHEDULE_TIMEOUT;
  const MAX_RESCHEDULE_TIMEOUT = ScheduledTaskEntity::MAX_RESCHEDULE_TIMEOUT;

  private $wp;

  public function __construct() {
    parent::__construct();
    $this->wp = WPFunctions::get();
  }

  public function subscribers() {
    return $this->hasManyThrough(
      __NAMESPACE__ . '\Subscriber',
      __NAMESPACE__ . '\ScheduledTaskSubscriber',
      'task_id',
      'subscriber_id'
    );
  }

  public function pause() {
    $this->set('status', self::STATUS_PAUSED);
    $this->save();
    return ($this->getErrors() === false && $this->id() > 0);
  }

  public static function pauseAllByNewsletter(Newsletter $newsletter) {
    ScheduledTask::rawExecute(
      'UPDATE `' . ScheduledTask::$_table . '` t ' .
      'JOIN `' . SendingQueue::$_table . '` q ON t.`id` = q.`task_id` ' .
      'SET t.`status` = "' . self::STATUS_PAUSED . '" ' .
      'WHERE ' .
      'q.`newsletter_id` = ' . $newsletter->id() .
      ' AND t.`status` = "' . self::STATUS_SCHEDULED . '" '
    );
  }

  public function resume() {
    $this->setExpr('status', 'NULL');
    $this->save();
    return ($this->getErrors() === false && $this->id() > 0);
  }

  public static function setScheduledAllByNewsletter(Newsletter $newsletter) {
    ScheduledTask::rawExecute(
      'UPDATE `' . ScheduledTask::$_table . '` t ' .
      'JOIN `' . SendingQueue::$_table . '` q ON t.`id` = q.`task_id` ' .
      'SET t.`status` = "' . self::STATUS_SCHEDULED . '" ' .
      'WHERE ' .
      'q.`newsletter_id` = ' . $newsletter->id() .
      ' AND t.`status` = "' . self::STATUS_PAUSED . '" ' .
      ' AND t.`scheduled_at` > CURDATE() - INTERVAL 30 DAY'
    );
  }

  public function complete() {
    $this->processedAt = $this->wp->currentTime('mysql');
    $this->set('status', self::STATUS_COMPLETED);
    $this->save();
    return ($this->getErrors() === false && $this->id() > 0);
  }

  public function save() {
    // set the default priority to medium
    if (!$this->priority) {
      $this->priority = self::PRIORITY_MEDIUM;
    }
    if (!is_null($this->meta) && !Helpers::isJson($this->meta)) {
      $this->set(
        'meta',
        (string)json_encode($this->meta)
      );
    }
    parent::save();
    return $this;
  }

  public function asArray() {
    $model = parent::asArray();
    $model['meta'] = $this->getMeta();
    return $model;
  }

  public function getMeta() {
    $meta = (Helpers::isJson($this->meta) && is_string($this->meta)) ? json_decode($this->meta, true) : $this->meta;
    return !empty($meta) ? (array)$meta : [];
  }

  public function delete() {
    try {
      ORM::get_db()->beginTransaction();
      ScheduledTaskSubscriber::where('task_id', $this->id)->deleteMany();
      parent::delete();
      ORM::get_db()->commit();
    } catch (\Exception $error) {
      ORM::get_db()->rollBack();
      throw $error;
    }
    return null;
  }

  /**
   * @return ScheduledTask|null
   */
  public static function findOneScheduledByNewsletterIdAndSubscriberId($newsletterId, $subscriberId) {
    return ScheduledTask::tableAlias('tasks')
      ->select('tasks.*')
      ->innerJoin(SendingQueue::$_table, 'queues.task_id = tasks.id', 'queues')
      ->innerJoin(ScheduledTaskSubscriber::$_table, 'task_subscribers.task_id = tasks.id', 'task_subscribers')
      ->where('queues.newsletter_id', $newsletterId)
      ->where('tasks.status', ScheduledTask::STATUS_SCHEDULED)
      ->where('task_subscribers.subscriber_id', $subscriberId)
      ->whereNull('queues.deleted_at')
      ->whereNull('tasks.deleted_at')
      ->findOne() ?: null;
  }
}
