<?php

namespace MailPoet\Test\Util\License\Features;

use Codeception\Util\Stub;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

class SubscribersTest extends \MailPoetUnitTest {

  public function testCheckReturnsTrueIfOldUserReachedLimit() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => false,
      'has_premium_key' => false,
      'installed_at' => '2018-11-11',
      'subscribers_count' => 2500,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 500,
    ]);
    expect($subscribers_feature->check())->true();
  }

  public function testCheckReturnsFalseIfOldUserDidntReachLimit() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => false,
      'has_premium_key' => false,
      'installed_at' => '2018-11-11',
      'subscribers_count' => 1500,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 500,
    ]);
    expect($subscribers_feature->check())->false();
  }

  public function testCheckReturnsTrueIfNewUserReachedLimit() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => false,
      'has_premium_key' => false,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 1500,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 500,
    ]);
    expect($subscribers_feature->check())->true();
  }

  public function testCheckReturnsFalseIfNewUserDidntReachLimit() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => false,
      'has_premium_key' => false,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 900,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 500,
    ]);
    expect($subscribers_feature->check())->false();
  }

  public function testCheckReturnsFalseIfMSSKeyExistsAndDidntReachLimit() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => true,
      'has_premium_key' => false,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 2500,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 3500,
    ]);
    expect($subscribers_feature->check())->false();
  }

  public function testCheckReturnsTrueIfMSSKeyExistsAndReachedLimit() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => true,
      'has_premium_key' => false,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 3000,
      'premium_subscribers_limit' => 500,
      'mss_subscribers_limit' => 2500,
    ]);
    expect($subscribers_feature->check())->true();
  }

  public function testCheckReturnsFalseIfPremiumKeyExistsAndDidntReachLimit() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => false,
      'has_premium_key' => true,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 2500,
      'premium_subscribers_limit' => 3500,
      'mss_subscribers_limit' => 500,
    ]);
    expect($subscribers_feature->check())->false();
  }

  public function testCheckReturnsTrueIfPremiumKeyExistsAndReachedLimit() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => false,
      'has_premium_key' => true,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 3000,
      'premium_subscribers_limit' => 2500,
      'mss_subscribers_limit' => 500,
    ]);
    expect($subscribers_feature->check())->true();
  }

  public function testCheckReturnsFalseIfPremiumKeyExistsButLimitMissing() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => false,
      'has_premium_key' => true,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 3000,
      'premium_subscribers_limit' => false,
      'mss_subscribers_limit' => false,
    ]);
    expect($subscribers_feature->check())->false();
  }

  public function testCheckReturnsFalseIfMSSKeyExistsButLimitMissing() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => true,
      'has_premium_key' => false,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 3000,
      'premium_subscribers_limit' => false,
      'mss_subscribers_limit' => false,
    ]);
    expect($subscribers_feature->check())->false();
  }

  private function constructWith($specs) {
    $settings = Stub::make(SettingsController::class, [
      'get' => function($name) use($specs) {
        if ($name === 'installed_at') return $specs['installed_at'];
        if ($name === SubscribersFeature::MSS_KEY_STATE) return $specs['has_mss_key'] ? 'valid' : 'invalid';
        if ($name === SubscribersFeature::PREMIUM_KEY_STATE) return $specs['has_premium_key'] ? 'valid' : 'invalid';
        if ($name === SubscribersFeature::PREMIUM_SUBSCRIBERS_LIMIT_SETTING_KEY) return $specs['premium_subscribers_limit'];
        if ($name === SubscribersFeature::MSS_SUBSCRIBERS_LIMIT_SETTING_KEY) return $specs['mss_subscribers_limit'];
      },
    ]);
    $subscribers_repository = Stub::make(SubscribersRepository::class, [
      'getTotalSubscribers' => function() use($specs) {
        return $specs['subscribers_count'];
      },
    ]);

    return new SubscribersFeature($settings, $subscribers_repository);
  }
}
