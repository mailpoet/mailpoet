<?php declare(strict_types = 1);

namespace MailPoet\PostEditorBlocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\WooCommerce as WooSegment;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscribers\TrackingConsentCapture;
use MailPoet\Subscribers\TrackingConsentController;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoet\WooCommerce\Helper as WooHelper;
use MailPoet\WooCommerce\Subscription;
use MailPoet\WP\Functions;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group woo
 */
class WooCommerceBlocksIntegrationTest extends \MailPoetTest {

  /** @var \WC_Order & MockObject */
  private $wcOrderMock;

  /** @var WooSegment & MockObject */
  private $wcSegmentMock;

  /** @var WooCommerceBlocksIntegration */
  private $integration;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->wcOrderMock = $this->createMock(\WC_Order::class);
    $this->wcOrderMock->method('get_id')
      ->willReturn(1);
    $this->wcSegmentMock = $this->createMock(WooSegment::class);
    $this->integration = new WooCommerceBlocksIntegration(
      $this->diContainer->get(Functions::class),
      $this->settings,
      $this->diContainer->get(Subscription::class),
      $this->wcSegmentMock,
      $this->diContainer->get(SubscribersRepository::class),
      $this->diContainer->get(WooHelper::class),
      $this->diContainer->get(TrackingConsentCapture::class)
    );
  }

  public function testItHandlesOptInForGuestCustomer() {
    $this->settings->set('woocommerce.optin_on_checkout.enabled', true);
    $email = 'guest@customer.com';
    $this->wcOrderMock->method('get_billing_email')
      ->willReturn($email);
    $this->setupSyncGuestUserMock($email);
    $request['extensions']['mailpoet']['optin'] = true;
    $this->integration->processCheckoutBlockOptin($this->wcOrderMock, $request);

    $subscriber = $this->entityManager->getRepository(SubscriberEntity::class)->findOneBy(['email' => $email]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->entityManager->refresh($subscriber);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  public function testItDoesNotChangeStatusForGuestCustomer() {
    $this->settings->set('woocommerce.optin_on_checkout.enabled', true);
    $email = 'guest@customer.com';
    $this->wcOrderMock->method('get_billing_email')
      ->willReturn($email);
    $this->setupSyncGuestUserMock($email);
    $request['extensions']['mailpoet']['optin'] = false;
    $this->integration->processCheckoutBlockOptin($this->wcOrderMock, $request);

    $subscriber = $this->entityManager->getRepository(SubscriberEntity::class)->findOneBy(['email' => $email]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->entityManager->refresh($subscriber);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  public function testItHandlesOptinForExistingUnsubscribedCustomer() {
    $this->settings->set('woocommerce.optin_on_checkout.enabled', true);
    $email = 'exising@customer.com';
    $this->wcOrderMock->method('get_billing_email')
      ->willReturn($email);
    $this->createSubscriber($email, SubscriberEntity::STATUS_UNSUBSCRIBED);
    $request['extensions']['mailpoet']['optin'] = true;
    $this->integration->processCheckoutBlockOptin($this->wcOrderMock, $request);

    $subscriber = $this->entityManager->getRepository(SubscriberEntity::class)->findOneBy(['email' => $email]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->entityManager->refresh($subscriber);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  public function testItHandlesOptinForExistingSubscribedCustomer() {
    $this->settings->set('woocommerce.optin_on_checkout.enabled', true);
    $email = 'exising@customer.com';
    $this->wcOrderMock->method('get_billing_email')
      ->willReturn($email);
    $this->createSubscriber($email, SubscriberEntity::STATUS_SUBSCRIBED);
    $request['extensions']['mailpoet']['optin'] = true;
    $this->integration->processCheckoutBlockOptin($this->wcOrderMock, $request);

    $subscriber = $this->entityManager->getRepository(SubscriberEntity::class)->findOneBy(['email' => $email]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->entityManager->refresh($subscriber);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItDoesNotChangeStatusForExistingSubscribedCustomer() {
    $this->settings->set('woocommerce.optin_on_checkout.enabled', true);
    $email = 'exising@customer.com';
    $this->wcOrderMock->method('get_billing_email')
      ->willReturn($email);
    $this->createSubscriber($email, SubscriberEntity::STATUS_SUBSCRIBED);
    $request['extensions']['mailpoet']['optin'] = false;
    $this->integration->processCheckoutBlockOptin($this->wcOrderMock, $request);

    $subscriber = $this->entityManager->getRepository(SubscriberEntity::class)->findOneBy(['email' => $email]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->entityManager->refresh($subscriber);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testANewGuestWhoLeavesTheConsentBoxUntickedEndsDenied() {
    $this->askForTrackingConsentAtCheckout();
    $email = 'unticked-guest@customer.com';
    $this->wcOrderMock->method('get_billing_email')
      ->willReturn($email);
    $this->setupSyncGuestUserMock($email);
    $request['extensions']['mailpoet']['optin'] = false;
    $request['extensions']['mailpoet']['tracking_consent'] = false;
    $this->integration->processCheckoutBlockOptin($this->wcOrderMock, $request);

    $subscriber = $this->entityManager->getRepository(SubscriberEntity::class)->findOneBy(['email' => $email]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->entityManager->refresh($subscriber);
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_DENIED);
  }

  public function testANewGuestWhoTicksTheConsentBoxEndsGranted() {
    $this->askForTrackingConsentAtCheckout();
    $email = 'ticked-guest@customer.com';
    $this->wcOrderMock->method('get_billing_email')
      ->willReturn($email);
    $this->setupSyncGuestUserMock($email);
    $request['extensions']['mailpoet']['optin'] = false;
    $request['extensions']['mailpoet']['tracking_consent'] = true;
    $this->integration->processCheckoutBlockOptin($this->wcOrderMock, $request);

    $subscriber = $this->entityManager->getRepository(SubscriberEntity::class)->findOneBy(['email' => $email]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->entityManager->refresh($subscriber);
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_GRANTED);
  }

  public function testANewGuestWhoseCheckoutSentNoConsentFieldIsNotRecordedAsDeclining() {
    $this->askForTrackingConsentAtCheckout();
    $email = 'headless-guest@customer.com';
    $this->wcOrderMock->method('get_billing_email')
      ->willReturn($email);
    $this->setupSyncGuestUserMock($email);
    $request['extensions']['mailpoet']['optin'] = false;
    $this->integration->processCheckoutBlockOptin($this->wcOrderMock, $request);

    $subscriber = $this->entityManager->getRepository(SubscriberEntity::class)->findOneBy(['email' => $email]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->entityManager->refresh($subscriber);
    verify($subscriber->getTrackingConsent())->equals(SubscriberEntity::TRACKING_CONSENT_UNKNOWN);
    verify($subscriber->getTrackingConsentMethod())->null();
  }

  public function testCheckoutBlockReceivesOptinMessageWithAllowedHtmlOnly() {
    $this->settings->set(
      'woocommerce.optin_on_checkout.message',
      'Send me <strong>offers</strong><img src="x" onerror="alert(1)"><script>alert(2)</script>'
    );
    $registry = $this->createMock(IntegrationRegistry::class);
    $registeredBlock = null;
    $registry->method('register')
      ->willReturnCallback(function ($block) use (&$registeredBlock) {
        $registeredBlock = $block;
        return true;
      });

    $this->integration->registerCheckoutFrontendBlocks($registry);

    $this->assertInstanceOf(MarketingOptinBlock::class, $registeredBlock);
    verify($registeredBlock->get_script_data()['defaultText'])->equals('Send me <strong>offers</strong><img src="x">alert(2)');
  }

  private function askForTrackingConsentAtCheckout(): void {
    $this->settings->set('woocommerce.optin_on_checkout.enabled', true);
    $this->settings->set(
      TrackingConsentController::SETTING_SUBSCRIBER_CHOICE,
      TrackingConsentController::CHOICE_ASK_ALL
    );
  }

  private function setupSyncGuestUserMock(string $email) {
    $this->wcSegmentMock->method('synchronizeGuestCustomer')
      ->willReturnCallback(function () use ($email) {
        return (new Subscriber())->withEmail($email)
          ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
          ->withIsWooCommerceUser(true)
          ->create();
      });
  }

  private function createSubscriber(string $email, string $status) {
    return (new Subscriber())->withEmail($email)
      ->withStatus($status)
      ->withIsWooCommerceUser(true)
      ->create();
  }
}
