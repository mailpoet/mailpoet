<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use Codeception\Util\Fixtures;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\ManageSubscriptionFormRenderer;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

/**
 * Tracking consent captured by the subscription form's own checkbox
 * (CNIL/Garante). The consent control is separate from the subscribe opt-in,
 * so nothing here may infer one from the other.
 */
class SubscriberSubscribeControllerTrackingConsentTest extends \MailPoetTest {
  private SettingsController $settings;

  private SubscriberSubscribeController $subscribeController;

  private SubscribersRepository $subscribersRepository;

  private SegmentsRepository $segmentsRepository;

  private FieldNameObfuscator $obfuscator;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->obfuscator = $this->diContainer->get(FieldNameObfuscator::class);
    $this->subscribeController = $this->diContainer->get(SubscriberSubscribeController::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->settings->set('signup_confirmation.enabled', false);
  }

  private function askEveryone(): void {
    $this->settings->set(
      TrackingConsentController::SETTING_SUBSCRIBER_CHOICE,
      TrackingConsentController::CHOICE_ASK_ALL
    );
  }

  public function testItRecordsConsentWhenTheBoxIsTicked() {
    $this->askEveryone();
    $email = 'consent-granted' . rand(0, 100000) . '@example.com';
    $this->submit($email, '1');

    $subscriber = $this->getSubscriber($email);
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    verify($subscriber->getTrackingConsentMethod())->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_FORM);
    verify($subscriber->getTrackingConsentCopy())->equals(ManageSubscriptionFormRenderer::getTrackingConsentCopy());
  }

  public function testItRecordsADeclineForANewSubscriber() {
    $this->askEveryone();
    $email = 'consent-declined' . rand(0, 100000) . '@example.com';
    $this->submit($email, '0');

    $subscriber = $this->getSubscriber($email);
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_DENIED);
    verify($subscriber->getTrackingConsentMethod())->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_FORM);
  }

  public function testItDoesNotDowngradeAnExistingSubscriberWhoLeavesTheBoxUnticked() {
    $this->askEveryone();
    $email = 'consent-existing' . rand(0, 100000) . '@example.com';
    $existing = (new SubscriberFactory())->withEmail($email)->create();
    $existing->setTrackingConsent(
      SubscriberEntity::TRACKING_CONSENT_GRANTED,
      SubscriberEntity::TRACKING_CONSENT_METHOD_MANAGE_PAGE,
      'consent given on the manage page'
    );
    $this->subscribersRepository->flush();

    $this->submit($email, '0');

    $this->entityManager->clear();
    $subscriber = $this->getSubscriber($email);
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    verify($subscriber->getTrackingConsentMethod())->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_MANAGE_PAGE);
  }

  public function testItRecordsNothingWhenTheSiteTracksEveryone() {
    // Default state. Even a crafted post carrying the field must be ignored,
    // because no consent control was ever shown.
    $email = 'consent-gated' . rand(0, 100000) . '@example.com';
    $this->submit($email, '1');

    $subscriber = $this->getSubscriber($email);
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_UNKNOWN);
    verify($subscriber->getTrackingConsentMethod())->equals(null);
  }

  public function testItIgnoresClientSuppliedProof() {
    $this->askEveryone();
    $email = 'consent-forged' . rand(0, 100000) . '@example.com';
    $this->submit($email, '1', [
      'tracking_consent_method' => 'forged_method',
      'tracking_consent_copy' => 'wording the subscriber never saw',
    ]);

    $subscriber = $this->getSubscriber($email);
    verify($subscriber->getTrackingConsentMethod())->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_FORM);
    verify($subscriber->getTrackingConsentCopy())->equals(ManageSubscriptionFormRenderer::getTrackingConsentCopy());
  }

  public function testItLeavesConsentUnknownWhenTheFormHasNoConsentBlock() {
    $this->askEveryone();
    $email = 'consent-noblock' . rand(0, 100000) . '@example.com';
    $segment = $this->segmentsRepository->createOrUpdate('Consent segment ' . rand(0, 100000));
    $form = $this->createForm($segment, false);

    $this->subscribeController->subscribe([
      $this->obfuscator->obfuscate('email') => $email,
      $this->obfuscator->obfuscate('segments') => [$segment->getId()],
      'form_id' => $form->getId(),
      'tracking_consent' => '1',
    ]);

    $subscriber = $this->getSubscriber($email);
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_UNKNOWN);
  }

  public function testConsentIsIndependentOfTheSubscribeOptIn() {
    // Subscribing and consenting to tracking are two separate decisions: a
    // subscriber who signs up must not be treated as having consented.
    $this->askEveryone();
    $email = 'consent-unbundled' . rand(0, 100000) . '@example.com';
    $this->submit($email, '0');

    $subscriber = $this->getSubscriber($email);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    verify($subscriber->getTrackingConsent())->notEquals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
  }

  private function submit(string $email, string $consent, array $extra = []): void {
    $segment = $this->segmentsRepository->createOrUpdate('Consent segment ' . rand(0, 100000));
    $form = $this->createForm($segment, true);

    $this->subscribeController->subscribe(array_merge([
      $this->obfuscator->obfuscate('email') => $email,
      $this->obfuscator->obfuscate('segments') => [$segment->getId()],
      'form_id' => $form->getId(),
      'tracking_consent' => $consent,
    ], $extra));
  }

  private function getSubscriber(string $email): SubscriberEntity {
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $email]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    return $subscriber;
  }

  private function createForm(SegmentEntity $segment, bool $withConsentBlock): FormEntity {
    $form = new FormEntity('Form' . rand(0, 100000));
    $body = Fixtures::get('form_body_template');
    $body[] = [
      'type' => 'segment',
      'params' => ['values' => [['id' => $segment->getId()]]],
    ];
    if ($withConsentBlock) {
      $body[] = [
        'type' => 'checkbox',
        'id' => TrackingConsentCapture::FIELD_ID,
        'name' => 'Tracking consent',
        'params' => [
          'label' => 'Email activity tracking',
          'values' => [['value' => ManageSubscriptionFormRenderer::getTrackingConsentCopy()]],
        ],
      ];
    }
    $form->setBody($body);
    $form->setSettings([
      'segments_selected_by' => 'user',
      'segments' => [$segment->getId()],
    ]);
    $this->entityManager->persist($form);
    $this->entityManager->flush();
    return $form;
  }
}
