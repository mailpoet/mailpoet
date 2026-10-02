<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscription;

use MailPoet\Config\Populator;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\MailPoetPageResolver;
use MailPoet\Settings\Pages as SettingPages;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class SubscriptionUrlFactoryTest extends \MailPoetTest {
  /** @var SubscriberEntity */
  private $subscriber;

  /** @var SubscriptionUrlFactory */
  private $subscriptionUrlFactory;

  /** @var MailPoetPageResolver */
  private $resolver;

  /** @var SettingsController */
  private $settings;

  /** @var array<int, array{status: string, name: string}> */
  private $snapshot = [];

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->resolver = $this->diContainer->get(MailPoetPageResolver::class);
    foreach ($this->allPages() as $page) {
      $this->snapshot[(int)$page->ID] = ['status' => $page->post_status, 'name' => $page->post_name]; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
    $this->diContainer->get(Populator::class)->up();
    $this->settings->resetCache();
    $this->resolver->resetCache();
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

  public function _after() {
    foreach ($this->allPages() as $page) {
      if (!isset($this->snapshot[(int)$page->ID])) {
        wp_delete_post((int)$page->ID, true);
      }
    }
    foreach ($this->snapshot as $id => $data) {
      if (get_post($id)) {
        wp_update_post(['ID' => $id, 'post_status' => $data['status'], 'post_name' => $data['name']]);
        continue;
      }
      SettingPages::createMailPoetPage(preg_replace('/__trashed$/', '', $data['name']));
    }
    $this->snapshot = [];
    $this->settings->resetCache();
    $this->resolver->resetCache();
    parent::_after();
  }

  public function testItUsesDefaultPageWhenConfiguredPageIsTrashed() {
    $trashed = $this->createPage('trashed-confirmation');
    wp_trash_post($trashed);
    $this->settings->set('subscription.pages.confirmation', $trashed);
    $this->resolver->resetCache();

    $url = $this->subscriptionUrlFactory->getConfirmationUrl($this->subscriber);

    $this->assertStringContainsString($this->defaultPermalink(), $url);
    $this->assertStringNotContainsString('trashed-confirmation', $url);
    $this->assertStringContainsString('action=confirm', $url);
  }

  public function testItUsesDefaultPageWhenConfiguredPageIsDraft() {
    $draft = $this->createPage('draft-manage', 'draft');
    $this->settings->set('subscription.pages.manage', $draft);
    $this->resolver->resetCache();

    $url = $this->subscriptionUrlFactory->getManageUrl($this->subscriber);

    $this->assertStringContainsString($this->defaultPermalink(), $url);
    $this->assertStringNotContainsString('draft-manage', $url);
  }

  public function testItUsesDefaultPageWhenConfiguredPageIsDeleted() {
    $deleted = $this->createPage('deleted-unsubscribe');
    wp_delete_post($deleted, true);
    $this->settings->set('subscription.pages.unsubscribe', $deleted);
    $this->resolver->resetCache();

    $url = $this->subscriptionUrlFactory->getUnsubscribeUrl($this->subscriber);

    $this->assertStringContainsString($this->defaultPermalink(), $url);
    $this->assertStringContainsString('action=unsubscribe', $url);
  }

  public function testItFallsBackToHomeUrlWhenNoMailPoetPageExists() {
    foreach ($this->allPages() as $page) {
      wp_delete_post((int)$page->ID, true);
    }
    foreach (['confirmation', 'confirm_unsubscribe', 'manage', 'unsubscribe'] as $key) {
      $this->settings->set('subscription.pages.' . $key, 999999);
    }
    $this->settings->set('reEngagement', ['page' => 999999]);
    $this->resolver->resetCache();
    $home = home_url('/');

    $expected = [
      'confirm' => $this->subscriptionUrlFactory->getConfirmationUrl($this->subscriber),
      'manage' => $this->subscriptionUrlFactory->getManageUrl($this->subscriber),
      'unsubscribe' => $this->subscriptionUrlFactory->getUnsubscribeUrl($this->subscriber),
      'confirm_unsubscribe' => $this->subscriptionUrlFactory->getConfirmUnsubscribeUrl($this->subscriber),
      're_engagement' => $this->subscriptionUrlFactory->getReEngagementUrl($this->subscriber),
    ];
    foreach ($expected as $action => $url) {
      $this->assertIsString($url, $action);
      $this->assertStringStartsWith($home, $url, $action);
      $this->assertStringContainsString('action=' . $action, $url, $action);
    }
  }

  public function testConfirmationOverrideToUnpublishedPageUsesGlobalPage() {
    $global = $this->createPage('global-confirmation');
    $this->settings->set('subscription.pages.confirmation', $global);
    $draft = $this->createPage('draft-override', 'draft');
    $this->resolver->resetCache();

    $url = $this->subscriptionUrlFactory->getConfirmationUrl($this->subscriber, $draft);

    $this->assertStringContainsString((string)get_permalink($global), $url);
    $this->assertStringNotContainsString('draft-override', $url);
  }

  public function testConfirmationOverrideToPublishedPageIsUsed() {
    $override = $this->createPage('published-override');

    $url = $this->subscriptionUrlFactory->getConfirmationUrl($this->subscriber, $override);

    $this->assertStringContainsString((string)get_permalink($override), $url);
  }

  public function testGetSubscriptionUrlKeepsConcretePost() {
    $draft = $this->createPage('concrete-draft', 'draft');
    $post = get_post($draft);
    $this->assertInstanceOf(\WP_Post::class, $post);

    $url = $this->subscriptionUrlFactory->getSubscriptionUrl($post, 'confirm', $this->subscriber);

    $this->assertStringContainsString((string)get_permalink($post), $url);
    $this->assertStringContainsString('action=confirm', $url);
  }

  private function defaultPermalink(): string {
    $default = SettingPages::getMailPoetPage(SettingPages::PAGE_SUBSCRIPTIONS);
    $this->assertInstanceOf(\WP_Post::class, $default);
    return (string)get_permalink($default);
  }

  private function createPage(string $name, string $status = 'publish'): int {
    return (int)wp_insert_post([
      'post_title' => $name,
      'post_name' => $name,
      'post_status' => $status,
      'post_type' => 'page',
    ]);
  }

  /** @return \WP_Post[] */
  private function allPages(): array {
    return get_posts([
      'post_type' => 'mailpoet_page',
      'post_status' => ['publish', 'draft', 'pending', 'private', 'future', 'trash'],
      'numberposts' => -1,
    ]);
  }
}
