<?php
namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\SendingQueue;
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

  function prepareQueue(SendingQueue $queue) {
    $subscribers = Subscriber::select('id')
      ->whereNull('deleted_at')
      ->whereIn('status', array(
        Subscriber::STATUS_SUBSCRIBED,
        Subscriber::STATUS_UNCONFIRMED
      ))
      ->findArray();
    $subscribers = Helpers::arrayColumn($subscribers, 'id');

    if(empty($subscribers)) {
      $queue->delete();
      return false;
    }

    // update current queue
    $queue->subscribers = serialize(
      array(
        'to_process' => $subscribers
      )
    );
    $queue->count_total = $queue->count_to_process = count($subscribers);

    return parent::prepareQueue($queue);
  }

  function processQueue(SendingQueue $queue) {
    $queue->subscribers = $queue->getSubscribers();
    if(empty($queue->subscribers['to_process'])) {
      $queue->delete();
      return false;
    }

    $subscriber_batches = array_chunk(
      $queue->subscribers['to_process'],
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

      $queue->updateProcessedSubscribers($subscribers_to_process_ids);
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
