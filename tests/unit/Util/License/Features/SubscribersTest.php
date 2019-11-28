<?php

namespace MailPoet\Test\Util\License\Features;

use Codeception\Util\Stub;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

class SubscribersTest extends \MailPoetUnitTest {

  function testCheckReturnsTrueIfOldUserReachedLimit() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => false,
      'has_premium_key' => false,
      'installed_at' => '2018-11-11',
      'subscribers_count' => 2500,
    ]);
    expect($subscribers_feature->check())->true();
  }

  function testCheckReturnsFalseIfOldUserDidntReachLimit() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => false,
      'has_premium_key' => false,
      'installed_at' => '2018-11-11',
      'subscribers_count' => 1500,
    ]);
    expect($subscribers_feature->check())->false();
  }

  function testCheckReturnsTrueIfNewUserReachedLimit() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => false,
      'has_premium_key' => false,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 1500,
    ]);
    expect($subscribers_feature->check())->true();
  }

  function testCheckReturnsFalseIfNewUserDidntReachLimit() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => false,
      'has_premium_key' => false,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 900,
    ]);
    expect($subscribers_feature->check())->false();
  }

  function testCheckReturnsFalseIfMSSKeyExists() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => true,
      'has_premium_key' => false,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 1500,
    ]);
    expect($subscribers_feature->check())->false();
  }

  function testCheckReturnsFalseIfPremiumKeyExists() {
    $subscribers_feature = $this->constructWith([
      'has_mss_key' => false,
      'has_premium_key' => true,
      'installed_at' => '2019-11-11',
      'subscribers_count' => 1500,
    ]);
    expect($subscribers_feature->check())->false();
  }

  private function constructWith($specs) {
    $settings = Stub::make(SettingsController::class, [
      'get' => function($name) use($specs) {
        if ($name === 'installed_at') return $specs['installed_at'];
        if ($name === Bridge::API_KEY_SETTING_NAME) return $specs['has_mss_key'];
        if ($name === Bridge::PREMIUM_KEY_SETTING_NAME) return $specs['has_premium_key'];
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
