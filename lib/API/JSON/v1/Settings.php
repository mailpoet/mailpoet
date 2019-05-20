<?php

namespace MailPoet\API\JSON\v1;

use Carbon\Carbon;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Models\ScheduledTask;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Models\Form;

if (!defined('ABSPATH')) exit;

class Settings extends APIEndpoint {

  /** @var SettingsController */
  private $settings;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
  ];

  function __construct(SettingsController $settings) {
    $this->settings = $settings;
  }

  function get() {
    return $this->successResponse($this->settings->getAll());
  }

  function set($settings = []) {
    if (empty($settings)) {
      return $this->badRequest(
        [
          APIError::BAD_REQUEST =>
            WPFunctions::get()->__('You have not specified any settings to be saved.', 'mailpoet'),
        ]);
    } else {
      $original_inactivation_interval = (int)$this->settings->get('deactivate_subscriber_after_inactive_days');
      // Will be uncommented on task [MAILPOET-1998]
      // $signup_confirmation = $this->settings->get('signup_confirmation.enabled');
      foreach ($settings as $name => $value) {
        $this->settings->set($name, $value);
      }
      if (isset($settings['deactivate_subscriber_after_inactive_days'])
        && $original_inactivation_interval !== (int)$settings['deactivate_subscriber_after_inactive_days']
      ) {
        $this->onInactiveSubscribersIntervalChange();
      }
      $bridge = new Bridge();
      $bridge->onSettingsSave($settings);
      // Will be uncommented on task [MAILPOET-1998]
      // if ($signup_confirmation !== $this->settings->get('signup_confirmation.enabled')) {
      //   Form::updateSuccessMessages();
      // }
      return $this->successResponse($this->settings->getAll());
    }
  }

  private function onInactiveSubscribersIntervalChange() {
    $task = ScheduledTask::where('type', InactiveSubscribers::TASK_TYPE)
      ->whereRaw('status = ?', [ScheduledTask::STATUS_SCHEDULED])
      ->findOne();
    if (!$task) {
      $task = ScheduledTask::create();
      $task->type = InactiveSubscribers::TASK_TYPE;
      $task->status = ScheduledTask::STATUS_SCHEDULED;
    }
    $datetime = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $task->scheduled_at = $datetime->subMinute();
    $task->save();
  }
}
