<?php

namespace MailPoet\Test\Router\Endpoints;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Router\Endpoints\Subscription;
use MailPoet\Subscription\Pages;
use MailPoet\WP\Functions as WPFunctions;

class SubscriptionTest extends \MailPoetTest {
  public $data;
  public $subscription;

  public function _before() {
    $this->data = [];
    // instantiate class
    $this->subscription = ContainerWrapper::getInstance()->get(Subscription::class);
  }

  public function testItDisplaysConfirmPage() {
    $pages = Stub::make(Pages::class, [
      'wp' => new WPFunctions,
      'confirm' => Expected::exactly(1),
    ], $this);
    $subscription = new Subscription($pages);
    $subscription->confirm($this->data);
  }

  public function testItDisplaysManagePage() {
    $pages = Stub::make(Pages::class, [
      'wp' => new WPFunctions,
      'getManageLink' => Expected::exactly(1),
      'getManageContent' => Expected::exactly(1),
    ], $this);
    $subscription = new Subscription($pages);
    $subscription->manage($this->data);
    do_shortcode('[mailpoet_manage]');
    do_shortcode('[mailpoet_manage_subscription]');
  }

  public function testItDisplaysUnsubscribePage() {
    $pages = Stub::make(Pages::class, [
      'wp' => new WPFunctions,
      'unsubscribe' => Expected::exactly(1),
    ], $this);
    $subscription = new Subscription($pages);
    $subscription->unsubscribe($this->data);
  }
}
