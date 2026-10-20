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
    $this->wp->method('homeUrl')->willReturn('https://example.com');
  }

  /** @param mixed $allowed */
  private function stubSettings($allowed, ?string $siteUrl = 'example.com'): void {
    $this->settings->method('get')->willReturnMap([
      [SendingEnvironment::SETTING_ALLOW_SENDING, null, $allowed],
      [SendingEnvironment::SETTING_ALLOW_SENDING_SITE_URL, null, $siteUrl],
    ]);
  }

  /**
   * @dataProvider sendingAllowedProvider
   * @param mixed $setting
   */
  public function testIsSendingAllowed(string $environment, $setting, ?bool $filterResult, bool $expected): void {
    $this->wp->method('wpGetEnvironmentType')->willReturn($environment);
    $this->stubSettings($setting);
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
    $this->stubSettings('1');
    $this->settings->expects($this->never())->method('delete');

    verify($this->createEnvironment()->isSendingAllowed())->true();
  }

  public function testClearOverrideOnProductionDeletesAStoredOverride(): void {
    $this->wp->method('wpGetEnvironmentType')->willReturn('production');
    $this->stubSettings('1');
    $deleted = [];
    $this->settings->method('delete')->willReturnCallback(function ($key) use (&$deleted) {
      $deleted[] = $key;
    });

    $this->createEnvironment()->clearOverrideOnProduction();

    verify($deleted)->same([
      SendingEnvironment::SETTING_ALLOW_SENDING,
      SendingEnvironment::SETTING_ALLOW_SENDING_SITE_URL,
    ]);
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
    $this->stubSettings('1');
    $this->wp->method('applyFilters')->willReturnArgument(1);
    $this->settings->expects($this->never())->method('delete');

    verify($this->createEnvironment()->isSendingAllowed())->true();
  }

  public function testAllowSendingStoresTheSettingAndTheSiteAddress(): void {
    $stored = [];
    $this->settings->method('set')->willReturnCallback(function ($key, $value) use (&$stored) {
      $stored[$key] = $value;
    });

    $this->createEnvironment()->allowSending();

    verify($stored)->same([
      SendingEnvironment::SETTING_ALLOW_SENDING => '1',
      SendingEnvironment::SETTING_ALLOW_SENDING_SITE_URL => 'example.com',
    ]);
  }

  /** @dataProvider siteUrlProvider */
  public function testSettingCountsOnlyForTheSiteItWasTurnedOnFor(?string $storedUrl, string $homeUrl, bool $expected): void {
    $this->wp = $this->createMock(WPFunctions::class);
    $this->wp->method('homeUrl')->willReturn($homeUrl);
    $this->wp->method('wpGetEnvironmentType')->willReturn('staging');
    $this->wp->method('applyFilters')->willReturnArgument(1);
    $this->stubSettings('1', $storedUrl);

    verify($this->createEnvironment()->isSendingAllowed())->same($expected);
  }

  public function siteUrlProvider(): array {
    return [
      'same address' => ['example.com', 'https://example.com', true],
      'other address' => ['example.com', 'https://staging.example.com', false],
      'other path' => ['example.com/shop', 'https://example.com', false],
      'no stored address' => [null, 'https://example.com', false],
      'empty stored address' => ['', 'https://example.com', false],
      'http instead of https' => ['example.com', 'http://example.com', true],
      'trailing slash' => ['example.com/blog', 'https://example.com/blog/', true],
      'host case' => ['example.com', 'https://EXAMPLE.com', true],
    ];
  }

  public function testSyncStoresTheAddressWhenTheSettingIsOn(): void {
    $this->stubSettings('1', null);
    $this->settings->expects($this->once())->method('set')->with(SendingEnvironment::SETTING_ALLOW_SENDING_SITE_URL, 'example.com');
    $this->settings->expects($this->never())->method('delete');

    $this->createEnvironment()->syncSiteUrlWithSetting();
  }

  public function testSyncClearsTheAddressWhenTheSettingIsOff(): void {
    $this->stubSettings('');
    $this->settings->expects($this->never())->method('set');
    $this->settings->expects($this->once())->method('delete')->with(SendingEnvironment::SETTING_ALLOW_SENDING_SITE_URL);

    $this->createEnvironment()->syncSiteUrlWithSetting();
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
