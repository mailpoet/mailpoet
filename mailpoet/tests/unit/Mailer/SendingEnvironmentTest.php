<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer;

use MailPoet\Mailer\SendingEnvironment;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class SendingEnvironmentTest extends \MailPoetUnitTest {
  /** @var MockObject&SettingsController */
  private $settings;

  /** @var MockObject&WPFunctions */
  private $wp;

  public function _before() {
    parent::_before();
    $this->settings = $this->createMock(SettingsController::class);
    $this->wp = $this->createMock(WPFunctions::class);
  }

  /**
   * @dataProvider sendingAllowedProvider
   * @param mixed $setting
   */
  public function testIsSendingAllowed(string $environment, $setting, ?bool $filterResult, bool $expected): void {
    $this->wp->method('wpGetEnvironmentType')->willReturn($environment);
    $this->settings->method('get')->with(SendingEnvironment::SETTING_ALLOW_SENDING)->willReturn($setting);
    $this->wp->method('applyFilters')->willReturnCallback(
      function (string $hook, $value) use ($setting, $filterResult) {
        verify($hook)->same(SendingEnvironment::FILTER_ALLOW_SENDING);
        verify($value)->same($setting === '1');
        return $filterResult ?? $value;
      }
    );

    verify($this->createEnvironment()->isSendingAllowed())->same($expected);
  }

  public function sendingAllowedProvider(): array {
    $cases = [];
    foreach (['staging', 'development', 'local'] as $environment) {
      $cases["$environment, no setting"] = [$environment, null, null, false];
      $cases["$environment, empty setting"] = [$environment, '', null, false];
      $cases["$environment, setting on"] = [$environment, '1', null, true];
      $cases["$environment, filter allows"] = [$environment, null, true, true];
      $cases["$environment, filter blocks"] = [$environment, '1', false, false];
    }
    $cases['production, no setting'] = ['production', null, null, true];
    return $cases;
  }

  public function testProductionIgnoresTheFilter(): void {
    $this->wp->method('wpGetEnvironmentType')->willReturn('production');
    $this->settings->method('get')->willReturn(null);
    $this->wp->expects($this->never())->method('applyFilters');

    verify($this->createEnvironment()->isSendingAllowed())->true();
  }

  public function testIsSendingAllowedNeverDeletesTheSetting(): void {
    $this->wp->method('wpGetEnvironmentType')->willReturn('production');
    $this->settings->method('get')->with(SendingEnvironment::SETTING_ALLOW_SENDING)->willReturn('1');
    $this->settings->expects($this->never())->method('delete');

    verify($this->createEnvironment()->isSendingAllowed())->true();
  }

  public function testClearOverrideOnProductionDeletesAStoredOverride(): void {
    $this->wp->method('wpGetEnvironmentType')->willReturn('production');
    $this->settings->method('get')->with(SendingEnvironment::SETTING_ALLOW_SENDING)->willReturn('1');
    $this->settings->expects($this->once())->method('delete')->with(SendingEnvironment::SETTING_ALLOW_SENDING);

    $this->createEnvironment()->clearOverrideOnProduction();
  }

  /**
   * @dataProvider emptySettingProvider
   * @param mixed $setting
   */
  public function testClearOverrideOnProductionKeepsAnEmptySetting($setting): void {
    $this->wp->method('wpGetEnvironmentType')->willReturn('production');
    $this->settings->method('get')->willReturn($setting);
    $this->settings->expects($this->never())->method('delete');

    $this->createEnvironment()->clearOverrideOnProduction();
  }

  public function emptySettingProvider(): array {
    return [[null], ['']];
  }

  public function testClearOverrideOnProductionKeepsTheSettingOnStaging(): void {
    $this->wp->method('wpGetEnvironmentType')->willReturn('staging');
    $this->settings->method('get')->willReturn('1');
    $this->settings->expects($this->never())->method('delete');

    $this->createEnvironment()->clearOverrideOnProduction();
  }

  public function testNonProductionNeverDeletesTheSetting(): void {
    $this->wp->method('wpGetEnvironmentType')->willReturn('staging');
    $this->settings->method('get')->willReturn('1');
    $this->wp->method('applyFilters')->willReturnArgument(1);
    $this->settings->expects($this->never())->method('delete');

    verify($this->createEnvironment()->isSendingAllowed())->true();
  }

  public function testAllowSendingStoresTheSetting(): void {
    $this->settings->expects($this->once())->method('set')->with(SendingEnvironment::SETTING_ALLOW_SENDING, '1');

    $this->createEnvironment()->allowSending();
  }

  public function testEveryGatedTaskTypeIsGatedWhenSendingIsBlocked(): void {
    $this->wp->method('wpGetEnvironmentType')->willReturn('staging');
    $this->settings->method('get')->willReturn(null);
    $this->wp->method('applyFilters')->willReturnArgument(1);
    $environment = $this->createEnvironment();

    verify(SendingEnvironment::GATED_TASK_TYPES)->same([
      'schedule',
      'sending',
      'stats_notification',
      'stats_notification_automated_emails',
      'bulk_confirmation_email_resend',
      'subscriber_limit_notification',
    ]);
    foreach (SendingEnvironment::GATED_TASK_TYPES as $type) {
      verify($environment->isTaskTypeGated($type))->true();
    }
    verify($environment->isTaskTypeGated('bounce'))->false();
  }

  public function testNoTaskTypeIsGatedWhenSendingIsAllowed(): void {
    $this->wp->method('wpGetEnvironmentType')->willReturn('production');
    $this->settings->method('get')->willReturn(null);
    $environment = $this->createEnvironment();

    foreach (SendingEnvironment::GATED_TASK_TYPES as $type) {
      verify($environment->isTaskTypeGated($type))->false();
    }
  }

  public function testItReportsTheEnvironmentType(): void {
    $this->wp->method('wpGetEnvironmentType')->willReturn('local');
    $environment = $this->createEnvironment();

    verify($environment->getEnvironmentType())->same('local');
    verify($environment->isProduction())->false();
  }

  private function createEnvironment(): SendingEnvironment {
    return new SendingEnvironment($this->settings, $this->wp);
  }
}
