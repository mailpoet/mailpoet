<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\PostEditorBlocks;

use MailPoet\Form\AssetsController;
use MailPoet\Subscription\Pages as SubscriptionPages;
use MailPoet\WP\Functions as WPFunctions;

// phpcs:disable Generic.Files.InlineHTML
class ManageSubscriptionBlock {
  /** @var WPFunctions */
  private $wp;

  /** @var SubscriptionPages */
  private $subscriptionPages;

  /** @var AssetsController */
  private $assetsController;

  public function __construct(
    WPFunctions $wp,
    SubscriptionPages $subscriptionPages,
    AssetsController $assetsController
  ) {
    $this->wp = $wp;
    $this->subscriptionPages = $subscriptionPages;
    $this->assetsController = $assetsController;
  }

  public function init() {
    // Registered in every context (including REST) so the editor's
    // ServerSideRender preview can render this block.
    $this->wp->registerBlockType('mailpoet/manage-subscription-block-render', [
      'render_callback' => [$this, 'renderManageSubscription'],
    ]);
  }

  public function initAdmin() {
    $this->wp->registerBlockType('mailpoet/manage-subscription-block', [
      'editor_script' => 'mailpoet/manage-subscription-block',
    ]);

    $this->wp->addAction('admin_head', function() {
      ?>
      <script type="text/javascript">
        window.mailpoetManageSubscriptionBlock = {
          title: '<?php echo esc_js(__('MailPoet Manage Subscription', 'mailpoet')); ?>',
          description: '<?php echo esc_js(__('Lets logged-in subscribers manage their lists and subscription status.', 'mailpoet')); ?>',
        };
      </script>
      <?php
    });
  }

  public function initFrontend() {
    $this->wp->registerBlockType('mailpoet/manage-subscription-block', [
      'render_callback' => [$this, 'renderManageSubscription'],
    ]);
  }

  public function renderManageSubscription(array $attributes = []): string {
    // getManageContent() does not enqueue the front-end form assets itself
    // (the subscription page flow does that separately), so load them here.
    $this->assetsController->setupFrontEndDependencies();

    // Make sure the Pages service has its internal state initialised (notably
    // $data, which isPreview() reads and which is null until init()) before
    // rendering, so it resolves the current logged-in user as a subscriber on
    // any page or the WooCommerce My Account page. Guarded so we don't clobber
    // an already-initialised shared instance (e.g. the router subscription page).
    if (!$this->subscriptionPages->isInitialized()) {
      $this->subscriptionPages->init();
    }

    return (string)$this->subscriptionPages->getManageContent();
  }
}
