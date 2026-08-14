<?php declare(strict_types = 1);

namespace MailPoet\Subscription;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscribers\TrackingConsentController;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

/**
 * Tracking consent captured on the WordPress registration form (CNIL/Garante).
 * The consent checkbox is separate from "add me to your mailing list".
 */
class RegistrationTrackingConsentTest extends \MailPoetTest {
  private Registration $registration;

  private SubscribersRepository $subscribersRepository;

  private SettingsController $settings;

  public function _before() {
    parent::_before();
    $this->registration = $this->diContainer->get(Registration::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->set('signup_confirmation.enabled', false);
    $this->settings->set('subscribe.on_register.segments', [1]);
  }

  public function _after() {
    unset($_POST['mailpoet']);
    parent::_after();
  }

  private function askEveryone(): void {
    $this->settings->set(
      TrackingConsentController::SETTING_SUBSCRIBER_CHOICE,
      TrackingConsentController::CHOICE_ASK_ALL
    );
  }

  public function testItRecordsConsentTickedAtRegistration() {
    $this->askEveryone();
    $_POST['mailpoet'] = ['subscribe_on_register' => true, 'tracking_consent' => true];
    $this->registration->onRegister(new \WP_Error(), 'login', 'reg-consent@example.com');

    $subscriber = $this->getSubscriber('reg-consent@example.com');
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    verify($subscriber->getTrackingConsentMethod())
      ->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_REGISTRATION);
  }

  public function testItRecordsADeclineForANewUser() {
    $this->askEveryone();
    $_POST['mailpoet'] = ['subscribe_on_register' => true, 'tracking_consent' => false];
    $this->registration->onRegister(new \WP_Error(), 'login', 'reg-declined@example.com');

    $subscriber = $this->getSubscriber('reg-declined@example.com');
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_DENIED);
  }

  public function testSubscribingDoesNotImplyConsent() {
    // The two checkboxes are independent: ticking only "add me to your mailing
    // list" must leave tracking consent ungranted.
    $this->askEveryone();
    $_POST['mailpoet'] = ['subscribe_on_register' => true];
    $this->registration->onRegister(new \WP_Error(), 'login', 'reg-unbundled@example.com');

    $subscriber = $this->getSubscriber('reg-unbundled@example.com');
    verify($subscriber->getTrackingConsent())->notEquals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
  }

  public function testItRecordsNothingWhenTheSiteTracksEveryone() {
    $_POST['mailpoet'] = ['subscribe_on_register' => true, 'tracking_consent' => true];
    $this->registration->onRegister(new \WP_Error(), 'login', 'reg-gated@example.com');

    $subscriber = $this->getSubscriber('reg-gated@example.com');
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_UNKNOWN);
  }

  public function testItDoesNotDowngradeAnExistingSubscriber() {
    $this->askEveryone();
    $email = 'reg-existing@example.com';
    $existing = (new SubscriberFactory())->withEmail($email)->create();
    $existing->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_GRANTED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_MANAGE_PAGE,
      'given on the manage page'
    );
    $this->subscribersRepository->flush();

    $_POST['mailpoet'] = ['subscribe_on_register' => true, 'tracking_consent' => false];
    $this->registration->onRegister(new \WP_Error(), 'login', $email);

    $this->entityManager->clear();
    verify($this->getSubscriber($email)->getTrackingConsent())
      ->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
  }

  public function testTheFieldIsHiddenUntilTheSiteAsks() {
    ob_start();
    $this->registration->extendForm();
    $withoutAsking = (string)ob_get_clean();
    verify($withoutAsking)->stringNotContainsString('mailpoet[tracking_consent]');

    $this->askEveryone();
    ob_start();
    $this->registration->extendForm();
    $whenAsking = (string)ob_get_clean();
    verify($whenAsking)->stringContainsString('mailpoet[tracking_consent]');
    verify($whenAsking)->stringNotContainsString('checked');
  }

  private function getSubscriber(string $email): SubscriberEntity {
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $email]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    return $subscriber;
  }
}
