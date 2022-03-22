<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\ResponseBuilders\ScheduledTaskSubscriberResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\CronHelper;
use MailPoet\Listing;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersListingRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class SendingTaskSubscribers extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  /** @var Listing\Handler */
  private $listingHandler;

  /** @var SettingsController */
  private $settings;

  /** @var CronHelper */
  private $cronHelper;

  /** @var WPFunctions */
  private $wp;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var ScheduledTaskSubscribersListingRepository */
  private $taskSubscribersListingRepository;

  /** @var ScheduledTaskSubscriberResponseBuilder */
  private $scheduledTaskSubscriberResponseBuilder;

  public function __construct(
    Listing\Handler $listingHandler,
    SettingsController $settings,
    CronHelper $cronHelper,
    SendingQueuesRepository $sendingQueuesRepository,
    ScheduledTaskSubscribersListingRepository $taskSubscribersListingRepository,
    ScheduledTaskSubscriberResponseBuilder $scheduledTaskSubscriberResponseBuilder,
    WPFunctions $wp
  ) {
    $this->listingHandler = $listingHandler;
    $this->settings = $settings;
    $this->cronHelper = $cronHelper;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->taskSubscribersListingRepository = $taskSubscribersListingRepository;
    $this->scheduledTaskSubscriberResponseBuilder = $scheduledTaskSubscriberResponseBuilder;
    $this->wp = $wp;
  }

  public function listing($data = []) {
    $newsletterId = !empty($data['params']['id']) ? (int)$data['params']['id'] : false;
    if (empty($newsletterId)) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('Newsletter not found!', 'mailpoet'),
      ]);
    }
    $tasksIds = $this->sendingQueuesRepository->getTaskIdsByNewsletterId($newsletterId);

    if (empty($tasksIds)) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email has not been sent yet.', 'mailpoet'),
      ]);
    }
    $data['params']['task_ids'] = $tasksIds;
    $definition = $this->listingHandler->getListingDefinition($data);
    $items = $this->taskSubscribersListingRepository->getData($definition);
    $groups = $this->taskSubscribersListingRepository->getGroups($definition);
    $filters = $this->taskSubscribersListingRepository->getFilters($definition);
    $count = $this->taskSubscribersListingRepository->getCount($definition);

    return $this->successResponse($this->scheduledTaskSubscriberResponseBuilder->buildForListing($items), [
      'count' => $count,
      'filters' => $filters,
      'groups' => $groups,
      'mta_log' => $this->settings->get('mta_log'),
      'mta_method' => $this->settings->get('mta.method'),
      'cron_accessible' => $this->cronHelper->isDaemonAccessible(),
      'current_time' => $this->wp->currentTime('mysql'),
    ]);
  }

  public function resend($data = []) {
    $taskId = !empty($data['taskId']) ? (int)$data['taskId'] : false;
    $subscriberId = !empty($data['subscriberId']) ? (int)$data['subscriberId'] : false;
    $taskSubscriber = ScheduledTaskSubscriber::where('task_id', $taskId)
      ->where('subscriber_id', $subscriberId)
      ->findOne();
    $task = ScheduledTask::findOne($taskId);
    $sendingQueue = SendingQueueModel::where('task_id', $taskId)->findOne();
    if (
      !($task instanceof ScheduledTask)
      || !($taskSubscriber instanceof ScheduledTaskSubscriber)
      || !($sendingQueue instanceof SendingQueueModel)
      || $taskSubscriber->failed != 1
    ) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('Failed sending task not found!', 'mailpoet'),
      ]);
    }
    $newsletter = Newsletter::findOne($sendingQueue->newsletterId);
    if (!($newsletter instanceof Newsletter)) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('Newsletter not found!', 'mailpoet'),
      ]);
    }

    $taskSubscriber->error = '';
    $taskSubscriber->failed = 0;
    $taskSubscriber->processed = 0;
    $taskSubscriber->save();

    $task->status = null;
    $task->save();

    $newsletter->status = Newsletter::STATUS_SENDING;
    $newsletter->save();

    return $this->successResponse([]);
  }
}
