<?php declare(strict_types = 1);

namespace MailPoet\PostEditorBlocks;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\WooCommerce as WooSegment;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
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
      $this->diContainer->get(WooHelper::class)
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
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  public function testItHandlesOptOutForGuestCustomer() {
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
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
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
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
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
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItHandlesOptOutForExistingSubscribedCustomer() {
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
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
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
