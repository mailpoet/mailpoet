<?php declare(strict_types = 1);

namespace MailPoet\Util\License\Features;

use MailPoet\Settings\SettingsController;
use MailPoet\Util\License\Features\Data\Capabilities;

class CapabilitiesManager {
  // Settings mapping
  const MSS_TIER_SETTING_KEY = 'mta.mailpoet_api_key_state.data.tier';
  const MSS_MAILPOET_LOGO_IN_EMAILS_SETTING_KEY = 'mta.mailpoet_api_key_state.data.mailpoet_logo_in_emails';
  const MSS_DETAILED_ANALYTICS_SETTING_KEY = 'mta.mailpoet_api_key_state.data.detailed_analytics';
  const MSS_AUTOMATION_STEPS_SETTING_KEY = 'mta.mailpoet_api_key_state.data.automation_steps';
  const MSS_SEGMENT_FILTERS_SETTING_KEY = 'mta.mailpoet_api_key_state.data.segment_filters';
  // Product capabilities mapping
  const MIN_TIER_LOGO_NOT_REQUIRED = 1;
  const MIN_TIER_ANALYTICS_ENABLED = 1;
  const MIN_TIER_UNLIMITED_AUTOMATION_STEPS = 2;
  const MIN_TIER_UNLIMITED_SEGMENT_FILTERS = 2;

  private SettingsController $settings;
  private ?int $tier;

  public function __construct(
    SettingsController $settings
  ) {
    $this->settings = $settings;
    $this->tier = $this->getTier();
  }

  private function getTier(): ?int {
    $tier = $this->settings->get(self::MSS_TIER_SETTING_KEY);
    return isset($tier) ? (int)$tier : null;
  }

  private function isMailpoetLogoInEmailsRequired(): bool {
    $mailpoetLogoInEmails = $this->settings->get(self::MSS_MAILPOET_LOGO_IN_EMAILS_SETTING_KEY);

    if (!isset($this->tier) && !isset($mailpoetLogoInEmails)) {
      return true;
    }

    // Allow for less restrictive individual capability to take precedence
    if (isset($mailpoetLogoInEmails) && (bool)$mailpoetLogoInEmails === false) {
      return false;
    }

    return (isset($this->tier) && $this->tier < self::MIN_TIER_LOGO_NOT_REQUIRED) || (isset($mailpoetLogoInEmails) && (bool)$mailpoetLogoInEmails === true);
  }

  private function isDetailedAnalyticsEnabled(): bool {
    $detailedAnalytics = $this->settings->get(self::MSS_DETAILED_ANALYTICS_SETTING_KEY);

    if (!isset($this->tier) && !isset($detailedAnalytics)) {
      return false;
    }

    // Allow for less restrictive individual capability to take precedence
    if (isset($detailedAnalytics) && (bool)$detailedAnalytics === true) {
      return true;
    }

    return (isset($this->tier) && $this->tier >= self::MIN_TIER_ANALYTICS_ENABLED) || (isset($detailedAnalytics) && (bool)$detailedAnalytics === true);
  }

  private function getAutomationStepsLimit(): int {
    $automationSteps = $this->settings->get(self::MSS_AUTOMATION_STEPS_SETTING_KEY);

    if (!isset($this->tier) && !isset($automationSteps)) {
      return 1;
    }

    $stepsFromTier = isset($this->tier) && $this->tier >= self::MIN_TIER_UNLIMITED_AUTOMATION_STEPS ? 0 : 1; //  0  is unlimited

    // Allow for less restrictive individual capability to take precedence
     return (isset($automationSteps) && ((int)$automationSteps === 0 || (int)$automationSteps > $stepsFromTier)) ? (int)$automationSteps : $stepsFromTier;
  }

  private function getSegmentFiltersLimit(): int {
    $segmentFilters = $this->settings->get(self::MSS_SEGMENT_FILTERS_SETTING_KEY);

    if (!isset($this->tier) && !isset($segmentFilters)) {
      return 1;
    }

    $segmentFiltersFromTier = isset($this->tier) && $this->tier >= self::MIN_TIER_UNLIMITED_SEGMENT_FILTERS ? 0 : 1; //  0  is unlimited
    // Allow for less restrictive individual capability to take precedence
    return (isset($segmentFilters) && ((int)$segmentFilters === 0 || (int)$segmentFilters > $segmentFiltersFromTier)) ? (int)$segmentFilters : $segmentFiltersFromTier;
  }

  public function getCapabilities(): Capabilities {
    return new Capabilities(
      $this->isMailpoetLogoInEmailsRequired(),
      $this->isDetailedAnalyticsEnabled(),
      $this->getAutomationStepsLimit(),
      $this->getSegmentFiltersLimit(),
    );
  }
}
