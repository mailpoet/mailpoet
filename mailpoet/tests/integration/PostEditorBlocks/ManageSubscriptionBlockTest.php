<?php declare(strict_types = 1);

namespace MailPoet\PostEditorBlocks;

use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class ManageSubscriptionBlockTest extends \MailPoetTest {
  /** @var ManageSubscriptionBlock */
  private $block;

  public function _before() {
    parent::_before();
    $this->block = $this->diContainer->get(ManageSubscriptionBlock::class);
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
}
