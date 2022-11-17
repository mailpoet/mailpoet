<?php declare(strict_types = 1);

namespace MailPoet\Referrals;

use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class ReferralDetectorTest extends \MailPoetUnitTest {

  /** @var SettingsController&MockObject */
  private $settingsMock;

  /** @var WPFunctions&MockObject */
  private $wpMock;

  public function _before() {
    $this->settingsMock = $this->createMock(SettingsController::class);
    $this->wpMock = $this->createMock(WPFunctions::class);
    if (!defined(ReferralDetector::REFERRAL_CONSTANT_NAME)) {
      define(ReferralDetector::REFERRAL_CONSTANT_NAME, 'constant_referral_id');
    }
  }

  public function testItPrefersSettingsValue() {
    $this->settingsMock
      ->expects($this->once())
      ->method('get')
      ->willReturn('settings_referral_id');
    $this->wpMock
      ->expects($this->never())
      ->method('getOption');
    $referralDetector = new ReferralDetector($this->wpMock, $this->settingsMock);
    expect($referralDetector->detect())->equals('settings_referral_id');
  }

  public function testItPrefersOptionValueToConstantAndStoresValueToSettings() {
    $this->settingsMock
      ->expects($this->once())
      ->method('get')
      ->willReturn(null);
    $this->wpMock
      ->expects($this->once())
      ->method('getOption')
      ->willReturn('option_referral_id');
    $this->settingsMock
      ->expects($this->once())
      ->method('set')
      ->with(ReferralDetector::REFERRAL_SETTING_NAME, 'option_referral_id');
    $referralDetector = new ReferralDetector($this->wpMock, $this->settingsMock);
    expect($referralDetector->detect())->equals('option_referral_id');
  }

  public function testItCanReadConstantAndStoreValueToSettings() {
    $this->settingsMock
      ->expects($this->once())
      ->method('get')
      ->willReturn(null);
    $this->wpMock
      ->expects($this->once())
      ->method('getOption')
      ->willReturn(null);
    $this->settingsMock
      ->expects($this->once())
      ->method('set')
      ->with(ReferralDetector::REFERRAL_SETTING_NAME, 'constant_referral_id');
    $referralDetector = new ReferralDetector($this->wpMock, $this->settingsMock);
    expect($referralDetector->detect())->equals('constant_referral_id');
  }
}
