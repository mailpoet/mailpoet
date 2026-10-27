<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron;

use Codeception\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Supervisor;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class SupervisorTest extends \MailPoetTest {
  public $supervisor;
  public $cronHelper;
  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    // cron trigger is by default set to 'WordPress'; when it runs and does not
    // detect any queues to process, it deletes the daemon setting, so Supervisor and
    // CronHelper's getDaemon() methods do not work. for that matter, we need to set
    // the trigger setting to anything but 'WordPress'.
    $this->settings = SettingsController::getInstance();
    $this->settings->set('cron_trigger', [
      'method' => 'none',
    ]);
    // The daemon is started over HTTP and the request waits for the 5-second timeout; the
    // decisions under test only read and write the daemon setting.
    $this->cronHelper = $this->getServiceWithOverrides(CronHelper::class, [
      'wp' => Stub::make(new WPFunctions(), ['wpRemotePost' => ['body' => 'OK']]),
    ]);
    $this->supervisor = new Supervisor($this->cronHelper);
  }

  public function testItCanBeInitialized() {
    $this->supervisor->init();
    verify($this->supervisor->token)->notEmpty();
    verify($this->supervisor->daemon)->notEmpty();
  }

  public function testItCreatesDaemonWhenOneDoesNotExist() {
    verify($this->settings->get(CronHelper::DAEMON_SETTING))->null();
    $this->supervisor->init();
    verify($this->supervisor->getDaemon())->notEmpty();
  }

  public function testItReturnsDaemonWhenOneExists() {
    $this->supervisor->init();
    verify($this->supervisor->getDaemon())->equals($this->supervisor->daemon);
  }

  public function testItDoesNothingWhenDaemonExecutionDurationIsBelowLimit() {
    $this->supervisor->init();
    verify($this->supervisor->checkDaemon())
      ->equals($this->supervisor->daemon);
  }

  public function testRestartsDaemonWhenExecutionDurationIsAboveLimit() {
    $this->supervisor->init();
    $this->supervisor->daemon['updated_at'] = time() - $this->cronHelper->getDaemonExecutionTimeout();
    $daemon = $this->supervisor->checkDaemon();
    verify(is_int($daemon['updated_at']))->true();
    verify($daemon['updated_at'])->notEquals($this->supervisor->daemon['updated_at']);
    verify($daemon['status'])->equals(CronHelper::DAEMON_STATUS_ACTIVE);
  }

  public function testRestartsDaemonWhenItIsInactive() {
    $this->supervisor->init();
    $this->supervisor->daemon['updated_at'] = time();
    $this->supervisor->daemon['status'] = CronHelper::DAEMON_STATUS_INACTIVE;
    $daemon = $this->supervisor->checkDaemon();
    verify($daemon['status'])->equals(CronHelper::DAEMON_STATUS_ACTIVE);
  }
}
