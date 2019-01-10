<?php

namespace MailPoet\Models;

use MailPoet\WP\Functions as WPFunctions;

if(!defined('ABSPATH')) exit;

class ScheduledTask extends Model {
  public static $_table = MP_SCHEDULED_TASKS_TABLE;
  const STATUS_COMPLETED = 'completed';
  const STATUS_SCHEDULED = 'scheduled';
  const STATUS_PAUSED = 'paused';
  const VIRTUAL_STATUS_RUNNING = 'running'; // For historical reasons this is stored as null in DB
  const PRIORITY_HIGH = 1;
  const PRIORITY_MEDIUM = 5;
  const PRIORITY_LOW = 10;

  private $wp;

  function __construct() {
    parent::__construct();
    $this->wp = new WPFunctions();
  }

  function subscribers() {
    return $this->hasManyThrough(
      __NAMESPACE__.'\Subscriber',
      __NAMESPACE__.'\ScheduledTaskSubscriber',
      'task_id',
      'subscriber_id'
    );
  }

  /** @return StatsNotification */
  function statsNotification() {
    return $this->hasOne(
      StatsNotification::class,
      'task_id',
      'id'
    );
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
    if(!$this->priority) {
      $this->priority = self::PRIORITY_MEDIUM;
    }
    parent::save();
    return $this;
  }

  function delete() {
    try {
      \ORM::get_db()->beginTransaction();
      ScheduledTaskSubscriber::where('task_id', $this->id)->deleteMany();
      parent::delete();
      \ORM::get_db()->commit();
    } catch(\Exception $error) {
      \ORM::get_db()->rollBack();
      throw $error;
    }
  }

  static function touchAllByIds(array $ids) {
    ScheduledTask::rawExecute(
      'UPDATE `' . ScheduledTask::$_table . '`' .
      'SET `updated_at` = NOW() ' .
      'WHERE `id` IN (' . join(',', $ids) . ')'
    );
  }
}
