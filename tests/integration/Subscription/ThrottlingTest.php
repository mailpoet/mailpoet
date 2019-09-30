<?php

namespace MailPoet\Test\Subscription;

use Carbon\Carbon;
use MailPoet\Models\SubscriberIP;
use MailPoet\Subscription\Throttling;
use MailPoet\WP\Functions as WPFunctions;

class ThrottlingTest extends \MailPoetTest {
  function testItProgressivelyThrottlesSubscriptions() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    expect(Throttling::throttle())->equals(false);
    expect(Throttling::throttle())->equals(60);
    for ($i = 1; $i <= 10; $i++) {
      $ip = SubscriberIP::create();
      $ip->ip = '127.0.0.1';
      $ip->created_at = Carbon::now()->subMinutes($i);
      $ip->save();
    }
    expect(Throttling::throttle())->equals(MINUTE_IN_SECONDS * pow(2, 10));
  }

  function testItDoesNotThrottleIfDisabledByAHook() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_subscription_limit_enabled', '__return_false');
    expect(Throttling::throttle())->equals(false);
    expect(Throttling::throttle())->equals(false);
    $wp->removeFilter('mailpoet_subscription_limit_enabled', '__return_false');
    expect(Throttling::throttle())->greaterThan(0);
  }

  function testItDoesNotThrottleForLoggedInUsers() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $wp_users = get_users();
    wp_set_current_user($wp_users[0]->ID);
    expect(Throttling::throttle())->equals(false);
    expect(Throttling::throttle())->equals(false);
    wp_set_current_user(0);
    expect(Throttling::throttle())->greaterThan(0);
  }

  function testItPurgesOldSubscriberIps() {
    $ip = SubscriberIP::create();
    $ip->ip = '127.0.0.1';
    $ip->save();

    $ip2 = SubscriberIP::create();
    $ip2->ip = '127.0.0.1';
    $ip2->created_at = Carbon::now()->subMonths(1)->subSeconds(1);
    $ip2->save();

    expect(SubscriberIP::count())->equals(2);
    Throttling::throttle();
    expect(SubscriberIP::count())->equals(1);
  }

  function testItConvertsSecondsToTimeString() {
    expect(Throttling::secondsToTimeString(122885))->equals('34 hours 8 minutes 5 seconds');
    expect(Throttling::secondsToTimeString(3660))->equals('1 hours 1 minutes');
    expect(Throttling::secondsToTimeString(3601))->equals('1 hours 1 seconds');
    expect(Throttling::secondsToTimeString(3600))->equals('1 hours');
    expect(Throttling::secondsToTimeString(61))->equals('1 minutes 1 seconds');
    expect(Throttling::secondsToTimeString(60))->equals('1 minutes');
    expect(Throttling::secondsToTimeString(59))->equals('59 seconds');
  }

  function _after() {
    SubscriberIP::deleteMany();
  }
}
