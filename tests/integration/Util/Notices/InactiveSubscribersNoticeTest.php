<?php

namespace MailPoet\Util\Notices;

use MailPoet\Models\Setting;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;

class InactiveSubscribersNoticeTest extends \MailPoetTest {
  function testItDisplays() {
    $this->createSubscribers(50);

    $notice = new InactiveSubscribersNotice(new SettingsController(), new WPFunctions());
    $result = $notice->init(true);
    expect($result)->contains('Good news! MailPoet won’t send emails to your 50 inactive subscribers.');
    expect($result)->contains('https://kb.mailpoet.com/article/264-inactive-subscribers');
    expect($result)->contains('<a href="admin.php?page=mailpoet-settings#advanced" class="button button-primary">Go to the Advanced Settings</a>');
  }

  function testItDoesntDisplayWhenDisabled() {
    $this->createSubscribers(50);

    $notice = new InactiveSubscribersNotice(new SettingsController(), new WPFunctions());
    $notice->disable();
    $result = $notice->init(true);
    expect($result)->null();
  }

  function testItDoesntDisplayWhenInactiveTimeRangeChanged() {
    $this->createSubscribers(50);

    $settings_factory = new Settings();
    $settings_factory->withDeactivateSubscriberAfter3Months();

    $notice = new InactiveSubscribersNotice(new SettingsController(), new WPFunctions());
    $result = $notice->init(true);
    expect($result)->null();
  }

  function testItDoesntDisplayWhenNotEnoughInactiveSubscribers() {
    $this->createSubscribers(49);

    $notice = new InactiveSubscribersNotice(new SettingsController(), new WPFunctions());
    $result = $notice->init(true);
    expect($result)->null();
  }

  function _before() {
    parent::_before();
    $this->cleanup();
  }

  function _after() {
    parent::_after();
    $this->cleanup();
  }

  private function cleanup() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
  }

  private function createSubscribers($count) {
    for ($i = 0; $i < $count; $i++) {
      $subscriber_factory = new SubscriberFactory();
      $subscriber_factory->withStatus(Subscriber::STATUS_INACTIVE);
      $subscriber_factory->create();
    }
  }
}
