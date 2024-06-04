<?php declare(strict_types = 1);

namespace MailPoet\Subscription;

use MailPoet\Entities\SubscriberIPEntity;
use MailPoet\Subscribers\SubscriberIPsRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class ThrottlingTest extends \MailPoetTest {
  /** @var Throttling */
  private $throttling;

  /** @var SubscriberIPsRepository */
  private $subscriberIPsRepository;

  protected function _before() {
    parent::_before();
    $this->throttling = $this->diContainer->get(Throttling::class);
    $this->subscriberIPsRepository = $this->diContainer->get(SubscriberIPsRepository::class);
    $this->subscriberIPsRepository->deleteCreatedAtBeforeTimeInSeconds(0);
  }

  public function testItProgressivelyThrottlesSubscriptions() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    verify($this->throttling->throttle())->equals(false);
    verify($this->throttling->throttle())->equals(60);
    for ($i = 1; $i <= 10; $i++) {
      $this->createSubscriberIP('127.0.0.1', Carbon::now()->subMinutes($i));
    }
    verify($this->throttling->throttle())->equals(MINUTE_IN_SECONDS * pow(2, 10));
  }

  public function testItDoesNotThrowErrorForBigNumbersAndCapsTimeout() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    verify($this->throttling->throttle())->equals(false);
    verify($this->throttling->throttle())->equals(60);
    for ($i = 1; $i <= 64; $i++) {
      $this->createSubscriberIP('127.0.0.1', Carbon::now()->subMinutes($i));
    }
    verify($this->throttling->throttle())->equals(DAY_IN_SECONDS);
  }

  public function testItDoesNotThrottleIfDisabledByAHook() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $wp = new WPFunctions;
    $wp->addFilter('mailpoet_subscription_limit_enabled', '__return_false');
    verify($this->throttling->throttle())->equals(false);
    verify($this->throttling->throttle())->equals(false);
    $wp->removeFilter('mailpoet_subscription_limit_enabled', '__return_false');
    verify($this->throttling->throttle())->greaterThan(0);
  }

  public function testItDoesNotThrottleForExemptRoleUsers() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $wpUsers = get_users();
    wp_set_current_user($wpUsers[0]->ID);
    verify($this->throttling->throttle())->equals(false);
    verify($this->throttling->throttle())->equals(false);
    wp_set_current_user(0);
    verify($this->throttling->throttle())->greaterThan(0);
  }

  public function testItThrottlesForNotExemptRoleUsers() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $wpUsers = get_users(['role' => 'administrator']);
    $wpUsers[0]->remove_role('administrator');
    wp_set_current_user($wpUsers[0]->ID);
    verify($this->throttling->throttle())->equals(false);
    verify($this->throttling->throttle())->equals(60);
    $wpUsers[0]->add_role('administrator');
    wp_set_current_user(0);
  }

  public function testItPurgesOldSubscriberIps() {
    $this->createSubscriberIP('127.0.0.1', Carbon::now());
    $this->createSubscriberIP('127.0.0.1', Carbon::now()->subDays(30)->subSeconds(1));

    verify($this->subscriberIPsRepository->countBy([]))->equals(2);
    $this->throttling->throttle();
    verify($this->subscriberIPsRepository->countBy([]))->equals(1);
  }

  public function testItConvertsSecondsToTimeString() {
    verify($this->throttling->secondsToTimeString(122885))->equals('34 hours 8 minutes 5 seconds');
    verify($this->throttling->secondsToTimeString(3660))->equals('1 hours 1 minutes');
    verify($this->throttling->secondsToTimeString(3601))->equals('1 hours 1 seconds');
    verify($this->throttling->secondsToTimeString(3600))->equals('1 hours');
    verify($this->throttling->secondsToTimeString(61))->equals('1 minutes 1 seconds');
    verify($this->throttling->secondsToTimeString(60))->equals('1 minutes');
    verify($this->throttling->secondsToTimeString(59))->equals('59 seconds');
  }

  private function createSubscriberIP(string $ip, Carbon $createdAt): SubscriberIPEntity {
    $subscriberIP = new SubscriberIPEntity($ip);
    $subscriberIP->setCreatedAt($createdAt);
    $this->entityManager->persist($subscriberIP);
    $this->entityManager->flush();
    return $subscriberIP;
  }
}
