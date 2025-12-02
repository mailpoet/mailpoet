<?php declare(strict_types = 1);

namespace MailPoet\Test\Settings;

use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class TrackingConfigTest extends \MailPoetUnitTest {

  /** @var MockObject|SettingsController */
  private $settingsMock;

  /** @var MockObject|WPFunctions */
  private $wpMock;

  /** @var TrackingConfig */
  private $trackingConfig;

  public function _before() {
    parent::_before();
    $this->settingsMock = $this->createMock(SettingsController::class);
    $this->wpMock = $this->createMock(WPFunctions::class);
    $this->trackingConfig = new TrackingConfig($this->settingsMock, $this->wpMock);
  }

  public function testIsEmailTrackingEnabledWithFullLevel() {
    $this->settingsMock->method('get')
      ->with('tracking.level', TrackingConfig::LEVEL_FULL)
      ->willReturn(TrackingConfig::LEVEL_FULL);

    verify($this->trackingConfig->isEmailTrackingEnabled())->true();
  }

  public function testIsEmailTrackingEnabledWithPartialLevel() {
    $this->settingsMock->method('get')
      ->with('tracking.level', TrackingConfig::LEVEL_FULL)
      ->willReturn(TrackingConfig::LEVEL_PARTIAL);

    verify($this->trackingConfig->isEmailTrackingEnabled())->true();
  }

  public function testIsEmailTrackingEnabledWithBasicLevel() {
    $this->settingsMock->method('get')
      ->with('tracking.level', TrackingConfig::LEVEL_FULL)
      ->willReturn(TrackingConfig::LEVEL_BASIC);

    verify($this->trackingConfig->isEmailTrackingEnabled())->false();
  }

  public function testIsEmailTrackingEnabledWithExplicitLevel() {
    // Should not call settings->get when explicit level is provided
    $this->settingsMock->expects($this->never())->method('get');

    verify($this->trackingConfig->isEmailTrackingEnabled(TrackingConfig::LEVEL_FULL))->true();
    verify($this->trackingConfig->isEmailTrackingEnabled(TrackingConfig::LEVEL_PARTIAL))->true();
    verify($this->trackingConfig->isEmailTrackingEnabled(TrackingConfig::LEVEL_BASIC))->false();
  }

  public function testIsCookieTrackingEnabledWithFullLevel() {
    $this->settingsMock->method('get')
      ->with('tracking.level', TrackingConfig::LEVEL_FULL)
      ->willReturn(TrackingConfig::LEVEL_FULL);

    $this->wpMock->expects($this->once())
      ->method('applyFilters')
      ->with('mailpoet_is_cookie_tracking_enabled', true)
      ->willReturn(true);

    verify($this->trackingConfig->isCookieTrackingEnabled())->true();
  }

  public function testIsCookieTrackingEnabledWithPartialLevel() {
    $this->settingsMock->method('get')
      ->with('tracking.level', TrackingConfig::LEVEL_FULL)
      ->willReturn(TrackingConfig::LEVEL_PARTIAL);

    $this->wpMock->expects($this->once())
      ->method('applyFilters')
      ->with('mailpoet_is_cookie_tracking_enabled', false)
      ->willReturn(false);

    verify($this->trackingConfig->isCookieTrackingEnabled())->false();
  }

  public function testIsCookieTrackingEnabledWithBasicLevel() {
    $this->settingsMock->method('get')
      ->with('tracking.level', TrackingConfig::LEVEL_FULL)
      ->willReturn(TrackingConfig::LEVEL_BASIC);

    $this->wpMock->expects($this->once())
      ->method('applyFilters')
      ->with('mailpoet_is_cookie_tracking_enabled', false)
      ->willReturn(false);

    verify($this->trackingConfig->isCookieTrackingEnabled())->false();
  }

  public function testIsCookieTrackingEnabledWithFilterOverride() {
    $this->settingsMock->method('get')
      ->with('tracking.level', TrackingConfig::LEVEL_FULL)
      ->willReturn(TrackingConfig::LEVEL_FULL);

    // Filter can override the result
    $this->wpMock->expects($this->once())
      ->method('applyFilters')
      ->with('mailpoet_is_cookie_tracking_enabled', true)
      ->willReturn(false);

    verify($this->trackingConfig->isCookieTrackingEnabled())->false();
  }

  public function testIsCookieTrackingEnabledWithExplicitLevel() {
    // Should not call settings->get when explicit level is provided
    $this->settingsMock->expects($this->never())->method('get');

    $this->wpMock->expects($this->once())
      ->method('applyFilters')
      ->with('mailpoet_is_cookie_tracking_enabled', true)
      ->willReturn(true);

    verify($this->trackingConfig->isCookieTrackingEnabled(TrackingConfig::LEVEL_FULL))->true();
  }

  public function testAreOpensMergedWithMergedSetting() {
    $this->settingsMock->method('get')
      ->with('tracking.opens', TrackingConfig::OPENS_MERGED)
      ->willReturn(TrackingConfig::OPENS_MERGED);

    verify($this->trackingConfig->areOpensMerged())->true();
  }

  public function testAreOpensMergedWithSeparatedSetting() {
    $this->settingsMock->method('get')
      ->with('tracking.opens', TrackingConfig::OPENS_MERGED)
      ->willReturn(TrackingConfig::OPENS_SEPARATED);

    verify($this->trackingConfig->areOpensMerged())->false();
  }

  public function testAreOpensMergedWithExplicitParameter() {
    // Should not call settings->get when explicit parameter is provided
    $this->settingsMock->expects($this->never())->method('get');

    verify($this->trackingConfig->areOpensMerged(TrackingConfig::OPENS_MERGED))->true();
    verify($this->trackingConfig->areOpensMerged(TrackingConfig::OPENS_SEPARATED))->false();
  }

  public function testAreOpensSeparatedWithMergedSetting() {
    $this->settingsMock->method('get')
      ->with('tracking.opens', TrackingConfig::OPENS_MERGED)
      ->willReturn(TrackingConfig::OPENS_MERGED);

    verify($this->trackingConfig->areOpensSeparated())->false();
  }

  public function testAreOpensSeparatedWithSeparatedSetting() {
    $this->settingsMock->method('get')
      ->with('tracking.opens', TrackingConfig::OPENS_MERGED)
      ->willReturn(TrackingConfig::OPENS_SEPARATED);

    verify($this->trackingConfig->areOpensSeparated())->true();
  }

  public function testAreOpensSeparatedWithExplicitParameter() {
    // Should not call settings->get when explicit parameter is provided
    $this->settingsMock->expects($this->never())->method('get');

    verify($this->trackingConfig->areOpensSeparated(TrackingConfig::OPENS_MERGED))->false();
    verify($this->trackingConfig->areOpensSeparated(TrackingConfig::OPENS_SEPARATED))->true();
  }

  public function testGetConfigReturnsCompleteConfiguration() {
    $this->settingsMock->method('get')
      ->willReturnMap([
        ['tracking.level', TrackingConfig::LEVEL_FULL, TrackingConfig::LEVEL_PARTIAL],
        ['tracking.opens', TrackingConfig::OPENS_MERGED, TrackingConfig::OPENS_SEPARATED],
      ]);

    $this->wpMock->method('applyFilters')
      ->with('mailpoet_is_cookie_tracking_enabled', false)
      ->willReturn(false);

    $config = $this->trackingConfig->getConfig();

    verify($config)->arrayHasKey('level');
    verify($config)->arrayHasKey('emailTrackingEnabled');
    verify($config)->arrayHasKey('cookieTrackingEnabled');
    verify($config)->arrayHasKey('opens');
    verify($config)->arrayHasKey('opensMerged');
    verify($config)->arrayHasKey('opensSeparated');

    verify($config['level'])->equals(TrackingConfig::LEVEL_PARTIAL);
    verify($config['emailTrackingEnabled'])->true();
    verify($config['cookieTrackingEnabled'])->false();
    verify($config['opens'])->equals(TrackingConfig::OPENS_SEPARATED);
    verify($config['opensMerged'])->false();
    verify($config['opensSeparated'])->true();
  }

  public function testGetConfigWithFullTrackingLevel() {
    $this->settingsMock->method('get')
      ->willReturnMap([
        ['tracking.level', TrackingConfig::LEVEL_FULL, TrackingConfig::LEVEL_FULL],
        ['tracking.opens', TrackingConfig::OPENS_MERGED, TrackingConfig::OPENS_MERGED],
      ]);

    $this->wpMock->method('applyFilters')
      ->with('mailpoet_is_cookie_tracking_enabled', true)
      ->willReturn(true);

    $config = $this->trackingConfig->getConfig();

    verify($config['level'])->equals(TrackingConfig::LEVEL_FULL);
    verify($config['emailTrackingEnabled'])->true();
    verify($config['cookieTrackingEnabled'])->true();
    verify($config['opens'])->equals(TrackingConfig::OPENS_MERGED);
    verify($config['opensMerged'])->true();
    verify($config['opensSeparated'])->false();
  }

  public function testGetConfigWithBasicTrackingLevel() {
    $this->settingsMock->method('get')
      ->willReturnMap([
        ['tracking.level', TrackingConfig::LEVEL_FULL, TrackingConfig::LEVEL_BASIC],
        ['tracking.opens', TrackingConfig::OPENS_MERGED, TrackingConfig::OPENS_MERGED],
      ]);

    $this->wpMock->method('applyFilters')
      ->with('mailpoet_is_cookie_tracking_enabled', false)
      ->willReturn(false);

    $config = $this->trackingConfig->getConfig();

    verify($config['level'])->equals(TrackingConfig::LEVEL_BASIC);
    verify($config['emailTrackingEnabled'])->false();
    verify($config['cookieTrackingEnabled'])->false();
  }
}
