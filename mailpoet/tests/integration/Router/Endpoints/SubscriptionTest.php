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
    $pages = Stub::make(Pages::class, [
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

    $wp = $this->createMock(WPFunctions::class);
    $wp->method('wpVerifyNonce')->with('valid_nonce', 'mailpoet_unsubscribe_reason')->willReturn(true);
    $wp->expects($this->once())->method('wpSafeRedirect')->with('http://example.com/unsubscribe?saved=1');
    $wp->method('sanitizeKey')->willReturnArgument(0);

    $subscription = new Subscription($pages, $wp, $request);
    $subscription->unsubscribeReason($this->data);

    verify($saved)->true();
  }

  public function testItRejectsUnsubscribeReasonWithInvalidNonce() {
    $pages = Stub::make(Pages::class, [
      'saveUnsubscribeReason' => Expected::never(),
    ], $this);

    $request = $this->createMock(Request::class);
    $request->method('isPost')->willReturn(true);
    $request->method('getStringParam')->willReturnMap([
      ['reason', 'spam'],
      ['_wpnonce', 'invalid_nonce'],
    ]);

    $wp = $this->createMock(WPFunctions::class);
    $wp->method('wpVerifyNonce')->with('invalid_nonce', 'mailpoet_unsubscribe_reason')->willReturn(false);
    $wp->expects($this->once())->method('wpDie')->with(
      $this->anything(),
      '',
      ['response' => 403]
    );

    $subscription = new Subscription($pages, $wp, $request);
    $subscription->unsubscribeReason($this->data);
  }

  public function testItRedirectsGetRequestToHomeUrl() {
    $pages = Stub::make(Pages::class, [
      'saveUnsubscribeReason' => Expected::never(),
    ], $this);

    $request = $this->createMock(Request::class);
    $request->method('isPost')->willReturn(false);

    $wp = $this->createMock(WPFunctions::class);
    $wp->method('homeUrl')->willReturn('http://example.com');
    $wp->expects($this->once())->method('wpSafeRedirect')->with('http://example.com');

    $subscription = new Subscription($pages, $wp, $request);
    $subscription->unsubscribeReason($this->data);
  }
}
