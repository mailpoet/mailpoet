<?php

namespace MailPoet\Models;

use Carbon\Carbon;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * @property int $id
 * @property string $processed_at
 * @property string|null $status
 * @property string|null $type
 * @property int $priority
 * @property string|null $scheduled_at
 * @property int $reschedule_count
 * @property string|array|null $meta
 */
class ScheduledTask extends Model {
  public static $_table = MP_SCHEDULED_TASKS_TABLE;
  const STATUS_COMPLETED = 'completed';
  const STATUS_SCHEDULED = 'scheduled';
  const STATUS_PAUSED = 'paused';
  const VIRTUAL_STATUS_RUNNING = 'running'; // For historical reasons this is stored as null in DB
  const PRIORITY_HIGH = 1;
  const PRIORITY_MEDIUM = 5;
  const PRIORITY_LOW = 10;

  const BASIC_RESCHEDULE_TIMEOUT = 5; //minutes
  const MAX_RESCHEDULE_TIMEOUT = 1440; //minutes

  private $wp;

  function __construct() {
    parent::__construct();
    $this->wp = new WPFunctions();
  }

  function subscribers() {
    return $this->hasManyThrough(
      __NAMESPACE__ . '\Subscriber',
      __NAMESPACE__ . '\ScheduledTaskSubscriber',
      'task_id',
      'subscriber_id'
    );
  }

  /** @return StatsNotification */
  function statsNotification() {
    $model = $this->hasOne(
      StatsNotification::class,
      'task_id',
      'id'
    );
    return $model;
  }

  function pause() {
    $this->set('status', self::STATUS_PAUSED);
    $this->save();
    return ($this->getErrors() === false && $this->id() > 0);
  }

  static function pauseAllByNewsletter(Newsletter $newsletter) {
    ScheduledTask::rawExecute(
      'UPDATE `' . ScheduledTask::$_table . '` t ' .
      'JOIN `' . SendingQueue::$_table . '` q ON t.`id` = q.`task_id` ' .
      'SET t.`status` = "' . self::STATUS_PAUSED . '" ' .
      'WHERE ' .
      'q.`newsletter_id` = ' . $newsletter->id() .
      ' AND t.`status` = "' . self::STATUS_SCHEDULED . '" '
    );
  }

  function resume() {
    $this->setExpr('status', 'NULL');
    $this->save();
    return ($this->getErrors() === false && $this->id() > 0);
  }

  static function setScheduledAllByNewsletter(Newsletter $newsletter) {
    ScheduledTask::rawExecute(
      'UPDATE `' . ScheduledTask::$_table . '` t ' .
      'JOIN `' . SendingQueue::$_table . '` q ON t.`id` = q.`task_id` ' .
      'SET t.`status` = "' . self::STATUS_SCHEDULED . '" ' .
      'WHERE ' .
      'q.`newsletter_id` = ' . $newsletter->id() .
      ' AND t.`status` = "' . self::STATUS_PAUSED . '" ' .
      ' AND t.`scheduled_at` > NOW()'
    );
  }

  function complete() {
    $this->processed_at = $this->wp->currentTime('mysql');
    $this->set('status', self::STATUS_COMPLETED);
    $this->save();
    return ($this->getErrors() === false && $this->id() > 0);
  }

  function save() {
    // set the default priority to medium
    if (!$this->priority) {
      $this->priority = self::PRIORITY_MEDIUM;
    }
    if (!is_null($this->meta) && !Helpers::isJson($this->meta)) {
      $this->set(
        'meta',
        json_encode($this->meta)
      );
    }
    parent::save();
    return $this;
  }

  function asArray() {
    $model = parent::asArray();
    $model['meta'] = $this->getMeta();
    return $model;
  }

  function getMeta() {
    return (Helpers::isJson($this->meta)) ? json_decode($this->meta, true) : $this->meta;
  }

  function delete() {
    try {
      \ORM::get_db()->beginTransaction();
      ScheduledTaskSubscriber::where('task_id', $this->id)->deleteMany();
      parent::delete();
      \ORM::get_db()->commit();
    } catch (\Exception $error) {
      \ORM::get_db()->rollBack();
      throw $error;
    }
  }

  function rescheduleProgressively() {
    $scheduled_at = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    $timeout = min(self::BASIC_RESCHEDULE_TIMEOUT * pow(2, $this->reschedule_count), self::MAX_RESCHEDULE_TIMEOUT);
    $this->scheduled_at = $scheduled_at->addMinutes($timeout);
    $this->reschedule_count++;
    $this->status = ScheduledTask::STATUS_SCHEDULED;
    $this->save();
    return $timeout;
  }

  static function touchAllByIds(array $ids) {
    ScheduledTask::rawExecute(
      'UPDATE `' . ScheduledTask::$_table . '`' .
      'SET `updated_at` = NOW() ' .
      'WHERE `id` IN (' . join(',', $ids) . ')'
    );
  }

  /**
   * @return ScheduledTask|null
   */
  static function findOneScheduledByNewsletterIdAndSubscriberId($newsletter_id, $subscriber_id) {
    return ScheduledTask::tableAlias('tasks')
      ->select('tasks.*')
      ->innerJoin(SendingQueue::$_table, 'queues.task_id = tasks.id', 'queues')
      ->innerJoin(ScheduledTaskSubscriber::$_table, 'task_subscribers.task_id = tasks.id', 'task_subscribers')
      ->where('queues.newsletter_id', $newsletter_id)
      ->where('tasks.status', ScheduledTask::STATUS_SCHEDULED)
      ->where('task_subscribers.subscriber_id', $subscriber_id)
      ->whereNull('queues.deleted_at')
      ->whereNull('tasks.deleted_at')
      ->findOne() ?: null;
  }
}
