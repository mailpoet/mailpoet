<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\Listing;
use MailPoet\Cron\CronHelper;
use MailPoet\Config\AccessControl;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\API\JSON\Endpoint as APIEndpoint;

if (!defined('ABSPATH')) exit;

class SendingTaskSubscribers extends APIEndpoint {
  public $permissions = array(
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS
  );

  /** @var Listing\Handler */
  private $listing_handler;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    Listing\Handler $listing_handler,
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->listing_handler = $listing_handler;
    $this->settings = $settings;
    $this->wp = $wp;
  }

  public function listing($data = array()) {
    $newsletter_id = !empty($data['params']['id']) ? (int)$data['params']['id'] : false;
    $tasks_ids = SendingQueue::select('task_id')
      ->where('newsletter_id', $newsletter_id)
      ->findArray();
    if (empty($tasks_ids)) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('This newsletter is not being sent to any subcriber yet.', 'mailpoet')
      ));
    }
    $data['params']['task_ids'] = array_map(function($item) {
      return $item['task_id'];
    }, $tasks_ids);
    $listing_data = $this->listing_handler->get('\MailPoet\Models\ScheduledTaskSubscriber', $data);

    $items = [];
    foreach ($listing_data['items'] as $item) {
      $items[] = $item->asArray();
    }

    return $this->successResponse($items, array(
      'count' => $listing_data['count'],
      'filters' => $listing_data['filters'],
      'groups' => $listing_data['groups'],
      'mta_log' => $this->settings->get('mta_log'),
      'mta_method' => $this->settings->get('mta.method'),
      'cron_accessible' => CronHelper::isDaemonAccessible(),
      'current_time' => $this->wp->currentTime('mysql')
    ));
  }
}
