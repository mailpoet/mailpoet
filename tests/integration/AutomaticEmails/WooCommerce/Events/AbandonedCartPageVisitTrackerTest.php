<?php

namespace MailPoet\Premium\AutomaticEmails\WooCommerce\Events;

use Carbon\Carbon;
use MailPoet\Util\Cookies;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Session;
use WooCommerce;
use WP_User;

class AbandonedCartPageVisitTrackerTest extends \MailPoetTest {
  /** @var Carbon */
  private $current_time;

  /** @var WPFunctions|MockObject */
  private $wp;

  /** @var mixed[] */
  private $session_store = [];

  /** @var AbandonedCartPageVisitTracker */
  private $page_visit_tracker;

  function _before() {
    $this->current_time = Carbon::now();
    Carbon::setTestNow($this->current_time);

    $this->wp = $this->makeEmpty(WPFunctions::class, [
      'currentTime' => $this->current_time->getTimestamp(),
    ]);

    $woo_commerce_mock = $this->mockWooCommerceClass(WooCommerce::class, []);
    $woo_commerce_mock->session = $this->createWooCommerceSessionMock();
    $woo_commerce_helper_mock = $this->make(WooCommerceHelper::class, [
      'isWooCommerceActive' => true,
      'WC' => $woo_commerce_mock,
    ]);

    $this->session_store = [];
    $this->page_visit_tracker = new AbandonedCartPageVisitTracker($this->wp, $woo_commerce_helper_mock, new Cookies());
  }

  function testItSetsTimestampWhenTrackingStarted() {
    $this->page_visit_tracker->startTracking();
    expect($this->session_store['mailpoet_last_visit_timestamp'])->same($this->current_time->getTimestamp());
  }

  function testItDeletesTimestampWhenTrackingStopped() {
    $this->page_visit_tracker->stopTracking();
    expect($this->session_store)->isEmpty();
  }

  function testItTracks() {
    $this->wp->method('isAdmin')->willReturn(false);
    $this->wp->method('wpGetCurrentUser')->willReturn(
      $this->makeEmpty(WP_User::class, ['exists' => true])
    );

    $hour_ago_timestamp = $this->current_time->getTimestamp() - 60 * 60;
    $this->session_store['mailpoet_last_visit_timestamp'] = $hour_ago_timestamp;

    $tracking_callback_executed = false;
    $this->page_visit_tracker->trackVisit(function () use (&$tracking_callback_executed) {
      $tracking_callback_executed = true;
    });
    expect($this->session_store['mailpoet_last_visit_timestamp'])->same($this->current_time->getTimestamp());
    expect($tracking_callback_executed)->true();
  }

  function testItTracksByCookie() {
    $this->wp->method('isAdmin')->willReturn(false);
    $this->wp->method('wpGetCurrentUser')->willReturn(
      $this->makeEmpty(WP_User::class, ['exists' => false])
    );
    $_COOKIE['mailpoet_abandoned_cart_tracking'] = true;

    $hour_ago_timestamp = $this->current_time->getTimestamp() - 60 * 60;
    $this->session_store['mailpoet_last_visit_timestamp'] = $hour_ago_timestamp;
    $this->page_visit_tracker->trackVisit();
    expect($this->session_store['mailpoet_last_visit_timestamp'])->same($this->current_time->getTimestamp());
  }

  function testItDoesNotTrackWhenUserNotFound() {
    $this->wp->method('isAdmin')->willReturn(false);
    $this->wp->method('wpGetCurrentUser')->willReturn(
      $this->makeEmpty(WP_User::class, ['exists' => false])
    );

    $hour_ago_timestamp = $this->current_time->getTimestamp() - 60 * 60;
    $this->session_store['mailpoet_last_visit_timestamp'] = $hour_ago_timestamp;
    $this->page_visit_tracker->trackVisit();
    expect($this->session_store['mailpoet_last_visit_timestamp'])->same($hour_ago_timestamp);
  }

  function testItDoesNotTrackAdminPage() {
    $this->wp->method('isAdmin')->willReturn(true);
    $this->wp->method('wpGetCurrentUser')->willReturn(
      $this->makeEmpty(WP_User::class, ['exists' => true])
    );

    $hour_ago_timestamp = $this->current_time->getTimestamp() - 60 * 60;
    $this->session_store['mailpoet_last_visit_timestamp'] = $hour_ago_timestamp;
    $this->page_visit_tracker->trackVisit();
    expect($this->session_store['mailpoet_last_visit_timestamp'])->same($hour_ago_timestamp);
  }

  function testItDoesNotTrackMultipleTimesPerMinute() {
    $ten_seconds_ago_timestamp = $this->current_time->getTimestamp() - 10;
    $this->session_store['mailpoet_last_visit_timestamp'] = $ten_seconds_ago_timestamp;
    $this->page_visit_tracker->trackVisit();
    expect($this->session_store['mailpoet_last_visit_timestamp'])->same($ten_seconds_ago_timestamp);
  }

  private function createWooCommerceSessionMock() {
    $mock = $this->mockWooCommerceClass(WC_Session::class, ['get', 'set', '__unset']);

    $mock->method('get')->willReturnCallback(function ($key) {
      return isset($this->session_store[$key]) ? $this->session_store[$key] : null;
    });
    $mock->method('set')->willReturnCallback(function ($key, $value) {
      $this->session_store[$key] = $value;
    });
    $mock->method('__unset')->willReturnCallback(function ($key) {
      unset($this->session_store[$key]);
    });
    return $mock;
  }

  private function mockWooCommerceClass($class_name, array $methods) {
    // WooCommerce class needs to be mocked without default 'disallowMockingUnknownTypes'
    // since WooCommerce may not be active (would result in error mocking undefined class)
    return $this->getMockBuilder($class_name)
      ->disableOriginalConstructor()
      ->disableOriginalClone()
      ->disableArgumentCloning()
      ->setMethods($methods)
      ->getMock();
  }

  function _after() {
    Carbon::setTestNow();
  }
}
