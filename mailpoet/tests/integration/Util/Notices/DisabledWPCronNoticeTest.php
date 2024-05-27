<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use Codeception\Util\Stub;
use MailPoet\Cron\CronTrigger;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class DisabledWPCronNoticeTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  private $originalCronMethodSetting;

  public function _before() {
    parent::_before();

    $this->settings = SettingsController::getInstance();
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
    $disabledWPCronNotice = Stub::construct(
      DisabledWPCronNotice::class,
      [$this->wp, $this->settings],
      ['isWPCronDisabled' => true]
    );
    $notice = $disabledWPCronNotice->init(true);
    verify($notice->getMessage())->stringContainsString('WordPress built-in cron is disabled');
    verify($notice->getMessage())->stringContainsString('admin.php?page=mailpoet-settings#advanced');
  }

  public function testItPrintsNoWarningWhenWPCronIsNotDisabled() {
    $disabledWPCronNotice = Stub::construct(
      DisabledWPCronNotice::class,
      [$this->wp, $this->settings],
      ['isWPCronDisabled' => false]
    );
    $notice = $disabledWPCronNotice->init(true);
    verify($notice)->null();
  }

  public function testItPrintsNoWarningWhenCronMethodIsNotActionScheduler() {
    $this->settings->set(CronTrigger::SETTING_CURRENT_METHOD, CronTrigger::METHOD_WORDPRESS);
    $disabledWPCronNotice = Stub::construct(
      DisabledWPCronNotice::class,
      [$this->wp, $this->settings],
      ['isWPCronDisabled' => true]
    );
    $notice = $disabledWPCronNotice->init(true);
    verify($notice)->null();
  }

  public function testItPrintsNoWarningWhenDisabled() {
    $disabledWPCronNotice = Stub::construct(
      DisabledWPCronNotice::class,
      [$this->wp, $this->settings],
      ['isWPCronDisabled' => true]
    );
    $warning = $disabledWPCronNotice->init(false);
    verify($warning)->null();
  }

  public function testItPrintsNoWarningWhenDismissed() {
    $disabledWPCronNotice = Stub::construct(
      DisabledWPCronNotice::class,
      [$this->wp, $this->settings],
      ['isWPCronDisabled' => true]
    );
    $disabledWPCronNotice->disable();
    $warning = $disabledWPCronNotice->init(true);
    verify($warning)->null();
  }
}
