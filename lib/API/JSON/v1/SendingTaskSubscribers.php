<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\Listing;
use MailPoet\Cron\CronHelper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Config\AccessControl;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Models\SendingQueue as SendingQueueModel;

if (!defined('ABSPATH')) exit;

class SendingTaskSubscribers extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  /** @var Listing\Handler */
  private $listing_handler;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  function __construct(
    Listing\Handler $listing_handler,
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->listing_handler = $listing_handler;
    $this->settings = $settings;
    $this->wp = $wp;
  }

  function listing($data = []) {
    $newsletter_id = !empty($data['params']['id']) ? (int)$data['params']['id'] : false;
    $tasks_ids = SendingQueueModel::select('task_id')
      ->where('newsletter_id', $newsletter_id)
      ->findArray();
    if (empty($tasks_ids)) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email has not been sent yet.', 'mailpoet'),
      ]);
    }
    $data['params']['task_ids'] = array_column($tasks_ids, 'task_id');
    $listing_data = $this->listing_handler->get('\MailPoet\Models\ScheduledTaskSubscriber', $data);

    $items = [];
    foreach ($listing_data['items'] as $item) {
      $items[] = $item->asArray();
    }

    return $this->successResponse($items, [
      'count' => $listing_data['count'],
      'filters' => $listing_data['filters'],
      'groups' => $listing_data['groups'],
      'mta_log' => $this->settings->get('mta_log'),
      'mta_method' => $this->settings->get('mta.method'),
      'cron_accessible' => CronHelper::isDaemonAccessible(),
      'current_time' => $this->wp->currentTime('mysql'),
    ]);
  }

  function resend($data = []) {
    $task_id = !empty($data['taskId']) ? (int)$data['taskId'] : false;
    $subscriber_id = !empty($data['subscriberId']) ? (int)$data['subscriberId'] : false;
    $task_subscriber = ScheduledTaskSubscriber::where('task_id', $task_id)
      ->where('subscriber_id', $subscriber_id)
      ->findOne();
    $task = ScheduledTask::findOne($task_id);
    $sending_queue = SendingQueueModel::where('task_id', $task_id)->findOne();
    if (!$task || !$task_subscriber || !$sending_queue || $task_subscriber->failed != 1) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('Failed sending task not found!', 'mailpoet'),
      ]);
    }
    $newsletter = Newsletter::findOne($sending_queue->newsletter_id);

    $task_subscriber->error = '';
    $task_subscriber->failed = 0;
    $task_subscriber->processed = 0;
    $task_subscriber->save();

    $task->status = null;
    $task->save();

    $newsletter->status = Newsletter::STATUS_SENDING;
    $newsletter->save();

    return $this->successResponse([]);
  }
}
