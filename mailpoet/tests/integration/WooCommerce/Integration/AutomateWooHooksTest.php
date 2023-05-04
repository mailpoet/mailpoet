<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce\Integrations;

use Codeception\Stub\Expected;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class AutomateWooHooksTest extends \MailPoetTest {
  /** @var WPFunctions */
  private $wp;

  /** @var MockObject */
  private $subscribersRepository;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  public function _before() {
    parent::_before();
    $this->wp = $this->make(new WPFunctions, [
      'isPluginActive' => function($name) {
        if ($name === 'automatewoo/automatewoo.php') {
          return true;
        }
        return false;
      },
    ]);
    $this->subscribersRepository = $this->createMock(SubscribersRepository::class);
    $this->subscriberFactory = new SubscriberFactory();
  }

  public function testSetup() {
    $wp = $this->make(new WPFunctions, [
      'isPluginActive' => function($name) {
        if ($name === 'automatewoo/automatewoo.php') {
          return true;
        }
        return false;
      },
      'addAction' => function($name, $callback) {
        expect($name)->equals(SubscriberEntity::HOOK_SUBSCRIBER_STATUS_CHANGED);
      },
    ]);
    $subscribersRepository = $this->createMock(SubscribersRepository::class);
    $automateWooHooks = new AutomateWooHooks($subscribersRepository, $wp);
    $automateWooHooks->setup();
  }

  public function testOptsOutUnsubscribedSubscriber() {
    $unsubscribedSubscriber = $this->subscriberFactory
      ->withEmail('unsubscribedUser@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->create();
    $this->subscribersRepository->method('findOneById')->willReturn($unsubscribedSubscriber);

    $automateWooHooksPartialMock = $this->getMockBuilder(AutomateWooHooks::class)
    ->setConstructorArgs([$this->subscribersRepository, $this->wp])
    ->onlyMethods(['getAutomateWooCustomer'])
    ->getMock();

    $automateWooCustomer = $this->make(new \AutomateWoo\Customer, ['opt_out' => Expected::once(function() {
    })]);
    $automateWooHooksPartialMock->expects($this->once())->method('getAutomateWooCustomer')->willReturn($automateWooCustomer);

    $automateWooHooksPartialMock->maybeOptOutSubscriber((int)$unsubscribedSubscriber->getId());
  }

  public function testDoesNotOptOutSubscribedSubscriber() {
    $subscribedSubscriber = $this->subscriberFactory
      ->withEmail('subscribedUser@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    $this->subscribersRepository->method('findOneById')->willReturn($subscribedSubscriber);

    $automateWooHooksPartialMock = $this->getMockBuilder(AutomateWooHooks::class)
      ->setConstructorArgs([$this->subscribersRepository, $this->wp])
      ->onlyMethods(['getAutomateWooCustomer'])
      ->getMock();
    $automateWooHooksPartialMock->expects($this->never())->method('getAutomateWooCustomer');

    $automateWooHooksPartialMock->maybeOptOutSubscriber((int)$subscribedSubscriber->getId());
  }
}
