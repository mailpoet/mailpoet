<?php declare(strict_types = 1);

namespace MailPoet\Test\Router\Endpoints;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Router\Endpoints\Subscription;
use MailPoet\Subscription\Pages;
use MailPoet\Util\Request;
use MailPoet\WP\Functions as WPFunctions;

class SubscriptionTest extends \MailPoetTest {
  public $data;

  /** @var WPFunctions */
  private $wp;

  /*** @var Request */
  private $request;

  public function _before() {
    $this->data = [];
    $this->wp = WPFunctions::get();
    $this->request = $this->diContainer->get(Request::class);
  }

  public function testItDisplaysConfirmPage() {
    $pages = Stub::make(Pages::class, [
      'wp' => $this->wp,
      'confirm' => Expected::exactly(1),
    ], $this);
    $subscription = new Subscription($pages, $this->wp, $this->request);
    $subscription->confirm($this->data);
  }

  public function testItDisplaysManagePage() {
    $pages = Stub::make(Pages::class, [
      'wp' => new WPFunctions,
      'getManageLink' => Expected::exactly(1),
      'getManageContent' => Expected::exactly(1),
    ], $this);
    $subscription = new Subscription($pages, $this->wp, $this->request);
    $subscription->manage($this->data);
    do_shortcode('[mailpoet_manage]');
    do_shortcode('[mailpoet_manage_subscription]');
  }

  public function testItDisplaysConfirmationPageOnGetRequest() {
    $pages = $this->makeSubscriptionPagesStub(false);
    $request = $this->createMock(Request::class);
    $request->method('isPost')->willReturn(false);
    $request->method('getStringParam')->willReturn(null);
    $subscription = new Subscription($pages, $this->wp, $request);
    $subscription->unsubscribe($this->data);

    verify($pages->initCalls)->equals([
      [Pages::ACTION_UNSUBSCRIBE, $this->data, false, false],
      [Pages::ACTION_CONFIRM_UNSUBSCRIBE, $this->data, true, true],
    ]);
  }

  public function testItDisplaysUnsubscribePageOnGetRequestForAlreadyUnsubscribedSubscriber() {
    $pages = $this->makeSubscriptionPagesStub(true);
    $request = $this->createMock(Request::class);
    $request->method('isPost')->willReturn(false);
    $request->method('getStringParam')->willReturn(null);
    $subscription = new Subscription($pages, $this->wp, $request);
    $subscription->unsubscribe($this->data);

    verify($pages->initCalls)->equals([
      [Pages::ACTION_UNSUBSCRIBE, $this->data, false, false],
      [Pages::ACTION_UNSUBSCRIBE, $this->data, true, true],
    ]);
  }

  /**
   * @return Pages&object{initCalls: list<array{0: mixed, 1: mixed, 2: bool, 3: bool}>}
   */
  private function makeSubscriptionPagesStub(bool $isSubscriberUnsubscribed): Pages {
    return new class($isSubscriberUnsubscribed) extends Pages {
      public $initCalls = [];

      /** @var bool */
      private $isSubscriberUnsubscribed;

      public function __construct(
        bool $isSubscriberUnsubscribed
      ) {
        $this->isSubscriberUnsubscribed = $isSubscriberUnsubscribed;
      }

      public function init($action = false, $data = [], $initShortcodes = false, $initPageFilters = false) {
        $this->initCalls[] = [$action, $data, $initShortcodes, $initPageFilters];
        return $this;
      }

      public function isSubscriberUnsubscribed(): bool {
        return $this->isSubscriberUnsubscribed;
      }
    };
  }

  public function testItSavesUnsubscribeReasonWithValidNonce() {
    $saved = false;
    $wp = $this->createMock(WPFunctions::class);
    $pages = Stub::make(Pages::class, [
      'wp' => $wp,
      'saveUnsubscribeReason' => Expected::once(function($reason, $reasonText) use (&$saved) {
        verify($reason)->equals('spam');
        verify($reasonText)->equals('Too many emails');
        $saved = true;
        return true;
      }),
      'getUnsubscribeReasonRedirectUrl' => Expected::once(function($success) {
        return 'http://example.com/unsubscribe?saved=1';
      }),
    ], $this);

    $request = $this->createMock(Request::class);
    $request->method('isPost')->willReturn(true);
    $request->method('getStringParam')->willReturnMap([
      ['reason', 'spam'],
      ['_wpnonce', 'valid_nonce'],
    ]);
    $request->method('getTextareaParam')->with('reason_text')->willReturn('Too many emails');

    $wp->method('wpVerifyNonce')->with('valid_nonce', 'mailpoet_unsubscribe_reason')->willReturn(true);
    $wp->method('sanitizeKey')->willReturnArgument(0);
    // Throw to interrupt the exit; that lets us assert wpSafeRedirect was called with the right URL.
    $wp->expects($this->once())
      ->method('wpSafeRedirect')
      ->with('http://example.com/unsubscribe?saved=1')
      ->willThrowException(new \RuntimeException('exit_redirect'));

    $subscription = new Subscription($pages, $wp, $request);
    try {
      $subscription->unsubscribeReason($this->data);
      $this->fail('Expected redirect to interrupt execution');
    } catch (\RuntimeException $e) {
      verify($e->getMessage())->equals('exit_redirect');
    }

    verify($saved)->true();
  }

