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
  const SETTING_SUBSCRIBER_CHOICE = 'tracking.consent.subscriber_choice';

  /** Track everyone, don't ask. Default: no recipient-facing controls anywhere. */
  const CHOICE_TRACK_ALL = 'track_all';

  /** Ask new subscribers. Everyone already on the list keeps being tracked. */
  const CHOICE_ASK_NEW = 'ask_new';

  /** Ask everyone. Nobody is tracked until they allow it. */
  const CHOICE_ASK_ALL = 'ask_all';

  const CHOICES = [
    self::CHOICE_TRACK_ALL,
    self::CHOICE_ASK_NEW,
    self::CHOICE_ASK_ALL,
  ];

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
   * The site's "Subscriber choice" state. Anything unrecognised falls back to
   * the default rather than to a stricter or looser state, so a corrupt value
   * cannot silently change what recipients are shown.
   */
  public function getSubscriberChoice(): string {
    $choice = (string)$this->settings->get(self::SETTING_SUBSCRIBER_CHOICE, self::CHOICE_TRACK_ALL);
    return in_array($choice, self::CHOICES, true) ? $choice : self::CHOICE_TRACK_ALL;
  }

  /**
   * Whether recipient-facing consent controls may be shown at all: the
   * manage-subscription checkbox, and the auto-added form/checkout checkbox and
   * footer opt-out link once those exist.
   *
   * Internal handling is deliberately NOT gated on this. Subscribers who are
   * already denied stay untracked, and import/export and stats keep honouring
   * consent, whatever the site has chosen here.
   */
  public function areSubscriberControlsVisible(): bool {
    return $this->getSubscriberChoice() !== self::CHOICE_TRACK_ALL;
  }

  /**
   * Whether subscribers who have never been asked ('unknown' consent) may be
   * treated as trackable. True unless the site asks everyone, so existing lists
   * keep today's behaviour until the site deliberately opts into strict consent.
   *
   * Background jobs that infer intent from missing engagement (inactive sweep,
   * resend to non-openers, re-engagement) use this so that, when asking
   * everyone, untracked 'unknown' subscribers are excluded the same way
   * 'denied' ones are — otherwise their frozen engagement would wrongly mark
   * them disengaged.
   */
  public function shouldTrackUnknownConsent(): bool {
    return $this->getSubscriberChoice() !== self::CHOICE_ASK_ALL;
  }
}
