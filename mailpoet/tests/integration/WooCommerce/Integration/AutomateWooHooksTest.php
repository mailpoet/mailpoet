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
      'addAction' => Expected::exactly(1),
    ]);

    $automateWooHooksPartialMock = $this->getMockBuilder(AutomateWooHooks::class)
      ->setConstructorArgs([$this->subscribersRepository, $wp])
      ->onlyMethods(['areMethodsAvailable'])
      ->getMock();
    $automateWooHooksPartialMock->expects($this->once())->method('areMethodsAvailable')->willReturn(true);

    $automateWooHooksPartialMock->setup();
  }

  public function testOptsOutUnsubscribedSubscriber() {
    $unsubscribedSubscriber = $this->subscriberFactory
      ->withEmail('unsubscribedUser@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)->create();
    $this->subscribersRepository->method('findOneById')->willReturn($unsubscribedSubscriber);

    $automateWooHooksPartialMock = $this->getMockBuilder(AutomateWooHooks::class)
    ->setConstructorArgs([$this->subscribersRepository, $this->wp])
    ->onlyMethods(['optOutSubscriber', 'optInSubscriber'])
    ->getMock();

    $automateWooHooksPartialMock->expects($this->once())->method('optOutSubscriber');
    $automateWooHooksPartialMock->expects($this->never())->method('optInSubscriber');

    $automateWooHooksPartialMock->syncSubscriber((int)$unsubscribedSubscriber->getId());
  }

  public function testOptsInSubscribedSubscriber() {
    $subscribedSubscriber = $this->subscriberFactory
      ->withEmail('subscribedUser@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    $this->subscribersRepository->method('findOneById')->willReturn($subscribedSubscriber);
    $this->subscribersRepository->method('getWooCommerceSegmentSubscriber')->willReturn($subscribedSubscriber);

    $automateWooHooksPartialMock = $this->getMockBuilder(AutomateWooHooks::class)
      ->setConstructorArgs([$this->subscribersRepository, $this->wp])
      ->onlyMethods(['optOutSubscriber', 'optInSubscriber'])
      ->getMock();

    $automateWooHooksPartialMock->expects($this->never())->method('optOutSubscriber');
    $automateWooHooksPartialMock->expects($this->once())->method('optInSubscriber');

    $automateWooHooksPartialMock->syncSubscriber((int)$subscribedSubscriber->getId());
  }

  public function testNotOptsInSubscribedSubscriber() {
    $subscribedSubscriber = $this->subscriberFactory
      ->withEmail('subscribedUser@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    $this->subscribersRepository->method('findOneById')->willReturn($subscribedSubscriber);
    $this->subscribersRepository->method('getWooCommerceSegmentSubscriber')->willReturn(null);

    $automateWooHooksPartialMock = $this->getMockBuilder(AutomateWooHooks::class)
      ->setConstructorArgs([$this->subscribersRepository, $this->wp])
      ->onlyMethods(['optOutSubscriber', 'optInSubscriber'])
      ->getMock();

    $automateWooHooksPartialMock->expects($this->never())->method('optOutSubscriber');
    $automateWooHooksPartialMock->expects($this->never())->method('optInSubscriber');

    $automateWooHooksPartialMock->syncSubscriber((int)$subscribedSubscriber->getId());
  }
}
