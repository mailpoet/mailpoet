<?php
namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Tasks\Bounce as BounceTask;
use MailPoet\Tasks\Subscribers as TaskSubscribers;
use MailPoet\Tasks\Subscribers\BatchIterator;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use function MailPoet\Util\array_column;

if (!defined('ABSPATH')) exit;

class Bounce extends SimpleWorker {
  const TASK_TYPE = 'bounce';
  const BATCH_SIZE = 100;

  const BOUNCED_HARD = 'hard';
  const BOUNCED_SOFT = 'soft';
  const NOT_BOUNCED = null;

  public $api;

  function init() {
    if (!$this->api) {
      $mailer_config = Mailer::getMailerConfig();
      $this->api = new API($mailer_config['mailpoet_api_key']);
    }
  }

  function checkProcessingRequirements() {
    return Bridge::isMPSendingServiceEnabled();
  }

  function prepareTask(ScheduledTask $task) {
    BounceTask::prepareSubscribers($task);

    if (!ScheduledTaskSubscriber::getUnprocessedCount($task->id)) {
      $task->delete();
      return false;
    }

    return parent::prepareTask($task);
  }

  function processTask(ScheduledTask $task) {
    $subscriber_batches = new BatchIterator($task->id, self::BATCH_SIZE);

    if (count($subscriber_batches) === 0) {
      $task->delete();
      return false;
    }

    $task_subscribers = new TaskSubscribers($task);

    foreach ($subscriber_batches as $subscribers_to_process_ids) {
      // abort if execution limit is reached
      CronHelper::enforceExecutionLimit($this->timer);

      $subscriber_emails = Subscriber::select('email')
        ->whereIn('id', $subscribers_to_process_ids)
        ->whereNull('deleted_at')
        ->findArray();
      $subscriber_emails = array_column($subscriber_emails, 'email');

      $this->processEmails($subscriber_emails);

      $task_subscribers->updateProcessedSubscribers($subscribers_to_process_ids);
    }

    return true;
  }

  function processEmails(array $subscriber_emails) {
    $checked_emails = $this->api->checkBounces($subscriber_emails);
    $this->processApiResponse((array)$checked_emails);
  }

  function processApiResponse(array $checked_emails) {
    foreach ($checked_emails as $email) {
      if (!isset($email['address'], $email['bounce'])) {
        continue;
      }
      if ($email['bounce'] === self::BOUNCED_HARD) {
        $subscriber = Subscriber::findOne($email['address']);
        $subscriber->status = Subscriber::STATUS_BOUNCED;
        $subscriber->save();
      }
    }
  }
}
