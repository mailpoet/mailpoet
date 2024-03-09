<?php declare(strict_types = 1);

namespace MailPoet\Util\License\Features;

use MailPoet\Config\ServicesChecker;
use MailPoet\Settings\SettingsController;

class CapabilitiesManagerTest extends \MailPoetTest {
  public function testItGetsCapabilities() {
    $capabilitiesManager = $this->getCapabilitiesManager();
    $capabilities = $capabilitiesManager->getCapabilities();
    verify($capabilities->getMailpoetLogoInEmails())->false();
    verify($capabilities->getDetailedAnalytics())->true();
    verify($capabilities->getAutomationSteps())->equals(1);
    verify($capabilities->getSegmentFilters())->equals(1);
  }

  public function testItGetsCapabilitiesFromFreeTier() {
    $settings = $this->createMock(SettingsController::class);
    $settings->method('get')->willReturnMap([
      ['mta.mailpoet_api_key_state.data.tier', null, 0],
    ]);
    $capabilitiesManager = $this->getCapabilitiesManager($settings);
    $capabilities = $capabilitiesManager->getCapabilities();
    verify($capabilities->getMailpoetLogoInEmails())->true();
    verify($capabilities->getDetailedAnalytics())->false();
    verify($capabilities->getAutomationSteps())->equals(1);
    verify($capabilities->getSegmentFilters())->equals(1);
  }

  public function testItGetsCapabilitiesFromTier1Tier() {
    $settings = $this->createMock(SettingsController::class);
    $settings->method('get')->willReturnMap([
      ['mta.mailpoet_api_key_state.data.tier', null, 1],
    ]);
    $capabilitiesManager = $this->getCapabilitiesManager($settings);
    $capabilities = $capabilitiesManager->getCapabilities();
    verify($capabilities->getMailpoetLogoInEmails())->false();
    verify($capabilities->getDetailedAnalytics())->true();
    verify($capabilities->getAutomationSteps())->equals(1);
    verify($capabilities->getSegmentFilters())->equals(1);
  }

  public function testItGetsCapabilitiesFromTier2Tier() {
    $settings = $this->createMock(SettingsController::class);
    $settings->method('get')->willReturnMap([
      ['mta.mailpoet_api_key_state.data.tier', null, 2],
    ]);
    $capabilitiesManager = $this->getCapabilitiesManager($settings);
    $capabilities = $capabilitiesManager->getCapabilities();
    verify($capabilities->getMailpoetLogoInEmails())->false();
    verify($capabilities->getDetailedAnalytics())->true();
    verify($capabilities->getAutomationSteps())->equals(0);
    verify($capabilities->getSegmentFilters())->equals(0);
  }

  public function testIndividualCapsCanBeUsedToGiveGreaterAccessThanTier() {
    $settings = $this->createMock(SettingsController::class);
    $settings->method('get')->willReturnMap([
      ['mta.mailpoet_api_key_state.data.tier', null, 0],
      ['mta.mailpoet_api_key_state.data.mailpoet_logo_in_emails', null, false],
      ['mta.mailpoet_api_key_state.data.detailed_analytics', null, true],
      ['mta.mailpoet_api_key_state.data.automation_steps', null, 2],
      ['mta.mailpoet_api_key_state.data.segment_filters', null, 0],
    ]);
    $capabilitiesManager = $this->getCapabilitiesManager($settings);
    $capabilities = $capabilitiesManager->getCapabilities();
    verify($capabilities->getMailpoetLogoInEmails())->false();
    verify($capabilities->getDetailedAnalytics())->true();
    verify($capabilities->getAutomationSteps())->equals(2);
    verify($capabilities->getSegmentFilters())->equals(0);
  }

  public function testIndividualCapsAreOnlyAppliedIfLessRestrictiveThanTier() {
    $settings = $this->createMock(SettingsController::class);
    $settings->method('get')->willReturnMap([
      ['mta.mailpoet_api_key_state.data.tier', null, 2],
      ['mta.mailpoet_api_key_state.data.mailpoet_logo_in_emails', null, true],
      ['mta.mailpoet_api_key_state.data.detailed_analytics', null, false],
      ['mta.mailpoet_api_key_state.data.automation_steps', null, 1],
      ['mta.mailpoet_api_key_state.data.segment_filters', null, 1],
    ]);
    $capabilitiesManager = $this->getCapabilitiesManager($settings);
    $capabilities = $capabilitiesManager->getCapabilities();
    verify($capabilities->getMailpoetLogoInEmails())->false();
    verify($capabilities->getDetailedAnalytics())->true();
    verify($capabilities->getAutomationSteps())->equals(0);
    verify($capabilities->getSegmentFilters())->equals(0);
  }

