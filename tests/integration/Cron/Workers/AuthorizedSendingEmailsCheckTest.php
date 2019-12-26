<?php

namespace MailPoet\Test\Cron\Workers;

use Codeception\Stub;
use MailPoet\Cron\Workers\AuthorizedSendingEmailsCheck;
use MailPoet\Models\ScheduledTask;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoetVendor\Idiorm\ORM;

class AuthorizedSendingEmailsCheckTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }

  public function testItRunsCheckOnBridge() {
    $bridge_mock = $this->makeEmpty(AuthorizedEmailsController::class, ['checkAuthorizedEmailAddresses' => Stub\Expected::once()]);
    $worker = new AuthorizedSendingEmailsCheck($bridge_mock);
    $worker->processTaskStrategy(ScheduledTask::createOrUpdate([]), microtime(true));
  }
}
