<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub\Expected;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Settings;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\ScheduledTask;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class SettingsTest extends \MailPoetTest {

  /** @var Settings */
  private $endpoint;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    $this->settings = SettingsController::getInstance();
    $this->settings->set('some.setting.key', true);
    $this->endpoint = new Settings(
      $this->settings,
      new Bridge,
      $this->make(AuthorizedEmailsController::class, ['onSettingsSave' => true ]),
      $this->make(TransactionalEmails::class),
      $this->make(ServicesChecker::class, ['isMailPoetAPIKeyPendingApproval' => false])
    );
  }

  public function testItCanGetSettings() {
    $response = $this->endpoint->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($response->data)->notEmpty();
    expect($response->data['some']['setting']['key'])->true();

    $this->diContainer->get(SettingsRepository::class)->truncate();
    $this->settings->resetCache();
    $response = $this->endpoint->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals($this->settings->getAllDefaults());
  }

  public function testItCanSetSettings() {
    $newSettings = [
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
      $this->make(AuthorizedEmailsController::class, ['onSettingsSave' => Expected::once()]),
      $this->make(TransactionalEmails::class),
      $this->make(ServicesChecker::class, ['isMailPoetAPIKeyPendingApproval' => false])
    );

    $response = $this->endpoint->set(/* missing data */);
    expect($response->errors[0]['error'])->equals(APIError::BAD_REQUEST);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    $response = $this->endpoint->set($newSettings);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $response = $this->endpoint->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['some']['setting'])->hasntKey('key');
    expect($response->data['some']['setting']['new_key'])->true();
    expect($response->data['some']['new_setting'])->true();
  }

  public function testItSetsAuthorizedFromAddressAndResumesSending() {
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once(['authorized@email.com'])]);
    $this->endpoint = new Settings(
      $this->settings,
      $bridgeMock,
      new AuthorizedEmailsController($this->settings, $bridgeMock, $this->diContainer->get(NewslettersRepository::class)),
      $this->make(TransactionalEmails::class),
      $this->make(ServicesChecker::class, ['isMailPoetAPIKeyPendingApproval' => false])
    );

    MailerLog::pauseSending(MailerLog::getMailerLog());
    $this->settings->set('sender.address', '');
    $response = $this->endpoint->setAuthorizedFromAddress(['address' => 'authorized@email.com']);
    expect($response->status)->same(200);
    expect($this->settings->get('sender.address'))->same('authorized@email.com');
    expect(MailerLog::isSendingPaused())->false();
  }

  public function testItRejectsUnauthorizedFromAddress() {
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once(['authorized@email.com'])]);
    $this->endpoint = new Settings(
      $this->settings,
      $bridgeMock,
      new AuthorizedEmailsController($this->settings, $bridgeMock, $this->diContainer->get(NewslettersRepository::class)),
      $this->make(TransactionalEmails::class),
      $this->make(ServicesChecker::class)
    );

    $this->settings->set('sender.address', '');
    $response = $this->endpoint->setAuthorizedFromAddress(['address' => 'invalid@email.com']);
    expect($response->status)->same(400);
    expect($response->getData()['errors'][0])->same([
      'error' => 'unauthorized',
      'message' => 'Can’t use this email yet! Please authorize it first.',
    ]);
    expect($this->settings->get('sender.address'))->same('');
  }

  public function testItSchedulesInactiveSubscribersCheckIfIntervalSettingChanges() {
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
    expect($task->scheduledAt)->lessThan(Carbon::now());
  }

  public function testItRemovesFreeAddressOverrideOnMSSActivation() {
    $_SERVER['HTTP_HOST'] = 'www.mailpoet.com';

    $this->settings->set('sender', ['address' => 'wordpress@mailpoet.com']);
    $this->settings->set('reply_to', ['address' => 'johndoeexampletestnonexistinghopefullyfreemail@gmail.com']);
    $this->settings->set('mta_group', 'non-mss-sending-method');


    $newSettings = ['mta_group' => 'mailpoet'];
    $this->endpoint->set($newSettings);

    $this->settings->resetCache();
    expect($this->settings->get('sender')['address'])->equals('johndoeexampletestnonexistinghopefullyfreemail@gmail.com');
    expect($this->settings->get('reply_to'))->isEmpty();
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
  }
}
