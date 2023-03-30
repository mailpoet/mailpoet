<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\WP;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscribersRepository;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group woo
 */
class SubscriptionTest extends \MailPoetTest {
  public $originalSettings;
  /** @var int */
  private $orderId;

  /** @var Subscription */
  private $subscription;

  /** @var SettingsController */
  private $settings;

  /** @var SegmentEntity */
  private $wcSegment;

  /** @var SubscriberEntity */
  private $subscriber;

  /** @var ConfirmationEmailMailer & MockObject */
  private $confirmationEmailMailer;

  /** @var WP */
  private $wp;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function _before() {
    $this->orderId = 123; // dummy
    $this->settings = SettingsController::getInstance();
    $wcHelper = $this->make(
      Helper::class,
      [
        'woocommerceFormField' => function ($key, $args, $value) {
          return ($args['label'] ?? '') . ($value ? 'checked' : '');
        },
      ]
    );
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->confirmationEmailMailer = $this->createMock(ConfirmationEmailMailer::class);
    $this->subscription = $this->getServiceWithOverrides(
      Subscription::class,
      ['wcHelper' => $wcHelper, 'confirmationEmailMailer' => $this->confirmationEmailMailer]
    );
    $this->wcSegment = $this->segmentsRepository->getWooCommerceSegment();
    $this->wp = $this->diContainer->get(WP::class);

    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('john.doe@example.com');
    $subscriber->setFirstName('John');
    $subscriber->setLastName('Doe');
    $subscriber->setIsWoocommerceUser(true);
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();
    $this->subscriber = $subscriber;
    // back up settings
    $this->originalSettings = $this->settings->get('woocommerce');
  }

  public function testItDisplaysACheckedCheckboxIfCurrentUserIsSubscribed() {
    $this->wp->synchronizeUsers();
    $wpUsers = get_users();
    wp_set_current_user($wpUsers[0]->ID);
    $subscriber = $this->subscribersRepository->getCurrentWPUser();
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->subscribeToSegment($subscriber, $this->wcSegment);
    expect($this->getRenderedOptinField())->stringContainsString('checked');
  }

  public function testItDisplaysAnUncheckedCheckboxIfCurrentUserIsNotSubscribed() {
    $this->wp->synchronizeUsers();
    $wpUsers = get_users();
    wp_set_current_user($wpUsers[0]->ID);
    $subscriber = $this->subscribersRepository->getCurrentWPUser();
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->unsubscribeToSegment($subscriber, $this->wcSegment);
    expect($this->getRenderedOptinField())->stringNotContainsString('checked');
  }

  public function testItDisplaysAnUncheckedCheckboxIfCurrentUserIsNotLoggedIn() {
    wp_set_current_user(0);
    expect($this->getRenderedOptinField())->stringNotContainsString('checked');
  }

  public function testItDisplaysCheckboxOptinMessageFromSettings() {
    $newMessage = 'This is a test message.';
    $this->settings->set(Subscription::OPTIN_MESSAGE_SETTING_NAME, $newMessage);
    expect($this->getRenderedOptinField())->stringContainsString($newMessage);
  }

  public function testItsTemplateCanBeOverriddenByAHook() {
    $newTemplate = 'This is a new template';
    add_filter(
      'mailpoet_woocommerce_checkout_optin_template',
      function ($template, $inputName, $checked, $labelString) use ($newTemplate) {
        return $newTemplate . $inputName . $checked . $labelString;
      },
      10,
      4
    );
    $result = $this->getRenderedOptinField();
    expect($result)->stringContainsString($newTemplate);
    expect($result)->stringContainsString(Subscription::CHECKOUT_OPTIN_INPUT_NAME);
  }

  public function testItDoesNotTryToSubscribeIfThereIsNoEmailInOrderData() {
    $data = [];
    $subscribed = $this->subscription->subscribeOnCheckout($this->orderId, $data);
    expect($subscribed)->equals(null);
  }

  public function testItDoesNotTryToSubscribeIfSubscriberWithTheEmailWasNotSynced() {
    // non-existent
    $data['billing_email'] = 'non-existent-subscriber@example.com';
    $subscribed = $this->subscription->subscribeOnCheckout($this->orderId, $data);
    expect($subscribed)->equals(null);
    // not a WooCommerce user
    $this->subscriber->setIsWoocommerceUser(false);
    $this->subscribersRepository->flush();
    $data['billing_email'] = $this->subscriber->getEmail();
    $subscribed = $this->subscription->subscribeOnCheckout($this->orderId, $data);
    expect($subscribed)->equals(null);
  }

