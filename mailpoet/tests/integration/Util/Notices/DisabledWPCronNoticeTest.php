<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use Codeception\Util\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronTrigger;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class DisabledWPCronNoticeTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var CronHelper */
  private $cronHelper;

  private $originalCronMethodSetting;

  public function _before() {
    parent::_before();

    $now = time();
    $this->settings = SettingsController::getInstance();
    $this->cronHelper = $this->make(
      CronHelper::class,
      ['getDaemon' => ['run_started_at' => $now, 'run_completed_at' => $now]],
    );
    $this->originalCronMethodSetting = $this->settings->get(CronTrigger::SETTING_CURRENT_METHOD);
    $this->settings->set(CronTrigger::SETTING_CURRENT_METHOD, CronTrigger::METHOD_ACTION_SCHEDULER);

    $this->wp = new WPFunctions;
    delete_transient(DisabledWPCronNotice::OPTION_NAME);
  }

  public function _after() {
    parent::_after();
    $this->settings->set(CronTrigger::SETTING_CURRENT_METHOD, $this->originalCronMethodSetting);
    delete_transient(DisabledWPCronNotice::OPTION_NAME);
  }

  public function testItPrintsWarningWhenWPCronIsDisabled() {
    $lastRun = time() - DAY_IN_SECONDS;
    $cronHelper = $this->make(
      CronHelper::class,
      ['getDaemon' => ['run_started_at' => $lastRun, 'run_completed_at' => $lastRun]],
    );
    $disabledWPCronNotice = Stub::construct(
      DisabledWPCronNotice::class,
      [$this->wp, $cronHelper, $this->settings],
      ['isWPCronDisabled' => true]
    );
    $notice = $disabledWPCronNotice->init(true);
    verify($notice->getMessage())->stringContainsString('WordPress built-in cron is disabled');
    verify($notice->getMessage())->stringContainsString('admin.php?page=mailpoet-settings#advanced');
  }

  public function testItPrintsNoWarningWhenWPCronIsNotDisabled() {
    $disabledWPCronNotice = Stub::construct(
      DisabledWPCronNotice::class,
      [$this->wp, $this->cronHelper, $this->settings],
      ['isWPCronDisabled' => false]
    );
    $notice = $disabledWPCronNotice->init(true);
    verify($notice)->null();
  }

  public function testItPrintsNoWarningWhenCronMethodIsNotActionScheduler() {
    $this->settings->set(CronTrigger::SETTING_CURRENT_METHOD, CronTrigger::METHOD_WORDPRESS);
    $disabledWPCronNotice = Stub::construct(
      DisabledWPCronNotice::class,
      [$this->wp, $this->cronHelper, $this->settings],
      ['isWPCronDisabled' => true]
    );
    $notice = $disabledWPCronNotice->init(true);
    verify($notice)->null();
  }

  public function testItPrintsNoWarningWhenDisabled() {
    $disabledWPCronNotice = Stub::construct(
      DisabledWPCronNotice::class,
      [$this->wp, $this->cronHelper, $this->settings],
      ['isWPCronDisabled' => true]
    );
    $warning = $disabledWPCronNotice->init(false);
    verify($warning)->null();
  }

  public function testItPrintsNoWarningWhenDismissed() {
    $disabledWPCronNotice = Stub::construct(
      DisabledWPCronNotice::class,
      [$this->wp, $this->cronHelper, $this->settings],
      ['isWPCronDisabled' => true]
    );
    $disabledWPCronNotice->disable();
    $warning = $disabledWPCronNotice->init(true);
    verify($warning)->null();
  }

  public function testItPrintsNoWarningWhenCronFunctions() {
    $disabledWPCronNotice = Stub::construct(
      DisabledWPCronNotice::class,
      [$this->wp, $this->cronHelper, $this->settings],
      ['isWPCronDisabled' => true]
    );
    $disabledWPCronNotice->disable();
    $warning = $disabledWPCronNotice->init(true);
    verify($warning)->null();
  }
}
