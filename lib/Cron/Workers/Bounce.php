<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Mailer\Mailer;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Bounce as BounceTask;
use MailPoet\Tasks\Subscribers as TaskSubscribers;
use MailPoet\Tasks\Subscribers\BatchIterator;

class Bounce extends SimpleWorker {
  const TASK_TYPE = 'bounce';
  const BATCH_SIZE = 100;

  const BOUNCED_HARD = 'hard';
  const BOUNCED_SOFT = 'soft';
  const NOT_BOUNCED = null;

  public $api;

  /** @var SettingsController */
  private $settings;

  public function __construct(SettingsController $settings) {
    $this->settings = $settings;
    parent::__construct();
  }

  public function init() {
    if (!$this->api) {
      $this->api = new API($this->settings->get(Mailer::MAILER_CONFIG_SETTING_NAME)['mailpoet_api_key']);
    }
  }

  public function checkProcessingRequirements() {
    return Bridge::isMPSendingServiceEnabled();
  }

  public function prepareTaskStrategy(ScheduledTask $task, $timer) {
    BounceTask::prepareSubscribers($task);

    if (!ScheduledTaskSubscriber::getUnprocessedCount($task->id)) {
      ScheduledTaskSubscriber::where('task_id', $task->id)->deleteMany();
      return false;
    }
    return true;
  }

  public function processTaskStrategy(ScheduledTask $task, $timer) {
    $subscriberBatches = new BatchIterator($task->id, self::BATCH_SIZE);

    if (count($subscriberBatches) === 0) {
      ScheduledTaskSubscriber::where('task_id', $task->id)->deleteMany();
      return true; // mark completed
    }

    $taskSubscribers = new TaskSubscribers($task);

    foreach ($subscriberBatches as $subscribersToProcessIds) {
      // abort if execution limit is reached
      $this->cronHelper->enforceExecutionLimit($timer);

      $subscriberEmails = Subscriber::select('email')
        ->whereIn('id', $subscribersToProcessIds)
        ->whereNull('deleted_at')
        ->findArray();
      $subscriberEmails = array_column($subscriberEmails, 'email');

      $this->processEmails($subscriberEmails);

      $taskSubscribers->updateProcessedSubscribers($subscribersToProcessIds);
    }

    return true;
  }

  public function processEmails(array $subscriberEmails) {
    $checkedEmails = $this->api->checkBounces($subscriberEmails);
    $this->processApiResponse((array)$checkedEmails);
  }

  public function processApiResponse(array $checkedEmails) {
    foreach ($checkedEmails as $email) {
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
