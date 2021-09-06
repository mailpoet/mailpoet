<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Bounce as BounceTask;
use MailPoet\Tasks\Subscribers as TaskSubscribers;
use MailPoet\Tasks\Subscribers\BatchIterator;
use MailPoetVendor\Carbon\Carbon;

class Bounce extends SimpleWorker {
  const TASK_TYPE = 'bounce';
  const BATCH_SIZE = 100;

  const BOUNCED_HARD = 'hard';
  const BOUNCED_SOFT = 'soft';
  const NOT_BOUNCED = null;

  public $api;

  /** @var SettingsController */
  private $settings;

  /** @var Bridge */
  private $bridge;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    SettingsController $settings,
    SubscribersRepository $subscribersRepository,
    Bridge $bridge
  ) {
    $this->settings = $settings;
    $this->bridge = $bridge;
    parent::__construct();
    $this->subscribersRepository = $subscribersRepository;
  }

  public function init() {
    if (!$this->api) {
      $this->api = new API($this->settings->get(Mailer::MAILER_CONFIG_SETTING_NAME)['mailpoet_api_key']);
    }
  }

  public function checkProcessingRequirements() {
    return $this->bridge->isMailpoetSendingServiceEnabled();
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

      $subscriberEmails = $this->subscribersRepository->getUndeletedSubscribersEmailsByIds($subscribersToProcessIds);
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
        $subscriber = $this->subscribersRepository->findOneBy(['email' => $email['address']]);
        if (!$subscriber instanceof SubscriberEntity) continue;
        $subscriber->setStatus(SubscriberEntity::STATUS_BOUNCED);
      }
    }
    $this->subscribersRepository->flush();
  }

  public function getNextRunDate() {
    $date = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
    return $date->startOfDay()
      ->addDay()
      ->addHours(rand(0, 5))
      ->addMinutes(rand(0, 59))
      ->addSeconds(rand(0, 59));
  }
}
