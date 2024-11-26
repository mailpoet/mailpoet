<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscription;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\Pages as SettingPages;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class SubscriptionUrlFactoryTest extends \MailPoetTest {
  /** @var SubscriberEntity */
  private $subscriber;

  /** @var SubscriptionUrlFactory */
  private $subscriptionUrlFactory;

  public function _before() {
    parent::_before();
    $this->subscriptionUrlFactory = $this->diContainer->get(SubscriptionUrlFactory::class);
    $subscriberFactory = new SubscriberFactory();
    $this->subscriber = $subscriberFactory->create();
  }

  public function testGetReEngagementUrlReturnsDefaultUrl() {
    SettingPages::createMailPoetPage(SettingPages::PAGE_SUBSCRIPTIONS);
    $expectedUrl = get_permalink(SettingPages::getMailPoetPage(SettingPages::PAGE_SUBSCRIPTIONS));

    $this->assertIsString($expectedUrl, "Permalink is a valid string");
    $this->assertStringContainsString($expectedUrl, $this->subscriptionUrlFactory->getReEngagementUrl($this->subscriber));
  }

  public function testGetReEngagementUrlReturnsUrlToUserSelectedPage() {
    $settings = $this->diContainer->get(SettingsController::class);
    $postId = wp_insert_post([
      'post_title' => 'testGetReEngagementUrlReturnsUrlToUserSelectedPage',
      'post_status' => 'publish',
    ]);

    $settings->set('reEngagement', ['page' => $postId]);
    $expectedUrl = get_permalink($postId);

    $this->assertIsString($expectedUrl, "Permalink is a valid string");
    $this->assertStringContainsString($expectedUrl, $this->subscriptionUrlFactory->getReEngagementUrl($this->subscriber));
  }
}
