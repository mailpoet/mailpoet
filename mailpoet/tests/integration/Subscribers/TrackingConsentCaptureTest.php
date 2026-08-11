<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\ManageSubscriptionFormRenderer;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class TrackingConsentCaptureTest extends \MailPoetTest {
  private TrackingConsentCapture $capture;

  private SettingsController $settings;

  public function _before() {
    parent::_before();
    $this->capture = $this->diContainer->get(TrackingConsentCapture::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
  }

  private function askEveryone(): void {
    $this->settings->set(
      TrackingConsentController::SETTING_SUBSCRIBER_CHOICE,
      TrackingConsentController::CHOICE_ASK_ALL
    );
  }

  public function testItCapturesNothingWhenTheSiteTracksEveryone() {
    // Default state: no control is shown, so nothing may be recorded.
    verify($this->capture->isCaptureEnabled())->false();
    verify($this->capture->getConsentData(true, SubscriberEntity::TRACKING_CONSENT_METHOD_FORM, 'copy', true))->equals([]);
  }

  public function testItRecordsAGrantWithServerStampedProof() {
    $this->askEveryone();
    $data = $this->capture->getConsentData(true, SubscriberEntity::TRACKING_CONSENT_METHOD_FORM, 'Allow tracking', true);

    verify($data['tracking_consent'])->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    verify($data['tracking_consent_method'])->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_FORM);
    verify($data['tracking_consent_copy'])->equals('Allow tracking');
  }

  public function testItRecordsADeclineOnlyForANewSubscriber() {
    $this->askEveryone();

    $newSubscriber = $this->capture->getConsentData(false, SubscriberEntity::TRACKING_CONSENT_METHOD_FORM, 'Allow tracking', true);
    verify($newSubscriber['tracking_consent'])->equals(SubscriberEntity::TRACKING_CONSENT_DENIED);

    // An existing subscriber who leaves the box unticked keeps whatever they
    // chose before — a signup form is not a place to withdraw consent.
    $existing = $this->capture->getConsentData(false, SubscriberEntity::TRACKING_CONSENT_METHOD_FORM, 'Allow tracking', false);
    verify($existing)->equals([]);
  }

  public function testItStillRecordsAGrantForAnExistingSubscriber() {
    $this->askEveryone();
    $data = $this->capture->getConsentData(true, SubscriberEntity::TRACKING_CONSENT_METHOD_FORM, 'Allow tracking', false);
    verify($data['tracking_consent'])->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
  }

  public function testItNeverDowngradesAnExistingGrantOnAnEntity() {
    $this->askEveryone();
    $subscriber = new SubscriberEntity();
    $subscriber->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_GRANTED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_MANAGE_PAGE,
      'old copy'
    );

    $this->capture->applyToSubscriber(
      $subscriber,
      false,
      SubscriberEntity::TRACKING_CONSENT_METHOD_WOOCOMMERCE_CHECKOUT,
      'new copy',
      false
    );

    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    verify($subscriber->getTrackingConsentMethod())->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_MANAGE_PAGE);
  }

  public function testItDefaultsToTheManagePageCopy() {
    verify($this->capture->getCopy(SubscriberEntity::TRACKING_CONSENT_METHOD_FORM))
      ->equals(ManageSubscriptionFormRenderer::getTrackingConsentCopy());
  }

  public function testItPrefersASurfaceOverrideOverTheDefault() {
    verify($this->capture->getCopy(SubscriberEntity::TRACKING_CONSENT_METHOD_FORM, 'Custom wording'))
      ->equals('Custom wording');
    // A blank override is not a choice, so the default still applies.
    verify($this->capture->getCopy(SubscriberEntity::TRACKING_CONSENT_METHOD_FORM, '  '))
      ->equals(ManageSubscriptionFormRenderer::getTrackingConsentCopy());
  }

  public function testTheFilterChangesTheCopyAndReceivesTheMethod() {
    $seenMethod = null;
    $callback = function ($copy, $method) use (&$seenMethod) {
      $seenMethod = $method;
      return "filtered: $copy";
    };
    add_filter('mailpoet_tracking_consent_copy', $callback, 10, 2);

    $copy = $this->capture->getCopy(SubscriberEntity::TRACKING_CONSENT_METHOD_COMMENT);

    remove_filter('mailpoet_tracking_consent_copy', $callback, 10);

    verify($copy)->equals('filtered: ' . ManageSubscriptionFormRenderer::getTrackingConsentCopy());
    verify($seenMethod)->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_COMMENT);
  }

  public function testItStripsClientSuppliedProof() {
    $data = $this->capture->stripPostedProof([
      'tracking_consent' => 'granted',
      'tracking_consent_method' => 'forged',
      'tracking_consent_copy' => 'forged copy',
      'email' => 'a@example.com',
    ]);

    verify(isset($data['tracking_consent_method']))->false();
    verify(isset($data['tracking_consent_copy']))->false();
    verify($data['tracking_consent'])->equals('granted');
    verify($data['email'])->equals('a@example.com');
  }

  public function testItDetectsAnExistingSubscriberByEmail() {
    $email = 'capture-existing@example.com';
    verify($this->capture->isNewSubscriber($email))->true();

    (new SubscriberFactory())->withEmail($email)->create();

    verify($this->capture->isNewSubscriber($email))->false();
  }
}
