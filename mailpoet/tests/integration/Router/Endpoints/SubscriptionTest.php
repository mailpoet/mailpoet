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
}
