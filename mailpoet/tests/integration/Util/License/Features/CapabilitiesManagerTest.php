<?php declare(strict_types = 1);

namespace MailPoet\Util\License\Features;

use MailPoet\Settings\SettingsController;

class CapabilitiesManagerTest extends \MailPoetTest {
  public function testItGetsCapabilities() {
    $settings = $this->createMock(SettingsController::class);
    $settings->method('get')->willReturnMap([
      ['mta.mailpoet_api_key_state.data.tier', null, 1],
      ['mta.mailpoet_api_key_state.data.mailpoet_logo_in_emails', null, false],
      ['mta.mailpoet_api_key_state.data.detailed_analytics', null, true],
      ['mta.mailpoet_api_key_state.data.automation_steps', null, 1],
      ['mta.mailpoet_api_key_state.data.segment_filters', null, 1],
    ]);
    $capabilitiesManager = new CapabilitiesManager($settings);
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
    $capabilitiesManager = new CapabilitiesManager($settings);
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
    $capabilitiesManager = new CapabilitiesManager($settings);
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
    $capabilitiesManager = new CapabilitiesManager($settings);
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
    $capabilitiesManager = new CapabilitiesManager($settings);
    $capabilities = $capabilitiesManager->getCapabilities();
    verify($capabilities->getMailpoetLogoInEmails())->false();
    verify($capabilities->getDetailedAnalytics())->true();
    verify($capabilities->getAutomationSteps())->equals(2);
    verify($capabilities->getSegmentFilters())->equals(0);
  }
}
