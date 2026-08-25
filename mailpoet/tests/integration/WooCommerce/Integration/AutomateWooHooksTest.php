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
      'addAction' => Expected::exactly(3),
    ]);

    $automateWooHooksPartialMock = $this->getMockBuilder(AutomateWooHooks::class)
      ->setConstructorArgs([$this->subscribersRepository, $wp])
      ->onlyMethods(['areMethodsAvailable'])
      ->getMock();
    $automateWooHooksPartialMock->expects($this->once())->method('areMethodsAvailable')->willReturn(true);

    $automateWooHooksPartialMock->setup();
  }

  /**
   * A stand-in for AutomateWoo\Customer: AutomateWoo is not loaded in MailPoet's test
   * environment, so the real class does not exist here.
   */
  private function makeAutomateWooCustomerDouble(&$calls) {
    return new class( $calls ) {
      /** @var array */
      public $calls;

      public function __construct(
        &$calls
      ) {
        $this->calls = &$calls;
      }

      // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
      public function opt_out_of_tracking() {
        $this->calls[] = 'opt_out_of_tracking';
      }

      // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
      public function opt_in_to_tracking() {
        $this->calls[] = 'opt_in_to_tracking';
      }
    };
  }

  private function makeHooksForTrackingConsent($subscriber, &$calls, bool $trackingMethodsAvailable = true, bool $customerFound = true) {
    $mock = $this->getMockBuilder(AutomateWooHooks::class)
      ->setConstructorArgs([$this->subscribersRepository, $this->wp])
      ->onlyMethods(['isAutomateWooReady', 'areTrackingMethodsAvailable', 'getAutomateWooCustomer'])
      ->getMock();
    $mock->method('isAutomateWooReady')->willReturn(true);
    $mock->method('areTrackingMethodsAvailable')->willReturn($trackingMethodsAvailable);
    $mock->method('getAutomateWooCustomer')->willReturn(
      $customerFound ? $this->makeAutomateWooCustomerDouble($calls) : false
    );
    $this->subscribersRepository->method('findOneById')->willReturn($subscriber);

    return $mock;
  }

  public function testDeniedConsentOptsTheCustomerOutOfTracking() {
    $subscriber = $this->subscriberFactory->withEmail('denied-consent@mailpoet.com')->create();
    $calls = [];
    $hooks = $this->makeHooksForTrackingConsent($subscriber, $calls);

    $hooks->syncTrackingConsent(
      (int)$subscriber->getId(),
      SubscriberEntity::TRACKING_CONSENT_UNKNOWN,
      SubscriberEntity::TRACKING_CONSENT_DENIED
    );

    verify($calls)->equals(['opt_out_of_tracking']);
  }

  public function testGrantedConsentClearsTheOptOut() {
    // Symmetric with optInSubscriber() reversing optOutSubscriber(): somebody who changes
    // their mind must not be stuck untracked in AutomateWoo forever.
    $subscriber = $this->subscriberFactory->withEmail('granted-consent@mailpoet.com')->create();
    $calls = [];
    $hooks = $this->makeHooksForTrackingConsent($subscriber, $calls);

    $hooks->syncTrackingConsent(
      (int)$subscriber->getId(),
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      SubscriberEntity::TRACKING_CONSENT_GRANTED
    );

    verify($calls)->equals(['opt_in_to_tracking']);
  }

  public function testUnknownConsentDoesNothing() {
    // Nobody was asked, which is not an answer.
    $subscriber = $this->subscriberFactory->withEmail('unknown-consent@mailpoet.com')->create();
    $calls = [];
    $hooks = $this->makeHooksForTrackingConsent($subscriber, $calls);

    $hooks->syncTrackingConsent(
      (int)$subscriber->getId(),
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      SubscriberEntity::TRACKING_CONSENT_UNKNOWN
    );

    verify($calls)->equals([]);
  }

  public function testAnUnrecognisedConsentValueDoesNothing() {
    // The hook is public, so a third party can fire it with a state MailPoet never
    // writes. Clearing an opt-out is the consequential direction, so it takes an
    // explicit `granted` rather than merely "not denied".
    $subscriber = $this->subscriberFactory->withEmail('odd-consent@mailpoet.com')->create();
    $calls = [];
    $hooks = $this->makeHooksForTrackingConsent($subscriber, $calls);

    $hooks->syncTrackingConsent(
      (int)$subscriber->getId(),
      SubscriberEntity::TRACKING_CONSENT_DENIED,
      'not-a-consent-state'
    );

    verify($calls)->equals([]);
  }

  public function testNoAutomateWooCustomerDoesNothing() {
    $subscriber = $this->subscriberFactory->withEmail('no-aw-customer@mailpoet.com')->create();
    $calls = [];
    $hooks = $this->makeHooksForTrackingConsent($subscriber, $calls, true, false);

    $hooks->syncTrackingConsent(
      (int)$subscriber->getId(),
      SubscriberEntity::TRACKING_CONSENT_UNKNOWN,
      SubscriberEntity::TRACKING_CONSENT_DENIED
    );

    verify($calls)->equals([]);
  }

  public function testAnOlderAutomateWooIsSkipped() {
    $subscriber = $this->subscriberFactory->withEmail('old-aw@mailpoet.com')->create();
    $calls = [];
    $hooks = $this->makeHooksForTrackingConsent($subscriber, $calls, false);

    $hooks->syncTrackingConsent(
      (int)$subscriber->getId(),
      SubscriberEntity::TRACKING_CONSENT_UNKNOWN,
      SubscriberEntity::TRACKING_CONSENT_DENIED
    );

    verify($calls)->equals([]);
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

  public function testOptsOutSubscribedSubscriberWithoutWooCommerceList() {
    $subscribedSubscriber = $this->subscriberFactory
      ->withEmail('subscribedUser@mailpoet.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)->create();
    $this->subscribersRepository->method('findOneById')->willReturn($subscribedSubscriber);
    $this->subscribersRepository->method('getWooCommerceSegmentSubscriber')->willReturn(null);

    $automateWooHooksPartialMock = $this->getMockBuilder(AutomateWooHooks::class)
      ->setConstructorArgs([$this->subscribersRepository, $this->wp])
      ->onlyMethods(['optOutSubscriber', 'optInSubscriber'])
      ->getMock();

    $automateWooHooksPartialMock->expects($this->once())->method('optOutSubscriber');
    $automateWooHooksPartialMock->expects($this->never())->method('optInSubscriber');

    $automateWooHooksPartialMock->syncSubscriber((int)$subscribedSubscriber->getId());
  }
}
