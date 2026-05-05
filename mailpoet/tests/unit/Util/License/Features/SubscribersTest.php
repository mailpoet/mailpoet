<?php declare(strict_types = 1);

namespace MailPoet\Test\Util\License\Features;

use Codeception\Util\Stub;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;

class SubscribersTest extends \MailPoetUnitTest {
  public function testCheckReturnsTrueIfOldUserReachedLimit() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'invalid',
      'premium_key_state' => 'invalid',
      'installed_at' => '2018-11-11',
      'subscribers_count' => 2500,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 500,
    ]);
    verify($subscribersFeature->check())->true();
  }

  public function testCheckReturnsFalseIfOldUserDidntReachLimit() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'invalid',
      'premium_key_state' => 'invalid',
      'installed_at' => '2018-11-11',
      'subscribers_count' => 1500,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 500,
    ]);
    verify($subscribersFeature->check())->false();
  }

  public function testCheckReturnsTrueIfNewUserReachedLimit() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'invalid',
      'premium_key_state' => 'invalid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 1500,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 500,
    ]);
    verify($subscribersFeature->check())->true();
  }

  public function testCheckReturnsTrueIfNoUserLimitReachedButShotRestrictsAccess() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'invalid',
      'premium_key_state' => 'invalid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 200,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 500,
      'access_restriction' => Bridge::KEY_ACCESS_SUBSCRIBERS_LIMIT,
    ]);
    verify($subscribersFeature->check())->true();
  }

  public function testCheckReturnsFalseIfNewUserDidntReachLimit() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'invalid',
      'premium_key_state' => 'invalid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 900,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 500,
    ]);
    verify($subscribersFeature->check())->false();
  }

  public function testCheckReturnsFalseIfMSSKeyExistsAndDidntReachLimit() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'valid',
      'premium_key_state' => 'invalid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 2500,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 3500,
    ]);
    verify($subscribersFeature->check())->false();
  }

  public function testCheckReturnsTrueIfMSSKeyExistsAndReachedLimit() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'valid',
      'premium_key_state' => 'invalid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 3000,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 2500,
    ]);
    verify($subscribersFeature->check())->true();
  }

  public function testCheckReturnsTrueIfMSSKeyIsExpiringAndReachedLimit() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'expiring',
      'premium_key_state' => 'invalid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 3000,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 2500,
    ]);
    verify($subscribersFeature->check())->true();
  }

  public function testCheckReturnsFalseIfMSSKeyIsAlreadyUsedAndReachedLimit() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'already_used',
      'premium_key_state' => 'invalid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 800,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 500,
    ]);
    verify($subscribersFeature->check())->false();
  }

  public function testCheckReturnsFalseIfPremiumKeyExistsAndDidntReachLimit() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'invalid',
      'premium_key_state' => 'valid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 2500,
      'premium_subscribers_limit' => 3500,
      'mss_subscribers_limit' => 500,
    ]);
    verify($subscribersFeature->check())->false();
  }

  public function testCheckReturnsTrueIfPremiumKeyExistsAndReachedLimit() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'invalid',
      'premium_key_state' => 'valid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 3000,
      'premium_subscribers_limit' => 2500,
      'mss_subscribers_limit' => 500,
    ]);
    verify($subscribersFeature->check())->true();
  }

  public function testCheckReturnsFalseIfPremiumKeyExistsButLimitMissing() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'invalid',
      'premium_key_state' => 'valid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 3000,
      'premium_subscribers_limit' => false,
      'mss_subscribers_limit' => false,
    ]);
    verify($subscribersFeature->check())->false();
  }

  public function testCheckReturnsFalseIfMSSKeyExistsButLimitMissing() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'valid',
      'premium_key_state' => 'invalid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 3000,
      'premium_subscribers_limit' => false,
      'mss_subscribers_limit' => false,
    ]);
    verify($subscribersFeature->check())->false();
  }

  public function testCheckReturnsTrueIfPremiumSupportAndReachedLimit() {
    $subscribersFeature = $this->constructWith([
      'has_mss_key' => false,
      'mss_key_state' => 'valid',
      'premium_key_state' => 'valid',
      'has_premium_key' => true,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 3000,
      'premium_subscribers_limit' => 2500,
      'mss_subscribers_limit' => 500,
      'support_tier' => 'premium',
    ]);
    verify($subscribersFeature->check())->true();
  }

  public function testItGetsFreshSubscribersCountWithoutTransient() {
    $subscribersRepository = $this->createMock(SubscribersRepository::class);
    $subscribersRepository->expects($this->once())
      ->method('getTotalSubscribers')
      ->willReturn(123);

    $wpFunctions = $this->createMock(WPFunctions::class);
    $wpFunctions->expects($this->never())->method('getTransient');
    $wpFunctions->expects($this->never())->method('setTransient');

    $subscribersFeature = new SubscribersFeature(
      $this->createMock(SettingsController::class),
      $subscribersRepository,
      $wpFunctions
    );

    verify($subscribersFeature->getFreshSubscribersCount())->equals(123);
  }

  public function testItGetsSubscriberLimitForNotificationsWhenLimitIsKnown() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'invalid',
      'premium_key_state' => 'invalid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 0,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 500,
    ]);
    verify($subscribersFeature->getSubscriberLimitForNotifications())->equals(SubscribersFeature::SUBSCRIBERS_NEW_LIMIT);

    $subscribersFeature = $this->constructWith([
      'mss_key_state' => Bridge::KEY_VALID,
      'premium_key_state' => 'invalid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 0,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 500,
    ]);
    verify($subscribersFeature->getSubscriberLimitForNotifications())->equals(500);

    $subscribersFeature = $this->constructWith([
      'mss_key_state' => 'invalid',
      'premium_key_state' => Bridge::KEY_VALID_UNDERPRIVILEGED,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 0,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 500,
    ]);
    verify($subscribersFeature->getSubscriberLimitForNotifications())->equals(500);
  }

  public function testItReturnsNoSubscriberLimitForNotificationsWhenPlanLimitIsMissing() {
    $subscribersFeature = $this->constructWith([
      'mss_key_state' => Bridge::KEY_VALID,
      'premium_key_state' => 'invalid',
      'installed_at' => '2019-11-11',
      'subscribers_count' => 0,
      'premium_subscribers_limit' => false,
      'mss_subscribers_limit' => false,
    ]);
    verify($subscribersFeature->getSubscriberLimitForNotifications())->null();
  }

  private function constructWith($specs) {
    $settings = Stub::make(SettingsController::class, [
      'get' => function($name) use($specs) {
        if ($name === 'installed_at') return $specs['installed_at'];
        if ($name === SubscribersFeature::MSS_KEY_STATE) return $specs['mss_key_state'];
        if ($name === SubscribersFeature::PREMIUM_KEY_STATE) return $specs['premium_key_state'];
        if ($name === SubscribersFeature::PREMIUM_SUBSCRIBERS_LIMIT_SETTING_KEY) return $specs['premium_subscribers_limit'];
        if ($name === SubscribersFeature::MSS_SUBSCRIBERS_LIMIT_SETTING_KEY) return $specs['mss_subscribers_limit'];
        if ($name === SubscribersFeature::PREMIUM_SUPPORT_SETTING_KEY) return isset($specs['support_tier']) ? $specs['support_tier'] : 'free';
        if ($name === Bridge::API_KEY_STATE_SETTING_NAME) return ['access_restriction' => $specs['access_restriction'] ?? null];
      },
    ]);

    $subscribersRepository = Stub::make(SubscribersRepository::class, [
      'getTotalSubscribers' => function() use($specs) {
        return $specs['subscribers_count'];
      },
    ]);

    $wpFunctions = Stub::make(WPFunctions::class, [
      'getTransient' => false,
      'setTransient' => false,
    ]);

    return new SubscribersFeature($settings, $subscribersRepository, $wpFunctions);
  }
}
