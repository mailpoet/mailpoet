<?php
namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use MailPoet\Cron\Workers\AuthorizedSendingEmailsCheck;
use MailPoet\Models\ScheduledTask;
use MailPoet\Services\Bridge;
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
    $bridge_mock = $this->makeEmpty(Bridge::class, ['checkAuthorizedEmailAddresses' => Stub\Expected::once()]);
    $worker = new AuthorizedSendingEmailsCheck($bridge_mock);
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate([]));
  }
}