  public function testIndividualCapsAreAppliedIfThereIsNoTier() {
    $settings = $this->createMock(SettingsController::class);
    $settings->method('get')->willReturnMap([
      ['mta.mailpoet_api_key_state.data.mailpoet_logo_in_emails', null, true],
      ['mta.mailpoet_api_key_state.data.detailed_analytics', null, false],
      ['mta.mailpoet_api_key_state.data.automation_steps', null, 1],
      ['mta.mailpoet_api_key_state.data.segment_filters', null, 1],
    ]);
    $capabilitiesManager = $this->getCapabilitiesManager($settings);
    $capabilities = $capabilitiesManager->getCapabilities();
    verify($capabilities->getMailpoetLogoInEmails())->true();
    verify($capabilities->getDetailedAnalytics())->false();
    verify($capabilities->getAutomationSteps())->equals(1);
    verify($capabilities->getSegmentFilters())->equals(1);
  }

  public function testLogoDisplayFallsBackToUserActivelyPayingIfNoCapabilities() {
    $settings = $this->createMock(SettingsController::class);
    $settings->method('get')->willReturnMap($this->getLegacySettings());
    $servicesChecker = $this->createMock(ServicesChecker::class);
    $servicesChecker->method('isPremiumKeyValid')->willReturn(true);
    $servicesChecker->method('isMailPoetAPIKeyValid')->willReturn(true);
    $servicesChecker->method('isUserActivelyPaying')->willReturn(true);
    $capabilitiesManager = $this->getCapabilitiesManager($settings, $servicesChecker);
    $capabilities = $capabilitiesManager->getCapabilities();
    verify($capabilities->getMailpoetLogoInEmails())->false();
  }

  public function testDetailedAnalyticsFallsBackToPremiumKeyAndSubscribersChecksIfNoCapabilities() {
    $settings = $this->createMock(SettingsController::class);
    $settings->method('get')->willReturnMap($this->getLegacySettings());
    $servicesChecker = $this->createMock(ServicesChecker::class);
    $servicesChecker->method('isPremiumKeyValid')->willReturn(true);
    $servicesChecker->method('isMailPoetAPIKeyValid')->willReturn(true);
    $servicesChecker->method('isUserActivelyPaying')->willReturn(true);
    $capabilitiesManager = $this->getCapabilitiesManager($settings);
    $capabilities = $capabilitiesManager->getCapabilities();
    verify($capabilities->getDetailedAnalytics())->true();
  }

  public function testAutomationStepsAndSegmentFiltersAreUnlimitedIfNoCapabilities() {
    $settings = $this->createMock(SettingsController::class);
    $settings->method('get')->willReturnMap($this->getLegacySettings());
    $capabilitiesManager = $this->getCapabilitiesManager($settings);
    $capabilities = $capabilitiesManager->getCapabilities();
    verify($capabilities->getAutomationSteps())->equals(0);
    verify($capabilities->getSegmentFilters())->equals(0);
  }

  private function getCapabilitiesManager($settingsMock = null, $servicesCheckerMock = null, $subscribersFeatureMock = null): CapabilitiesManager {
    $settings = $this->createMock(SettingsController::class);
    $settings->method('get')->willReturnMap([
      ['mta.mailpoet_api_key_state.data.tier', null, 1],
      ['mta.mailpoet_api_key_state.data.mailpoet_logo_in_emails', null, false],
      ['mta.mailpoet_api_key_state.data.detailed_analytics', null, true],
      ['mta.mailpoet_api_key_state.data.automation_steps', null, 1],
      ['mta.mailpoet_api_key_state.data.segment_filters', null, 1],
    ]);
    $servicesChecker = $this->createMock(ServicesChecker::class);
    $servicesChecker->method('isPremiumKeyValid')->willReturn(true);
    $servicesChecker->method('isMailPoetAPIKeyValid')->willReturn(true);
    $servicesChecker->method('isUserActivelyPaying')->willReturn(true);
    $servicesChecker->method('isPremiumPluginActive')->willReturn(true);
    $subscribersFeature = $this->createMock(Subscribers::class);
    $subscribersFeature->method('hasValidPremiumKey')->willReturn(true);
    $subscribersFeature->method('check')->willReturn(true);
    return new CapabilitiesManager($settingsMock ?? $settings, $servicesCheckerMock ?? $servicesChecker, $subscribersFeatureMock ?? $subscribersFeature);
  }

  private function getLegacySettings() {
    return [
      ['mta.mailpoet_api_key_state.data.tier', null, null],
      ['mta.mailpoet_api_key_state.data.mailpoet_logo_in_emails', null, null],
      ['mta.mailpoet_api_key_state.data.detailed_analytics', null, null],
      ['mta.mailpoet_api_key_state.data.automation_steps', null, null],
      ['mta.mailpoet_api_key_state.data.segment_filters', null, null],
    ];
  }
}
