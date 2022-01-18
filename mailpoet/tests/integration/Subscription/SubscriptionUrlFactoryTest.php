<?php

namespace MailPoet\Test\Subscription;

use MailPoet\Entities\SettingEntity;
use MailPoet\Entities\SubscriberEntity;
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
    $expectedUrl = '/?mailpoet_page=subscriptions&mailpoet_router&endpoint=subscription&action=re_engagement&data=';
    $this->assertStringContainsString($expectedUrl, $this->subscriptionUrlFactory->getReEngagementUrl($this->subscriber));
  }

  public function testGetReEngagementUrlReturnsUrlToUserSelectedPage() {
    global $wp_rewrite;

    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('reEngagement', ['page' => 2]);

    $pagePermaStructure = $wp_rewrite->get_page_permastruct();

    // this check is needed because on CircleCI permalinks are enabled and on the local environments not necessarily.
    if ($pagePermaStructure) {
      $expectedUrl = '/sample-page/';
    } else {
      $expectedUrl = '/?page_id=2';
    }

    $this->assertStringContainsString($expectedUrl, $this->subscriptionUrlFactory->getReEngagementUrl($this->subscriber));
  }

  public function _after() {
    parent::_after();
    $this->truncateEntity(SettingEntity::class);
  }
}
