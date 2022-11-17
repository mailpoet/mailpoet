<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Models;

use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Util\Helpers;

/**
 * @property int $countProcessed
 * @property int $countToProcess
 * @property int $countTotal
 * @property string|array $newsletterRenderedBody
 * @property string $newsletterRenderedSubject
 * @property int $taskId
 * @property int $newsletterId
 * @property string|object|array|null $meta
 * @property string|array $subscribers
 * @property string|null $deletedAt
 * @property string $scheduledAt
 * @property string $status
 */

class SendingQueue extends Model {
  public static $_table = MP_SENDING_QUEUES_TABLE; // phpcs:ignore PSR2.Classes.PropertyDeclaration
  const STATUS_COMPLETED = SendingQueueEntity::STATUS_COMPLETED;
  const STATUS_SCHEDULED = SendingQueueEntity::STATUS_SCHEDULED;
  const STATUS_PAUSED = SendingQueueEntity::STATUS_PAUSED;
  const PRIORITY_HIGH = SendingQueueEntity::PRIORITY_HIGH;
  const PRIORITY_MEDIUM = SendingQueueEntity::PRIORITY_MEDIUM;
  const PRIORITY_LOW = SendingQueueEntity::PRIORITY_LOW;

  public function __construct() {
    parent::__construct();

    $this->addValidations('newsletter_rendered_body', [
      'validRenderedNewsletterBody' => __('Rendered newsletter body is invalid!', 'mailpoet'),
    ]);
  }

  public function task() {
    return $this->hasOne(__NAMESPACE__ . '\ScheduledTask', 'id', 'task_id');
  }

  public function newsletter() {
    return $this->has_one(__NAMESPACE__ . '\Newsletter', 'id', 'newsletter_id');
  }

  public function pause() {
    if ($this->countProcessed === $this->countTotal) {
      return false;
    } else {
      return $this->task()->findOne()->pause();
    }
  }

  public function resume() {
    if ($this->countProcessed === $this->countTotal) {
      return $this->complete();
    } else {
      $this->newsletter()->findOne()->setStatus(Newsletter::STATUS_SENDING);
      return $this->task()->findOne()->resume();
    }
  }

  public function complete() {
    return $this->task()->findOne()->complete();
  }

  public function save() {
    $this->newsletterRenderedBody = $this->getNewsletterRenderedBody();
    if (!Helpers::isJson($this->newsletterRenderedBody) && !is_null($this->newsletterRenderedBody)) {
      $this->set(
        'newsletter_rendered_body',
        (string)json_encode($this->newsletterRenderedBody)
      );
    }
    if (!is_null($this->meta) && !Helpers::isJson($this->meta)) {
      $this->set(
        'meta',
        (string)json_encode($this->meta)
      );
    }
    parent::save();
    $this->newsletterRenderedBody = $this->getNewsletterRenderedBody();
    return $this;
  }

  public function getNewsletterRenderedBody($type = false) {
    $renderedNewsletter = $this->decodeRenderedNewsletterBodyObject($this->newsletterRenderedBody);
    return ($type && !empty($renderedNewsletter[$type])) ?
      $renderedNewsletter[$type] :
      $renderedNewsletter;
  }

  public function getMeta() {
    return (Helpers::isJson($this->meta) && is_string($this->meta)) ? json_decode($this->meta, true) : $this->meta;
  }

  public function asArray() {
    $model = parent::asArray();
    $model['newsletter_rendered_body'] = $this->getNewsletterRenderedBody();
    $model['meta'] = $this->getMeta();
    return $model;
  }

  private function decodeRenderedNewsletterBodyObject($renderedBody) {
    if (is_serialized($renderedBody)) {
      return unserialize($renderedBody);
    }
    if (Helpers::isJson($renderedBody)) {
      return json_decode($renderedBody, true);
    }
    return $renderedBody;
  }

  public static function getTasks() {
    return ScheduledTask::tableAlias('tasks')
    ->selectExpr('tasks.*')
    ->join(
      MP_SENDING_QUEUES_TABLE,
      'tasks.id = queues.task_id',
      'queues'
    );
  }

  public static function joinWithTasks() {
    return static::tableAlias('queues')
    ->join(
      MP_SCHEDULED_TASKS_TABLE,
      'tasks.id = queues.task_id',
      'tasks'
    );
  }

  public static function joinWithSubscribers() {
    return static::joinWithTasks()
    ->join(
      MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE,
      'tasks.id = subscribers.task_id',
      'subscribers'
    );
  }

  public static function findTaskByNewsletterId($newsletterId) {
    return static::getTasks()
    ->where('queues.newsletter_id', $newsletterId);
  }
}