  public function testItKeepsSubscribedStatusWhenOptinIsDisabledAndSignUpConfirmationIsEnabled() {
    $this->subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->subscribeToSegment($this->subscriber, $this->wcSegment);
    $this->entityManager->refresh($this->subscriber);
    $subscribedSegments = $this->subscriber->getSegments();
    expect($subscribedSegments)->count(1);

    $this->settings->set(Subscription::OPTIN_ENABLED_SETTING_NAME, false);
    $this->settings->set('signup_confirmation', ['enabled' => true]);
    $data['billing_email'] = $this->subscriber->getEmail();

    $subscribedInWooSegment = $this->subscription->subscribeOnCheckout($this->orderId, $data);
    expect($subscribedInWooSegment)->equals(false);

    $this->entityManager->refresh($this->subscriber);
    $subscribedSegments = $this->subscriber->getSegments();
    expect($subscribedSegments)->count(1);
    expect($this->subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItUnsubscribesIfCheckboxIsNotChecked() {
    $this->subscribeToSegment($this->subscriber, $this->wcSegment);
    $this->entityManager->clear();
    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscribedSegments = $subscriber->getSegments();
    expect($subscribedSegments)->count(1);

    $this->settings->set(Subscription::OPTIN_ENABLED_SETTING_NAME, true);
    $_POST[Subscription::CHECKOUT_OPTIN_PRESENCE_CHECK_INPUT_NAME] = 1;
    $data['billing_email'] = $this->subscriber->getEmail();
    $subscribed = $this->subscription->subscribeOnCheckout($this->orderId, $data);
    expect($subscribed)->equals(false);

    $subscribedSegments = $this->subscriber->getSegments();
    expect($subscribedSegments)->count(0);

    $this->entityManager->clear();
    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $unsubscribeLog = $this->entityManager->getRepository(StatisticsUnsubscribeEntity::class)->findOneBy(['subscriber' => $subscriber]);
    $this->assertInstanceOf(StatisticsUnsubscribeEntity::class, $unsubscribeLog);
    expect($unsubscribeLog->getSource())->equals(StatisticsUnsubscribeEntity::SOURCE_ORDER_CHECKOUT);
  }

  public function testItSubscribesIfCheckboxIsChecked() {
    // double opt-in disabled, no email
    $this->settings->set('signup_confirmation', ['enabled' => false]);
    $this->confirmationEmailMailer
      ->expects($this->never())
      ->method('sendConfirmationEmail');

    $this->subscriber->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->subscribersRepository->flush();

    $subscribedSegments = $this->subscriber->getSegments();
    expect($subscribedSegments)->count(0);

    // extra segment to subscribe to
    $segment = $this->segmentsRepository->createOrUpdate('some name', 'some description');
    $this->settings->set(Subscription::OPTIN_SEGMENTS_SETTING_NAME, [$segment->getId()]);

    $this->settings->set(Subscription::OPTIN_ENABLED_SETTING_NAME, true);
    $_POST[Subscription::CHECKOUT_OPTIN_INPUT_NAME] = 'on';
    $_POST[Subscription::CHECKOUT_OPTIN_PRESENCE_CHECK_INPUT_NAME] = 1;
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $data['billing_email'] = $this->subscriber->getEmail();

    $subscribed = $this->subscription->subscribeOnCheckout($this->orderId, $data);

    expect($subscribed)->equals(true);
    unset($_POST[Subscription::CHECKOUT_OPTIN_INPUT_NAME]);

    $this->entityManager->clear();
    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscribedSegments = $subscriber->getSegments()->toArray();
    expect($subscribedSegments)->count(2);

    $subscribedSegmentIds = array_map(function (SegmentEntity $segment): int {
      return (int)$segment->getId();
    }, $subscribedSegments);
    expect(in_array($this->wcSegment->getId(), $subscribedSegmentIds))->true();
    expect(in_array($segment->getId(), $subscribedSegmentIds))->true();

    $this->entityManager->clear();
    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getSource())->equals(Source::WOOCOMMERCE_CHECKOUT);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($subscriber->getConfirmedIp())->notEmpty();
    expect($subscriber->getConfirmedAt())->notEmpty();
  }

  public function testItSendsConfirmationEmail() {
    // double opt-in enabled
    $this->settings->set('signup_confirmation', ['enabled' => true]);
    $this->confirmationEmailMailer
      ->expects($this->once())
      ->method('sendConfirmationEmailOnce');

    $this->subscriber->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->subscribersRepository->flush();

    $this->entityManager->clear();
    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscribedSegments = $subscriber->getSegments();
    expect($subscribedSegments)->count(0);

    $this->settings->set(Subscription::OPTIN_ENABLED_SETTING_NAME, true);
    $_POST[Subscription::CHECKOUT_OPTIN_INPUT_NAME] = 'on';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $data['billing_email'] = $this->subscriber->getEmail();

    $subscribed = $this->subscription->subscribeOnCheckout($this->orderId, $data);

    expect($subscribed)->equals(true);
    unset($_POST[Subscription::CHECKOUT_OPTIN_INPUT_NAME]);

    $this->entityManager->clear();
    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscribedSegments = $subscriber->getSegments();
    expect($subscribedSegments)->count(1);

    $subscriber = $this->subscribersRepository->findOneById($this->subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getSource())->equals(Source::WOOCOMMERCE_CHECKOUT);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  private function subscribeToSegment(SubscriberEntity $subscriber, SegmentEntity $segment): void {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();
  }

  private function unsubscribeToSegment(SubscriberEntity $subscriber, SegmentEntity $segment): void {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();
  }

  private function getRenderedOptinField() {
    ob_start();
    $this->subscription->extendWooCommerceCheckoutForm();
    $result = ob_get_clean();
    return $result;
  }

  public function _after() {
    parent::_after();
    // restore settings
    $this->settings->set('woocommerce', $this->originalSettings);
  }
}
