<?php
namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\ScheduledTask;
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
    $subscribers = Subscriber::select('id')
      ->whereNull('deleted_at')
      ->whereIn('status', array(
        Subscriber::STATUS_SUBSCRIBED,
        Subscriber::STATUS_UNCONFIRMED
      ))
      ->findArray();
    $subscribers = Helpers::arrayColumn($subscribers, 'id');

    if(empty($subscribers)) {
      $task->delete();
      return false;
    }

    // update current task
    $task->subscribers = serialize(
      array(
        'to_process' => $subscribers
      )
    );
    $task->count_total = $task->count_to_process = count($subscribers);

    return parent::prepareTask($task);
  }

  function processTask(ScheduledTask $task) {
    $task->subscribers = $task->getSubscribers();
    if(empty($task->subscribers['to_process'])) {
      $task->delete();
      return false;
    }

    $subscriber_batches = array_chunk(
      $task->subscribers['to_process'],
      self::BATCH_SIZE
    );

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
