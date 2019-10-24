<?php

namespace MailPoet\Test\API\JSON\v1;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Settings;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Setting;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class SettingsTest extends \MailPoetTest {

  /** @var Settings */
  private $endpoint;

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    $this->settings = SettingsController::getInstance();
    $this->settings->set('some.setting.key', true);
    $this->endpoint = new Settings(
      $this->settings,
      new Bridge,
      $this->make(AuthorizedEmailsController::class, ['onSettingsSave' => true ])
    );
  }

  function testItCanGetSettings() {
    $response = $this->endpoint->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($response->data)->notEmpty();
    expect($response->data['some']['setting']['key'])->true();

    Setting::deleteMany();
    SettingsController::resetCache();
    $response = $this->endpoint->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals($this->settings->getAllDefaults());
  }

  function testItCanSetSettings() {
    $new_settings = [
      'some' => [
        'setting' => [
          'new_key' => true,
        ],
        'new_setting' => true,
      ],
    ];

    $this->endpoint = new Settings(
      $this->settings,
      $this->make(Bridge::class, ['onSettingsSave' => Expected::once()]),
      $this->make(AuthorizedEmailsController::class, ['onSettingsSave' => Expected::once()])
    );

    $response = $this->endpoint->set(/* missing data */);
    expect($response->errors[0]['error'])->equals(APIError::BAD_REQUEST);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    $response = $this->endpoint->set($new_settings);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $response = $this->endpoint->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['some']['setting'])->hasntKey('key');
    expect($response->data['some']['setting']['new_key'])->true();
    expect($response->data['some']['new_setting'])->true();
  }

  function testItSchedulesInactiveSubscribersCheckIfIntervalSettingChanges() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 30);
    $settings = ['deactivate_subscriber_after_inactive_days' => 30];
    $this->endpoint->set($settings);
    $task = ScheduledTask::where('type', InactiveSubscribers::TASK_TYPE)
      ->whereRaw('status = ?', [ScheduledTask::STATUS_SCHEDULED])
      ->findOne();
    expect($task)->false();

    $settings = ['deactivate_subscriber_after_inactive_days' => 0];
    $this->endpoint->set($settings);
    $task = ScheduledTask::where('type', InactiveSubscribers::TASK_TYPE)
      ->whereRaw('status = ?', [ScheduledTask::STATUS_SCHEDULED])
      ->findOne();
    expect($task->scheduled_at)->lessThan(Carbon::now());
  }

  function _after() {
    \ORM::forTable(Setting::$_table)->deleteMany();
  }
}
