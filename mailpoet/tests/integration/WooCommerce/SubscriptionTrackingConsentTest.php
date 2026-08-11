<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscribers\TrackingConsentController;
use MailPoet\Subscription\ManageSubscriptionFormRenderer;

/**
 * Tracking consent captured at WooCommerce checkout (CNIL/Garante).
 *
 * The checkout consent control is a second, independent checkbox: ticking the
 * marketing opt-in must never imply consent to open and click tracking.
 *
 * @group woo
 */
class SubscriptionTrackingConsentTest extends \MailPoetTest {
  private Subscription $subscription;

  private SettingsController $settings;

  private SubscribersRepository $subscribersRepository;

  private SubscriberEntity $subscriber;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->subscription = $this->diContainer->get(Subscription::class);

    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('checkout-consent@example.com');
    $subscriber->setIsWoocommerceUser(true);
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();
    $this->subscriber = $subscriber;

    $this->settings->set('signup_confirmation.enabled', false);
  }

  private function askEveryone(): void {
    $this->settings->set(
      TrackingConsentController::SETTING_SUBSCRIBER_CHOICE,
      TrackingConsentController::CHOICE_ASK_ALL
    );
  }

  public function testItRecordsConsentTickedAtCheckout() {
    $this->askEveryone();
    $this->subscription->handleSubscriberOptin($this->subscriber, true, true);

    verify($this->subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    verify($this->subscriber->getTrackingConsentMethod())
      ->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_WOOCOMMERCE_CHECKOUT);
    verify($this->subscriber->getTrackingConsentCopy())
      ->equals(ManageSubscriptionFormRenderer::getTrackingConsentCopy());
  }

  public function testConsentIsIndependentOfTheMarketingOptIn() {
    $this->askEveryone();

    // Opting into marketing must not grant tracking consent on its own.
    $this->subscription->handleSubscriberOptin($this->subscriber, true, false);
    verify($this->subscriber->getTrackingConsent())->notEquals(SubscriberEntity::TRACKING_CONSENT_GRANTED);

    // And consent can be given without opting into marketing at all.
    $other = new SubscriberEntity();
    $other->setEmail('checkout-consent-only@example.com');
    $other->setIsWoocommerceUser(true);
    $other->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscribersRepository->persist($other);
    $this->subscribersRepository->flush();

    $this->subscription->handleSubscriberOptin($other, false, true);
    verify($other->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
  }

  public function testItRecordsNothingWhenTheSiteTracksEveryone() {
    // Default state: no consent control is shown at checkout, so a posted
    // value must be ignored.
    $this->subscription->handleSubscriberOptin($this->subscriber, true, true);
    verify($this->subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_UNKNOWN);
  }

  public function testItNeverDowngradesAWooCommerceCustomer() {
    // Checkout always acts on an existing subscriber row, so an unticked box
    // leaves an earlier choice alone rather than revoking it.
    $this->askEveryone();
    $this->subscriber->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_GRANTED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_MANAGE_PAGE,
      'given on the manage page'
    );
    $this->subscribersRepository->flush();

    $this->subscription->handleSubscriberOptin($this->subscriber, true, false);

    verify($this->subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    verify($this->subscriber->getTrackingConsentMethod())
      ->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_MANAGE_PAGE);
  }

  public function testItPersistsConsentEvenWhenTheCustomerDoesNotSubscribe() {
    $this->askEveryone();
    $this->subscription->handleSubscriberOptin($this->subscriber, false, true);

    $this->entityManager->clear();
    $reloaded = $this->subscribersRepository->findOneBy(['email' => 'checkout-consent@example.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $reloaded);
    verify($reloaded->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
  }

  public function testTheCheckoutFieldIsHiddenUntilTheSiteAsks() {
    $this->settings->set(Subscription::OPTIN_ENABLED_SETTING_NAME, true);

    ob_start();
    $this->subscription->extendWooCommerceCheckoutForm();
    $withoutAsking = (string)ob_get_clean();
    verify($withoutAsking)->stringNotContainsString(Subscription::CHECKOUT_TRACKING_CONSENT_INPUT_NAME);

    $this->askEveryone();
    ob_start();
    $this->subscription->extendWooCommerceCheckoutForm();
    $whenAsking = (string)ob_get_clean();
    verify($whenAsking)->stringContainsString(Subscription::CHECKOUT_TRACKING_CONSENT_INPUT_NAME);
    // A pre-ticked consent box is not valid consent (CJEU Planet49).
    verify($whenAsking)->stringNotContainsString('checked=\'checked\'');
  }
}
