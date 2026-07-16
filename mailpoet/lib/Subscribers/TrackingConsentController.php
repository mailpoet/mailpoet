<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;

/**
 * The single answer to "may we track this subscriber?" (CNIL/Garante).
 *
 * Used by BOTH enforcement points — send-time pixel removal and serve-time
 * recording suppression — so they can never drift apart.
 */
class TrackingConsentController {
  const SETTING_TRACK_UNKNOWN = 'tracking.consent.track_unknown';

  private SettingsController $settings;

  private TrackingConfig $trackingConfig;

  public function __construct(
    SettingsController $settings,
    TrackingConfig $trackingConfig
  ) {
    $this->settings = $settings;
    $this->trackingConfig = $trackingConfig;
  }

  public function isTrackingAllowed(SubscriberEntity $subscriber): bool {
    // The existing global switch still wins. Settings > Advanced > basic
    // already means "no engagement tracking at all" for the whole site.
    if (!$this->trackingConfig->isEmailTrackingEnabled()) {
      return false;
    }

    switch ($subscriber->getTrackingConsent()) {
      case SubscriberEntity::TRACKING_CONSENT_GRANTED:
        return true;
      case SubscriberEntity::TRACKING_CONSENT_DENIED:
        return false;
      default:
        // 'unknown' = we never asked. Sites under the opt-in regime (new FR
        // and IT contacts) set this to false; everyone else keeps today's
        // behaviour.
        return (bool)$this->settings->get(self::SETTING_TRACK_UNKNOWN, true);
    }
  }
}
