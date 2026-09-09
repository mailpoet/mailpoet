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
   * @param string|null $storedConsent What the subscriber already has on record,
   *                                   or null when there is no row yet.
   * @return array<string, string>
   */
  public function getConsentData(bool $granted, string $method, string $copy, bool $isNewSubscriber, ?string $storedConsent = null): array {
    if (!$this->isCaptureEnabled()) {
      return [];
    }
    if (!$this->mayRecord($granted, $isNewSubscriber, $storedConsent)) {
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
   * Whether a collection point may write this answer.
   *
   * These points are all reachable without proving who you are: a comment, a
   * subscription form, a registration and a checkout all identify the subscriber
   * by an email address the visitor typed, and nothing verifies that the address
   * is theirs. So an answer that would replace a choice already on record is
   * dropped. Changing an answer already given is done from the manage
   * subscription page, which checks a link token first.
   *
   * Someone who has never been asked can still answer here, and a subscriber
   * being created now records either answer, since neither overwrites anything.
   * An unticked box on a signup form is still not a withdrawal of consent given
   * somewhere else.
   */
  private function mayRecord(bool $granted, bool $isNewSubscriber, ?string $storedConsent): bool {
    if ($isNewSubscriber) {
      return true;
    }
    if (
      $storedConsent === SubscriberEntity::TRACKING_CONSENT_GRANTED
      || $storedConsent === SubscriberEntity::TRACKING_CONSENT_DENIED
    ) {
      return false;
    }
    return $granted;
  }

  /**
   * Applies consent straight to an entity, for paths that persist the
   * subscriber themselves instead of going through SubscriberSaveController
   * (WooCommerce checkout).
   */
  public function applyToSubscriber(SubscriberEntity $subscriber, bool $granted, string $method, string $copy, bool $isNewSubscriber): void {
    $data = $this->getConsentData($granted, $method, $copy, $isNewSubscriber, $subscriber->getTrackingConsent());
    if (!$data) {
      return;
    }
    $subscriber->setTrackingConsent($data[self::FIELD_ID], $method, $copy);
  }

  /**
   * Consent fields for a collection point that knows the subscriber only by an
   * email address someone typed. One lookup answers both questions the decision
   * needs: whether a row exists, and what it already says.
   *
   * @return array<string, string>
   */
  public function getConsentDataForEmail(bool $granted, string $method, string $copy, ?string $email): array {
    $subscriber = ($email === null || $email === '')
      ? null
      : $this->subscribersRepository->findOneBy(['email' => $email]);

    return $this->getConsentData(
      $granted,
      $method,
      $copy,
      !$subscriber instanceof SubscriberEntity,
      $subscriber instanceof SubscriberEntity ? $subscriber->getTrackingConsent() : null
    );
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
