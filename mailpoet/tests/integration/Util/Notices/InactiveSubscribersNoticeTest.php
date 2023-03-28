<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;

class InactiveSubscribersNoticeTest extends \MailPoetTest {
  /** @var InactiveSubscribersNotice */
  private $notice;

  public function testItDisplays() {
    $this->createSubscribers(50);

    $result = $this->notice->init(true);
    expect($result)->stringContainsString('Good news! MailPoet won’t send emails to your 50 inactive subscribers.');
    expect($result)->stringContainsString('https://kb.mailpoet.com/article/264-inactive-subscribers');
    expect($result)->stringContainsString('<a href="admin.php?page=mailpoet-settings#advanced" class="button button-primary">Go to the Advanced Settings</a>');
  }

  public function testItDoesntDisplayWhenDisabled() {
    $this->createSubscribers(50);

    $this->notice->disable();
    $result = $this->notice->init(true);
    expect($result)->null();
  }

  public function testItDisplaysWhenInactiveTimeRangeIsTheDefaultValue() {
    $this->createSubscribers(50);

    $settingsFactory = new Settings();
    $settingsFactory->withDeactivateSubscriberAfter12Months();

    $result = $this->notice->init(true);
    expect($result)->stringContainsString('Good news! MailPoet won’t send emails to your 50 inactive subscribers.');
    expect($result)->stringContainsString('https://kb.mailpoet.com/article/264-inactive-subscribers');
    expect($result)->stringContainsString('<a href="admin.php?page=mailpoet-settings#advanced" class="button button-primary">Go to the Advanced Settings</a>');
  }

  public function testItDoesntDisplayWhenInactiveTimeRangeChanged() {
    $this->createSubscribers(50);

    $settingsFactory = new Settings();
    $settingsFactory->withDeactivateSubscriberAfter3Months();

    $result = $this->notice->init(true);
    expect($result)->null();
  }

  public function testItDoesntDisplayWhenNotEnoughInactiveSubscribers() {
    $this->createSubscribers(49);

    $result = $this->notice->init(true);
    expect($result)->null();
  }

  public function _before() {
    parent::_before();
    $this->notice = new InactiveSubscribersNotice(
      SettingsController::getInstance(),
      $this->diContainer->get(SubscribersRepository::class),
      new WPFunctions()
    );
  }

  private function createSubscribers($count) {
    for ($i = 0; $i < $count; $i++) {
      $subscriberFactory = new SubscriberFactory();
      $subscriberFactory->withStatus(SubscriberEntity::STATUS_INACTIVE);
      $subscriberFactory->create();
    }
  }
}
