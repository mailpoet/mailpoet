<?php
namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use MailPoet\Cron\Workers\AuthorizedSendingEmailsCheck;
use MailPoet\Models\ScheduledTask;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;

class AuthorizedSendingEmailsCheckTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->settings = new SettingsController();
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }

  function testItRunsCheckOnBridge() {
    $bridge_mock = $this->makeEmpty(AuthorizedEmailsController::class, ['checkAuthorizedEmailAddresses' => Stub\Expected::once()]);
    $worker = new AuthorizedSendingEmailsCheck($bridge_mock);
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate([]));
  }

  function testItDoesNotScheduleAutomatically() {
    $this->settings->set('mta_group', 'mailpoet');
    $this->settings->set('mta.method', 'MailPoet');
    $bridge_mock = $this->makeEmpty(AuthorizedEmailsController::class, ['checkAuthorizedEmailAddresses' => Stub\Expected::never()]);
    $worker = new AuthorizedSendingEmailsCheck($bridge_mock);
    $worker->process();

    $task = ScheduledTask::where('type', AuthorizedSendingEmailsCheck::TASK_TYPE)
      ->where('status', ScheduledTask::STATUS_SCHEDULED)
      ->findOne();
    expect($task)->false();
  }
}
