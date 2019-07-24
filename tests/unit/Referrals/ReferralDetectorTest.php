<?php
namespace MailPoet\Referrals;

use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class ReferralDetectorTest extends \MailPoetUnitTest {

  /** @var MockObject */
  private $settings_mock;

  /** @var MockObject */
  private $wp_mock;

  function _before() {
    $this->settings_mock = $this->createMock(SettingsController::class);
    $this->wp_mock = $this->createMock(WPFunctions::class);
    if (!defined(ReferralDetector::REFERRAL_CONSTANT_NAME)) {
      define(ReferralDetector::REFERRAL_CONSTANT_NAME, 'constant_referral_id');
    }
  }

  function testItPrefersSettingsValueOverAll() {
    $this->settings_mock
      ->expects($this->once())
      ->method('get')
      ->willReturn('settings_referral_id');
    $this->wp_mock
      ->expects($this->never())
      ->method('getOption');
    $referral_detector = new ReferralDetector($this->wp_mock, $this->settings_mock);
    expect($referral_detector->detect())->equals('settings_referral_id');
  }

  function testItPrefersOptionValueOverConstantAndStoreValueToSettings() {
    $this->settings_mock
      ->expects($this->once())
      ->method('get')
      ->willReturn(null);
    $this->wp_mock
      ->expects($this->once())
      ->method('getOption')
      ->willReturn('option_referral_id');
    $this->settings_mock
      ->expects($this->once())
      ->method('set')
      ->with(ReferralDetector::REFERRAL_SETTING_NAME, 'option_referral_id');
    $referral_detector = new ReferralDetector($this->wp_mock, $this->settings_mock);
    expect($referral_detector->detect())->equals('option_referral_id');
  }

  function testItCanReadConstantAndStoreValueToSettings() {
    $this->settings_mock
      ->expects($this->once())
      ->method('get')
      ->willReturn(null);
    $this->wp_mock
      ->expects($this->once())
      ->method('getOption')
      ->willReturn(null);
    $this->settings_mock
      ->expects($this->once())
      ->method('set')
      ->with(ReferralDetector::REFERRAL_SETTING_NAME, 'constant_referral_id');
    $referral_detector = new ReferralDetector($this->wp_mock, $this->settings_mock);
    expect($referral_detector->detect())->equals('constant_referral_id');
  }
}
