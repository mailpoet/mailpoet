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
use MailPoet\Subscription\Pages;
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

  /**
   * Documents pre-existing MailPoet behaviour rather than asserting something
   * desirable. With signup confirmation on (the default), SubscriberActions
   * discards the whole submission for someone who is already subscribed, so a
   * consent tick on a form never reaches them. Their route is the manage page.
   * Not changed here: it affects every form field, not just consent.
   */
  public function testAnAlreadySubscribedPersonsFormConsentIsDroppedUnderConfirmation() {
    $this->settings->set('signup_confirmation.enabled', true);
    $this->askEveryone();
    $email = 'consent-already-subscribed' . rand(0, 100000) . '@example.com';
    $existing = (new SubscriberFactory())->withEmail($email)->create();
    $existing->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->subscribersRepository->flush();

    $this->submit($email, '1');

    $this->entityManager->clear();
    verify($this->getSubscriber($email)->getTrackingConsent())
      ->equals(SubscriberEntity::TRACKING_CONSENT_UNKNOWN);
  }

  /**
   * The deferred branch: confirmation on and the subscriber exists but is not
   * subscribed yet, so the submission is stashed as unconfirmed_data and
   * replayed at confirmation. A grant has to survive that gap.
   */
  public function testAGrantSurvivesTheDoubleOptInGap() {
    $this->settings->set('signup_confirmation.enabled', true);
    $this->askEveryone();
    $email = 'consent-unconfirmed' . rand(0, 100000) . '@example.com';
    $existing = (new SubscriberFactory())->withEmail($email)->create();
    $existing->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscribersRepository->flush();

    $this->submit($email, '1');

    $this->entityManager->clear();
    $stashed = $this->getSubscriber($email)->getUnconfirmedData();
    $this->assertIsString($stashed);
    $decoded = json_decode($stashed, true);
    $this->assertIsArray($decoded);
    verify($decoded['tracking_consent'])->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    verify($decoded['tracking_consent_method'])->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_FORM);

    // Stashing it is only half the journey. Confirmation is a separate request
    // that replays unconfirmed_data, so the grant is only proven to survive
    // once the subscriber has actually been through it.
    $this->confirm($email);

    $this->entityManager->clear();
    $confirmed = $this->getSubscriber($email);
    verify($confirmed->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    verify($confirmed->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
    verify($confirmed->getTrackingConsentMethod())->equals(SubscriberEntity::TRACKING_CONSENT_METHOD_FORM);
    verify($confirmed->getTrackingConsentCopy())
      ->equals(ManageSubscriptionFormRenderer::getTrackingConsentCopy());
  }

  /** Runs the real confirmation request for this subscriber, the way clicking the emailed link does. */
  private function confirm(string $email): void {
    $subscriber = $this->getSubscriber($email);
    $linkTokens = $this->diContainer->get(LinkTokens::class);
    $pages = $this->diContainer->get(Pages::class);
    $pages->init(false, [
      'email' => $subscriber->getEmail(),
      'token' => $linkTokens->getToken($subscriber),
    ], false, false)->confirm();
  }

  /**
   * And the matching guard for the same deferred branch: an existing
   * subscriber who leaves the box unticked must not have a decline stashed,
   * because it would be applied at confirmation and revoke an earlier choice.
   */
  public function testNoDeclineIsStashedForAnExistingSubscriber() {
    $this->settings->set('signup_confirmation.enabled', true);
    $this->askEveryone();
    $email = 'consent-nostash' . rand(0, 100000) . '@example.com';
    $existing = (new SubscriberFactory())->withEmail($email)->create();
    $existing->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscribersRepository->flush();

    $this->submit($email, '0');

    $this->entityManager->clear();
    $stashed = $this->getSubscriber($email)->getUnconfirmedData();
    $decoded = is_string($stashed) ? json_decode($stashed, true) : [];
    $this->assertIsArray($decoded);
    verify(isset($decoded['tracking_consent']))->false();
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
