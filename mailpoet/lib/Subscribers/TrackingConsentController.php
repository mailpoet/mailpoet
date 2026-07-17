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
      case SubscriberEntity::TRACKING_CONSENT_UNKNOWN:
        // 'unknown' = we never asked. Sites under the opt-in regime (new FR
        // and IT contacts) set this to false; everyone else keeps today's
        // behaviour.
        return $this->shouldTrackUnknownConsent();
      case SubscriberEntity::TRACKING_CONSENT_DENIED:
      default:
        // Denied — and, defensively, any unrecognised value — is never tracked.
        // Storage is constrained to the three states (Assert\Choice on the
        // entity), so 'default' should be unreachable; deny rather than fall
        // back to the permissive unknown path for a compliance-critical flag.
        return false;
    }
  }

  /**
   * Whether subscribers who have never been asked ('unknown' consent) may be
   * treated as trackable. Default true (existing behaviour). When false (strict
   * opt-in mode), only subscribers who explicitly granted consent are tracked.
   *
   * Background jobs that infer intent from missing engagement (inactive sweep,
   * resend to non-openers, re-engagement) use this so that, in strict mode,
   * untracked 'unknown' subscribers are excluded the same way 'denied' ones
   * are — otherwise their frozen engagement would wrongly mark them disengaged.
   */
  public function shouldTrackUnknownConsent(): bool {
    return (bool)$this->settings->get(self::SETTING_TRACK_UNKNOWN, true);
  }
}
