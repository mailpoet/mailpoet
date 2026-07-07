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
      'attributes' => [
        'preview' => [
          'type' => 'boolean',
          'default' => false,
        ],
      ],
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

    if (!empty($attributes['preview']) && $this->wp->currentUserCan('edit_posts')) {
      // The editor's ServerSideRender preview passes preview=true so it shows a
      // representative demo form instead of the current admin's own subscription
      // (or the "subscribers only" message when the admin isn't a subscriber).
      // Force the demo regardless of any prior Pages state. WordPress passes
      // attributes that a block type doesn't register through to the render
      // callback verbatim, so hand-written block markup in content can carry
      // preview=true; the capability check (the same one the block-renderer
      // REST endpoint enforces) keeps visitors from forcing the demo form.
      $this->subscriptionPages->init(false, ['preview' => true]);
    } elseif (!$this->subscriptionPages->isInitialized()) {
      // Make sure the Pages instance has its internal state initialised (notably
      // $data, which isPreview() reads and which is null until init()) before
      // rendering, so it resolves the current logged-in user as a subscriber on
      // any page or the WooCommerce My Account page. Pages is registered
      // non-shared, so this instance belongs to this block alone and persists
      // only across renders within a single request; the guard just avoids
      // re-initialising it on repeated renders.
      $this->subscriptionPages->init();
    }

    return (string)$this->subscriptionPages->getManageContent();
  }
}
