<?php
namespace MailPoet\Models;

use MailPoet\Util\Helpers;
use MailPoet\WP\Emoji;
use MailPoet\Tasks\Subscribers as TaskSubscribers;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * @property int $count_processed
 * @property int $count_to_process
 * @property int $count_total
 * @property string $newsletter_rendered_body
 * @property string $newsletter_rendered_subject
 * @property int $task_id
 * @property int $newsletter_id
 * @property string|object|null $meta
 * @property string|array $subscribers
 * @property string|null $deleted_at
 */

class SendingQueue extends Model {
  public static $_table = MP_SENDING_QUEUES_TABLE;
  const STATUS_COMPLETED = 'completed';
  const STATUS_SCHEDULED = 'scheduled';
  const STATUS_PAUSED = 'paused';
  const PRIORITY_HIGH = 1;
  const PRIORITY_MEDIUM = 5;
  const PRIORITY_LOW = 10;

  private $emoji;

  function __construct() {
    parent::__construct();

    $this->addValidations('newsletter_rendered_body', [
      'validRenderedNewsletterBody' => WPFunctions::get()->__('Rendered newsletter body is invalid!', 'mailpoet'),
    ]);
    $this->emoji = new Emoji();
  }

  function task() {
    return $this->hasOne(__NAMESPACE__ . '\ScheduledTask', 'id', 'task_id');
  }

  function newsletter() {
    return $this->has_one(__NAMESPACE__ . '\Newsletter', 'id', 'newsletter_id');
  }

  function pause() {
    if ($this->count_processed === $this->count_total) {
      return false;
    } else {
      return $this->task()->findOne()->pause();
    }
  }

  function resume() {
    if ($this->count_processed === $this->count_total) {
      return $this->complete();
    } else {
      return $this->task()->findOne()->resume();
    }
  }

  function complete() {
    return $this->task()->findOne()->complete();
  }

  function save() {
    $this->newsletter_rendered_body = $this->getNewsletterRenderedBody();
    if (!Helpers::isJson($this->newsletter_rendered_body) && !is_null($this->newsletter_rendered_body)) {
      $this->set(
        'newsletter_rendered_body',
        json_encode($this->encodeEmojisInBody($this->newsletter_rendered_body))
      );
    }
    if (!is_null($this->meta) && !Helpers::isJson($this->meta)) {
      $this->set(
        'meta',
        json_encode($this->meta)
      );
    }
    parent::save();
    $this->newsletter_rendered_body = $this->getNewsletterRenderedBody();
    return $this;
  }

  /**
   * Used only for checking processed subscribers in old queues
   */
  private function getSubscribers() {
    if (!is_serialized($this->subscribers)) {
      return $this->subscribers;
    }
    $subscribers = unserialize($this->subscribers);
    if (empty($subscribers['processed'])) {
      $subscribers['processed'] = [];
    }
    return $subscribers;
  }

  function getNewsletterRenderedBody($type = false) {
    $rendered_newsletter = $this->decodeRenderedNewsletterBodyObject($this->newsletter_rendered_body);
    return ($type && !empty($rendered_newsletter[$type])) ?
      $rendered_newsletter[$type] :
      $rendered_newsletter;
  }

  function getMeta() {
    return (Helpers::isJson($this->meta)) ? json_decode($this->meta, true) : $this->meta;
  }

  function encodeEmojisInBody($newsletter_rendered_body) {
    if (is_array($newsletter_rendered_body)) {
      foreach ($newsletter_rendered_body as $key => $value) {
        $newsletter_rendered_body[$key] = $this->emoji->encodeForUTF8Column(
          self::$_table,
          'newsletter_rendered_body',
          $value
        );
      }
    }
    return $newsletter_rendered_body;
  }

  function decodeEmojisInBody($newsletter_rendered_body) {
    if (is_array($newsletter_rendered_body)) {
      foreach ($newsletter_rendered_body as $key => $value) {
        $newsletter_rendered_body[$key] = $this->emoji->decodeEntities($value);
      }
    }
    return $newsletter_rendered_body;
  }

  function isSubscriberProcessed($subscriber_id) {
    if (!empty($this->subscribers)
      && ScheduledTaskSubscriber::getTotalCount($this->task_id) === 0
    ) {
      $subscribers = $this->getSubscribers();
      return in_array($subscriber_id, $subscribers['processed']);
    } else {
      $task = $this->task()->findOne();
      if ($task) {
        $task_subscribers = new TaskSubscribers($task);
        return $task_subscribers->isSubscriberProcessed($subscriber_id);
      }
      return false;
    }
  }

  function asArray() {
    $model = parent::asArray();
    $model['newsletter_rendered_body'] = $this->getNewsletterRenderedBody();
    $model['meta'] = $this->getMeta();
    return $model;
  }

  private function decodeRenderedNewsletterBodyObject($rendered_body) {
    if (is_serialized($rendered_body)) {
      return $this->decodeEmojisInBody(unserialize($rendered_body));
    }
    if (Helpers::isJson($rendered_body)) {
      return $this->decodeEmojisInBody(json_decode($rendered_body, true));
    }
    return $rendered_body;
  }

  static function getTasks() {
    return ScheduledTask::tableAlias('tasks')
    ->selectExpr('tasks.*')
    ->join(
      MP_SENDING_QUEUES_TABLE,
      'tasks.id = queues.task_id',
      'queues'
    );
  }

  static function joinWithTasks() {
    return static::tableAlias('queues')
    ->join(
      MP_SCHEDULED_TASKS_TABLE,
      'tasks.id = queues.task_id',
      'tasks'
    );
  }

  static function joinWithSubscribers() {
    return static::joinWithTasks()
    ->join(
      MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE,
      'tasks.id = subscribers.task_id',
      'subscribers'
    );
  }

  static function findTaskByNewsletterId($newsletter_id) {
    return static::getTasks()
    ->where('queues.newsletter_id', $newsletter_id);
  }
}
