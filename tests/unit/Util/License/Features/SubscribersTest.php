<?php

use Codeception\Util\Fixtures;
use Codeception\Util\Stub;
use MailPoet\Models\Subscriber;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;

class SubscribersFeaturesTest extends MailPoetTest {
  function testItEnforcesSubscriberLimitWithoutPremiumLicense() {
    $subscribers_feature = Stub::make(new SubscribersFeature(), array(
      'terminateRequest' => function() { return true; }
    ), $this);
    expect($subscribers_feature->check(0))->null();
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    expect($subscribers_feature->check(0))->true();
  }

  function testItDoesNotEnforceSubscriberLimitWithPremiumLicense() {
    define('MAILPOET_PREMIUM_LICENSE', true);
    $subscribers_feature = new SubscribersFeature();
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    expect($subscribers_feature->check(0))->null();
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }
}