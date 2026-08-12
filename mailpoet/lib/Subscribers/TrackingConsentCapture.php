<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscription\ManageSubscriptionFormRenderer;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Captures tracking consent at signup surfaces (CNIL/Garante).
 *
 * Consent must be its own control everywhere and must never be derived from the
 * general subscribe opt-in, so every collection point asks separately and comes
 * through here to decide what to store. Keeping the decision in one place is
 * what stops the four surfaces drifting apart.
 */
class TrackingConsentCapture {
  /**
   * The field id the consent checkbox uses on both the manage-subscription page
   * and subscription forms, and therefore the key the posted value arrives on.
   */
  const FIELD_ID = 'tracking_consent';

  private TrackingConsentController $trackingConsentController;

  private SubscribersRepository $subscribersRepository;

  private WPFunctions $wp;

  public function __construct(
    TrackingConsentController $trackingConsentController,
    SubscribersRepository $subscribersRepository,
    WPFunctions $wp
  ) {
    $this->trackingConsentController = $trackingConsentController;
    $this->subscribersRepository = $subscribersRepository;
    $this->wp = $wp;
  }

  /**
   * Whether a consent control may be shown at a collection point at all. Sites
   * that track everyone without asking never show one, so nobody is prompted
   * about a choice the site owner does not offer.
   */
  public function isCaptureEnabled(): bool {
    return $this->trackingConsentController->areSubscriberControlsVisible();
  }

  /**
   * The wording shown next to the checkbox, and therefore the wording stored as
   * proof. Always read it through here so the stored copy is what was rendered,
   * including when a site owner has filtered it.
   *
   * @param string $method One of SubscriberEntity::TRACKING_CONSENT_METHOD_*.
   * @param string|null $override Copy configured for this surface, if any.
   */
  public function getCopy(string $method, ?string $override = null): string {
    $copy = ($override !== null && trim($override) !== '')
      ? $override
      : ManageSubscriptionFormRenderer::getTrackingConsentCopy();

    /**
     * Filters the tracking-consent wording shown to subscribers at a
     * collection point.
     *
     * Whatever this returns is both displayed and stored as the proof of what
     * the subscriber agreed to, so changing it changes the consent record for
     * everyone who consents afterwards. Records already stored keep the wording
     * they were given.
     *
     * @param string $copy   The wording about to be shown.
     * @param string $method The collection point, one of the
     *                       SubscriberEntity::TRACKING_CONSENT_METHOD_* values.
     */
    $filtered = $this->wp->applyFilters('mailpoet_tracking_consent_copy', $copy, $method);
    return is_string($filtered) && trim($filtered) !== '' ? $filtered : $copy;
  }

  /**
   * Consent fields to merge into subscriber data on a signup path, or an empty
   * array when nothing should be written.
   *
   * Granting is always recorded. Declining is recorded only for a subscriber
   * being created now: someone already on the list who submits a form again
   * keeps whatever they chose before, because an unticked box on a signup form
   * is not a withdrawal of consent given somewhere else.
   *
   * @return array<string, string>
   */
  public function getConsentData(bool $granted, string $method, string $copy, bool $isNewSubscriber): array {
    if (!$this->isCaptureEnabled()) {
      return [];
    }
    if (!$granted && !$isNewSubscriber) {
      return [];
    }
    return [
      self::FIELD_ID => $granted
        ? SubscriberEntity::TRACKING_CONSENT_GRANTED
        : SubscriberEntity::TRACKING_CONSENT_DENIED,
      'tracking_consent_method' => $method,
      'tracking_consent_copy' => $copy,
    ];
  }

  /**
   * Applies consent straight to an entity, for paths that persist the
   * subscriber themselves instead of going through SubscriberSaveController
   * (WooCommerce checkout).
   */
  public function applyToSubscriber(SubscriberEntity $subscriber, bool $granted, string $method, string $copy, bool $isNewSubscriber): void {
    $data = $this->getConsentData($granted, $method, $copy, $isNewSubscriber);
    if (!$data) {
      return;
    }
    $subscriber->setTrackingConsent($data[self::FIELD_ID], $method, $copy);
  }

  /**
   * Whether this email would be a newly created subscriber. Signup paths look
   * the subscriber up again inside SubscriberActions::subscribe(), so the
   * answer has to be taken before that call.
   */
  public function isNewSubscriber(?string $email): bool {
    if ($email === null || $email === '') {
      return true;
    }
    return $this->subscribersRepository->findOneBy(['email' => $email]) === null;
  }
}
