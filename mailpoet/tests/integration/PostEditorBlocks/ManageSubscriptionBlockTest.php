<?php declare(strict_types = 1);

namespace MailPoet\PostEditorBlocks;

use MailPoet\Form\AssetsController;
use MailPoet\Subscription\Pages as SubscriptionPages;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;

class ManageSubscriptionBlockTest extends \MailPoetTest {
  /** @var ManageSubscriptionBlock */
  private $block;

  public function _before() {
    parent::_before();
    // The block service keeps its Pages instance, whose init() leaves sticky
    // state ($data, $subscriber). Pages is registered non-shared, so wiring
    // the block manually gives each test a fresh Pages and keeps results
    // independent of test order.
    $this->block = new ManageSubscriptionBlock(
      $this->diContainer->get(WPFunctions::class),
      $this->diContainer->get(SubscriptionPages::class),
      $this->diContainer->get(AssetsController::class)
    );
  }

  public function testItRendersTheManageFormForALoggedInSubscriber(): void {
    $wpUser = get_users()[0];
    (new SubscriberFactory())
      ->withEmail($wpUser->user_email) // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      ->withWpUserId((int)$wpUser->ID)
      ->create();

    wp_set_current_user((int)$wpUser->ID);
    $html = $this->block->renderManageSubscription();
    wp_set_current_user(0);

    // The manage-subscription form posts to admin-post.php with this action.
    $this->assertStringContainsString('mailpoet_subscription_update', $html);
  }

  public function testItReturnsTheSubscribersOnlyMessageForGuests(): void {
    wp_set_current_user(0);
    $this->assertStringContainsString(
      'Subscription management form is only available to mailing lists subscribers.',
      $this->block->renderManageSubscription()
    );
  }

  public function testItRendersTheDemoFormInPreviewModeForEditors(): void {
    $wpUser = get_users()[0];
    wp_set_current_user((int)$wpUser->ID);
    $html = $this->block->renderManageSubscription(['preview' => true]);
    wp_set_current_user(0);

    // Preview renders the demo form, not the current user's own subscription.
    $this->assertStringContainsString('mailpoet_subscription_update', $html);
    $this->assertStringContainsString(SubscriptionPages::DEMO_EMAIL, $html);
  }

  public function testItIgnoresThePreviewAttributeForGuests(): void {
    wp_set_current_user(0);
    // WordPress passes unregistered attributes from post content through to
    // the render callback, so preview=true must not work without edit_posts.
    $html = $this->block->renderManageSubscription(['preview' => true]);
    $this->assertStringContainsString(
      'Subscription management form is only available to mailing lists subscribers.',
      $html
    );
    $this->assertStringNotContainsString(SubscriptionPages::DEMO_EMAIL, $html);
  }
}
