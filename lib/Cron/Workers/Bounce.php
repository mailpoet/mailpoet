<?php
namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Tasks\Subscribers\BatchIterator;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Util\Helpers;

if(!defined('ABSPATH')) exit;

class Bounce extends SimpleWorker {
  const TASK_TYPE = 'bounce';
  const BATCH_SIZE = 100;

  const BOUNCED_HARD = 'hard';
  const BOUNCED_SOFT = 'soft';
  const NOT_BOUNCED = null;

  public $api;

  function init() {
    if(!$this->api) {
      $mailer_config = Mailer::getMailerConfig();
      $this->api = new API($mailer_config['mailpoet_api_key']);
    }
  }

  function checkProcessingRequirements() {
    return Bridge::isMPSendingServiceEnabled();
  }

  function prepareTask(ScheduledTask $task) {
    // Prepare subscribers on the DB side for performance reasons
    Subscriber::rawExecute(
      'INSERT INTO ' . MP_SCHEDULED_TASK_SUBSCRIBERS_TABLE . '
       (task_id, subscriber_id, processed)
       SELECT ? as task_id, s.`id` as subscriber_id, ? as processed
       FROM ' . MP_SUBSCRIBERS_TABLE . ' s
       WHERE s.`deleted_at` IS NULL
       AND s.`status` IN (?, ?)',
      array(
        $task->id,
        ScheduledTaskSubscriber::STATUS_TO_PROCESS,
        Subscriber::STATUS_SUBSCRIBED,
        Subscriber::STATUS_UNCONFIRMED
      )
    );

    if(!ScheduledTaskSubscriber::getToProcessCount($task->id)) {
      $task->delete();
      return false;
    }

    return parent::prepareTask($task);
  }

  function processTask(ScheduledTask $task) {
    $subscriber_batches = new BatchIterator($task->id, self::BATCH_SIZE);

    if(count($subscriber_batches) === 0) {
      $task->delete();
      return false;
    }

    foreach($subscriber_batches as $subscribers_to_process_ids) {
      // abort if execution limit is reached
      CronHelper::enforceExecutionLimit($this->timer);

      $subscriber_emails = Subscriber::select('email')
        ->whereIn('id', $subscribers_to_process_ids)
        ->whereNull('deleted_at')
        ->findArray();
      $subscriber_emails = Helpers::arrayColumn($subscriber_emails, 'email');

      $this->processEmails($subscriber_emails);

      $task->updateProcessedSubscribers($subscribers_to_process_ids);
    }

    return true;
  }

  function processEmails(array $subscriber_emails) {
    $checked_emails = $this->api->checkBounces($subscriber_emails);
    $this->processApiResponse((array)$checked_emails);
  }

  function processApiResponse(array $checked_emails) {
    foreach($checked_emails as $email) {
      if(!isset($email['address'], $email['bounce'])) {
        continue;
      }
      if($email['bounce'] === self::BOUNCED_HARD) {
        $subscriber = Subscriber::findOne($email['address']);
        $subscriber->status = Subscriber::STATUS_BOUNCED;
        $subscriber->save();
      }
    }
  }
}