  public function testItRejectsUnsubscribeReasonWithInvalidNonce() {
    $wp = $this->createMock(WPFunctions::class);
    $pages = Stub::make(Pages::class, [
      'wp' => $wp,
      'saveUnsubscribeReason' => Expected::never(),
    ], $this);

    $request = $this->createMock(Request::class);
    $request->method('isPost')->willReturn(true);
    $request->method('getStringParam')->willReturnMap([
      ['reason', 'spam'],
      ['_wpnonce', 'invalid_nonce'],
    ]);

    $wp->method('wpVerifyNonce')->with('invalid_nonce', 'mailpoet_unsubscribe_reason')->willReturn(false);
    $wp->expects($this->once())
      ->method('wpDie')
      ->with($this->anything(), '', ['response' => 403])
      ->willThrowException(new \RuntimeException('exit_die'));

    $subscription = new Subscription($pages, $wp, $request);
    try {
      $subscription->unsubscribeReason($this->data);
      $this->fail('Expected wpDie to interrupt execution');
    } catch (\RuntimeException $e) {
      verify($e->getMessage())->equals('exit_die');
    }
  }

  public function testItOptsOutOfTrackingWithValidNonce() {
    $wp = $this->createMock(WPFunctions::class);
    $optedOut = false;
    $pages = Stub::make(Pages::class, [
      'wp' => $wp,
      'trackingOptOut' => Expected::once(function($method, $copy) use (&$optedOut) {
        $optedOut = true;
      }),
    ], $this);

    $request = $this->createMock(Request::class);
    $request->method('isPost')->willReturn(true);
    $request->method('getStringParam')->willReturnMap([
      ['_wpnonce', 'valid_nonce'],
    ]);
    $wp->method('wpVerifyNonce')->with('valid_nonce', 'mailpoet_tracking_opt_out')->willReturn(true);

    $subscription = new Subscription($pages, $wp, $request);
    $subscription->trackingOptOut($this->data);

    verify($optedOut)->true();
  }

  public function testItRejectsTrackingOptOutWithInvalidNonce() {
    $wp = $this->createMock(WPFunctions::class);
    $pages = Stub::make(Pages::class, [
      'wp' => $wp,
      'trackingOptOut' => Expected::never(),
    ], $this);

    $request = $this->createMock(Request::class);
    $request->method('isPost')->willReturn(true);
    $request->method('getStringParam')->willReturnMap([
      ['_wpnonce', 'invalid_nonce'],
    ]);
    $wp->method('wpVerifyNonce')->with('invalid_nonce', 'mailpoet_tracking_opt_out')->willReturn(false);
    $wp->expects($this->once())
      ->method('wpDie')
      ->with($this->anything(), '', ['response' => 403])
      ->willThrowException(new \RuntimeException('exit_die'));

    $subscription = new Subscription($pages, $wp, $request);
    try {
      $subscription->trackingOptOut($this->data);
      $this->fail('Expected wpDie to interrupt execution');
    } catch (\RuntimeException $e) {
      verify($e->getMessage())->equals('exit_die');
    }
  }

  public function testItRedirectsGetRequestToHomeUrl() {
    $wp = $this->createMock(WPFunctions::class);
    $pages = Stub::make(Pages::class, [
      'wp' => $wp,
      'saveUnsubscribeReason' => Expected::never(),
    ], $this);

    $request = $this->createMock(Request::class);
    $request->method('isPost')->willReturn(false);

    $wp->method('homeUrl')->willReturn('http://example.com');
    $wp->expects($this->once())
      ->method('wpSafeRedirect')
      ->with('http://example.com')
      ->willThrowException(new \RuntimeException('exit_redirect'));

    $subscription = new Subscription($pages, $wp, $request);
    try {
      $subscription->unsubscribeReason($this->data);
      $this->fail('Expected redirect to interrupt execution');
    } catch (\RuntimeException $e) {
      verify($e->getMessage())->equals('exit_redirect');
    }
  }
}
