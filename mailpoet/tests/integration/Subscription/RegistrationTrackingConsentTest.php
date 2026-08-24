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

  /** @var int[] */
  private $createdWpUserIds = [];

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
    // These tests create real WP users so the whole user_register chain runs. WP users
    // are not truncated between tests, so leaving them behind would skew any later test
    // that counts or syncs WP users.
    foreach ($this->createdWpUserIds as $userId) {
      wp_delete_user($userId);
    }
    $this->createdWpUserIds = [];
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

  public function testAConsentOnlyRegistrantIsRecordedEvenWithoutSubscribing() {
    // The gap this fixes: onRegister() only reads tracking_consent inside the
    // subscribe branch, so someone who allowed tracking without ticking
    // "add me to your mailing list" had their answer thrown away. These tests
    // go through wp_insert_user(), so the real user_register chain runs:
    // capture at priority 5, WP-user sync at 6, consent at 7.
    $this->askEveryone();
    $email = 'reg-consent-only@example.com';
    $_POST['mailpoet'] = ['tracking_consent' => true];

    $this->registerWpUser($email);

    $this->entityManager->clear();
    $subscriber = $this->getSubscriber($email);
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    verify($subscriber->getTrackingConsentMethod())
      ->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_REGISTRATION);
  }

  public function testAConsentOnlyRegistrantCanDecline() {
    $this->askEveryone();
    $email = 'reg-consent-only-declined@example.com';
    $_POST['mailpoet'] = ['tracking_consent' => false];

    $this->registerWpUser($email);

    $this->entityManager->clear();
    verify($this->getSubscriber($email)->getTrackingConsent())
      ->equals(SubscriberEntity::TRACKING_CONSENT_DENIED);
  }

  public function testAConsentOnlyRegistrationDoesNotDowngradeAnExistingSubscriber() {
    // Same unticked box, different history: this address was already on the
    // list with a grant, so registering again must not revoke it.
    $this->askEveryone();
    $email = 'reg-consent-only-existing@example.com';
    $existing = (new SubscriberFactory())->withEmail($email)->create();
    $existing->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_GRANTED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_MANAGE_PAGE,
      'given on the manage page'
    );
    $this->subscribersRepository->flush();

    $_POST['mailpoet'] = ['tracking_consent' => false];
    $this->registerWpUser($email);

    $this->entityManager->clear();
    verify($this->getSubscriber($email)->getTrackingConsent())
      ->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
  }

  public function testItRecordsNothingWhenNoConsentFieldWasPosted() {
    $this->askEveryone();
    $email = 'reg-no-field@example.com';
    $_POST['mailpoet'] = ['subscribe_on_register' => true];

    $this->registerWpUser($email);

    $this->entityManager->clear();
    verify($this->getSubscriber($email)->getTrackingConsent())
      ->equals(SubscriberEntity::TRACKING_CONSENT_UNKNOWN);
  }

  /**
   * Creates a real WP user, which fires user_register and therefore the whole
   * chain this change hooks into.
   */
  private function registerWpUser(string $email): int {
    $userId = wp_insert_user([
      'user_login' => substr(md5($email), 0, 20),
      'user_email' => $email,
      'user_pass' => 'password',
    ]);
    $this->assertIsInt($userId);
    $this->createdWpUserIds[] = $userId;
    return $userId;
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
