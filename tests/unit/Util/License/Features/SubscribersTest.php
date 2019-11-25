<?php

namespace MailPoet\Test\Util\License\Features;

use Codeception\Util\Stub;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

class SubscribersTest extends \MailPoetUnitTest {

  function testCheckReturnsTrueIfOldUserReachedLimit() {
    $subscribers_feature = Stub::make(SubscribersFeature::class, [
      'license' => false,
      'installation_time' => strtotime('2018-11-11'),
      'subscribers_count' => 2500,
    ]);
    expect($subscribers_feature->check())->true();
  }

  function testCheckReturnsFalseIfOldUserDidntReachLimit() {
    $subscribers_feature = Stub::make(SubscribersFeature::class, [
      'license' => false,
      'installation_time' => strtotime('2018-11-11'),
      'subscribers_count' => 1500,
    ]);
    expect($subscribers_feature->check())->false();
  }

  function testCheckReturnsTrueIfNewUserReachedLimit() {
    $subscribers_feature = Stub::make(SubscribersFeature::class, [
      'license' => false,
      'installation_time' => strtotime('2019-11-11'),
      'subscribers_count' => 1500,
    ]);
    expect($subscribers_feature->check())->true();
  }

  function testCheckReturnsFalseIfNewUserDidntReachLimit() {
    $subscribers_feature = Stub::make(SubscribersFeature::class, [
      'license' => false,
      'installation_time' => strtotime('2019-11-11'),
      'subscribers_count' => 900,
    ]);
    expect($subscribers_feature->check())->false();
  }

  function testCheckReturnsFalseIfLicenseExists() {
    $subscribers_feature = Stub::make(SubscribersFeature::class, [
      'license' => true,
      'installation_time' => strtotime('2019-11-11'),
      'subscribers_count' => 1500,
    ]);
    expect($subscribers_feature->check())->false();
  }
}
