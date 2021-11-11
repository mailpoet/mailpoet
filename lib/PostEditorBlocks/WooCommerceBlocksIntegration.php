<?php declare(strict_types=1);

namespace MailPoet\PostEditorBlocks;

use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;
use MailPoet\Config\Env;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\Subscription as WooCommerceSubscription;
use MailPoet\WP\Functions as WPFunctions;

class WooCommerceBlocksIntegration {
  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings,
    WooCommerceSubscription $woocommerceSubscription
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
    $this->woocommerceSubscription = $woocommerceSubscription;
  }

  public function init() {
    $this->wp->addAction(
      'woocommerce_blocks_checkout_block_registration',
      [$this, 'registerCheckoutFrontendBlocks']
    );
    $this->wp->addAction(
      'woocommerce_blocks_checkout_update_order_from_request',
      [$this, 'processCheckoutBlockOptin'],
      10,
      2
    );
    $this->wp->addFilter(
      '__experimental_woocommerce_blocks_add_data_attributes_to_block',
      [$this, 'addDataAttributesToBlock']
    );
    $this->wp->registerBlockType(Env::$assetsPath . '/js/src/marketing_optin_block');
    $this->extendRestApi();
  }

  /**
   * Load blocks in frontend with Checkout.
   */
  public function registerCheckoutFrontendBlocks($integration_registry) {
    $integration_registry->register(new MarketingOptinBlock(
      [
      'defaultText'  => $this->settings->get('woocommerce.optin_on_checkout.message', ''),
      'optinEnabled' => $this->settings->get('woocommerce.optin_on_checkout.enabled', false),
      'defaultStatus' => $this->woocommerceSubscription->isCurrentUserSubscribed(),
      ],
      $this->wp
    ));
  }

  public function addDataAttributesToBlock(array $blocks) {
    $blocks[] = 'mailpoet/marketing-optin-block';
    return $blocks;
  }

  public function extendRestApi() {
    $extend = Package::container()->get(ExtendRestApi::class);
    $extend->register_endpoint_data(
      [
        'endpoint'        => CheckoutSchema::IDENTIFIER,
        'namespace'       => 'mailpoet',
        'schema_callback' => function () {
          return [
            'optin' => [
              'description' => __('Subscribe to marketing opt-in.', 'mailpoet'),
              'type'        => 'boolean',
            ],
          ];
        },
      ]
    );
  }

  public function processCheckoutBlockOptin(\WC_Order $order, $request) {
    if (!$this->settings->get('woocommerce.optin_on_checkout.enabled', false)) {
      return;
    }

    if (!$order || !$order->get_billing_email()) {
      return;
    }

    if (!isset($request['extensions']['mailpoet']['optin'])) {
      return;
    }

    // See Subscription::subscribeOnCheckout
    $subscriber = Subscriber::where('email', $order->get_billing_email())
      ->where('is_woocommerce_user', 1)
      ->findOne();

    if (!$subscriber) {
      return null;
    }

    $this->woocommerceSubscription->handleSubscriberOptin($subscriber, (bool)$request['extensions']['mailpoet']['optin']);
  }
}
