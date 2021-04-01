<?php

namespace MailPoet\Subscription;

use MailPoet\Models\SubscriberIP;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class ThrottlingTest extends \MailPoetTest {
  /** @var Throttling */
  private $throttling;

  protected function _before() {
    parent::_before();
    $this->throttling = $this->diContainer->get(Throttling::class);
  }

  public function testItProgressivelyThrottlesSubscriptions() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    expect($this->throttling->throttle())->equals(false);
    expect($this->throttling->throttle())->equals(60);
    for ($i = 1; $i <= 10; $i++) {
      $ip = SubscriberIP::create();
      $ip->ip = '127.0.0.1';
      $ip->createdAt = Carbon::now()->subMinutes($i);
      $ip->save();
    }
    expect($this->throttling->throttle())->equals(MINUTE_IN_SECONDS * pow(2, 10));
  }

  public function testItDoesNotThrottleIfDisabledByAHook() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_subscription_limit_enabled', '__return_false');
    expect($this->throttling->throttle())->equals(false);
    expect($this->throttling->throttle())->equals(false);
    $wp->removeFilter('mailpoet_subscription_limit_enabled', '__return_false');
    expect($this->throttling->throttle())->greaterThan(0);
  }

  public function testItDoesNotThrottleForLoggedInUsers() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $wpUsers = get_users();
    wp_set_current_user($wpUsers[0]->ID);
    expect($this->throttling->throttle())->equals(false);
    expect($this->throttling->throttle())->equals(false);
    wp_set_current_user(0);
    expect($this->throttling->throttle())->greaterThan(0);
  }

  public function testItPurgesOldSubscriberIps() {
    $ip = SubscriberIP::create();
    $ip->ip = '127.0.0.1';
    $ip->save();

    $ip2 = SubscriberIP::create();
    $ip2->ip = '127.0.0.1';
    $ip2->createdAt = Carbon::now()->subDays(30)->subSeconds(1);
    $ip2->save();

    expect(SubscriberIP::count())->equals(2);
    $this->throttling->throttle();
    expect(SubscriberIP::count())->equals(1);
  }

  public function testItConvertsSecondsToTimeString() {
    expect($this->throttling->secondsToTimeString(122885))->equals('34 hours 8 minutes 5 seconds');
    expect($this->throttling->secondsToTimeString(3660))->equals('1 hours 1 minutes');
    expect($this->throttling->secondsToTimeString(3601))->equals('1 hours 1 seconds');
    expect($this->throttling->secondsToTimeString(3600))->equals('1 hours');
    expect($this->throttling->secondsToTimeString(61))->equals('1 minutes 1 seconds');
    expect($this->throttling->secondsToTimeString(60))->equals('1 minutes');
    expect($this->throttling->secondsToTimeString(59))->equals('59 seconds');
  }

  public function _after() {
    SubscriberIP::deleteMany();
  }
}
