<?php

namespace MailPoet\Test\Util\License\Features;

use Codeception\Util\Fixtures;
use Codeception\Util\Stub;
use MailPoet\Models\Subscriber;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoetVendor\Idiorm\ORM;

class SubscribersTest extends \MailPoetTest {

  function testChecksIfSubscribersWithinLimitWhenPremiumLicenseDoesNotExist() {
    // if premium unlocker plugin is enabled, skip this check
    if (defined('MAILPOET_PREMIUM_LICENSE')) return;
    $subscribers_feature = new SubscribersFeature();
    expect($subscribers_feature->check(0))->false();
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    expect($subscribers_feature->check(0))->true();
  }

  function testChecksIfSubscribersWithinLimitWhenPremiumLicenseExists() {
    $subscribers_feature = Stub::construct(
      new SubscribersFeature(),
      [
        'license' => true,
      ]
    );
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    expect($subscribers_feature->check(0))->false();
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }

}
