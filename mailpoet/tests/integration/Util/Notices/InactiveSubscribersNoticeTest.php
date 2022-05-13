<?php

namespace MailPoet\Util\Notices;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;

class InactiveSubscribersNoticeTest extends \MailPoetTest {
  public function testItDisplays() {
    $this->createSubscribers(50);

    $notice = new InactiveSubscribersNotice(SettingsController::getInstance(), new WPFunctions());
    $result = $notice->init(true);
    expect($result)->stringContainsString('Good news! MailPoet won’t send emails to your 50 inactive subscribers.');
    expect($result)->stringContainsString('https://kb.mailpoet.com/article/264-inactive-subscribers');
    expect($result)->stringContainsString('<a href="admin.php?page=mailpoet-settings#advanced" class="button button-primary">Go to the Advanced Settings</a>');
  }

  public function testItDoesntDisplayWhenDisabled() {
    $this->createSubscribers(50);

    $notice = new InactiveSubscribersNotice(SettingsController::getInstance(), new WPFunctions());
    $notice->disable();
    $result = $notice->init(true);
    expect($result)->null();
  }

  public function testItDisplaysWhenInactiveTimeRangeIsTheDefaultValue() {
    $this->createSubscribers(50);

    $settingsFactory = new Settings();
    $settingsFactory->withDeactivateSubscriberAfter12Months();

    $notice = new InactiveSubscribersNotice(SettingsController::getInstance(), new WPFunctions());
    $result = $notice->init(true);
    expect($result)->stringContainsString('Good news! MailPoet won’t send emails to your 50 inactive subscribers.');
    expect($result)->stringContainsString('https://kb.mailpoet.com/article/264-inactive-subscribers');
    expect($result)->stringContainsString('<a href="admin.php?page=mailpoet-settings#advanced" class="button button-primary">Go to the Advanced Settings</a>');
  }

  public function testItDoesntDisplayWhenInactiveTimeRangeChanged() {
    $this->createSubscribers(50);

    $settingsFactory = new Settings();
    $settingsFactory->withDeactivateSubscriberAfter3Months();

    $notice = new InactiveSubscribersNotice(SettingsController::getInstance(), new WPFunctions());
    $result = $notice->init(true);
    expect($result)->null();
  }

  public function testItDoesntDisplayWhenNotEnoughInactiveSubscribers() {
    $this->createSubscribers(49);

    $notice = new InactiveSubscribersNotice(SettingsController::getInstance(), new WPFunctions());
    $result = $notice->init(true);
    expect($result)->null();
  }

  public function _before() {
    parent::_before();
    $this->cleanup();
  }

  public function _after() {
    parent::_after();
    $this->cleanup();
  }

  private function cleanup() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
    $this->truncateEntity(SubscriberEntity::class);
  }

  private function createSubscribers($count) {
    for ($i = 0; $i < $count; $i++) {
      $subscriberFactory = new SubscriberFactory();
      $subscriberFactory->withStatus(SubscriberEntity::STATUS_INACTIVE);
      $subscriberFactory->create();
    }
  }
}
