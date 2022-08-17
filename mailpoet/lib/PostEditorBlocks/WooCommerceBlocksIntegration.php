<?php declare(strict_types=1);

namespace MailPoet\PostEditorBlocks;

use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;
use MailPoet\Config\Env;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\WooCommerce as WooSegment;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WooCommerce\Helper as WooHelper;
use MailPoet\WooCommerce\Subscription as WooCommerceSubscription;
use MailPoet\WP\Functions as WPFunctions;

class WooCommerceBlocksIntegration {
  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var WooCommerceSubscription */
  private $woocommerceSubscription;

  /** @var WooSegment */
  private $wooSegment;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var WooHelper  */
  private $wooHelper;

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings,
    WooCommerceSubscription $woocommerceSubscription,
    WooSegment $wooSegment,
    SubscribersRepository $subscribersRepository,
    WooHelper $wooHelper
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
    $this->woocommerceSubscription = $woocommerceSubscription;
    $this->wooSegment = $wooSegment;
    $this->subscribersRepository = $subscribersRepository;
    $this->wooHelper = $wooHelper;
  }

  public function init() {
    $this->wp->addAction(
      'woocommerce_blocks_checkout_block_registration',
      [$this, 'registerCheckoutFrontendBlocks']
    );
    $addDataAttributesToBlockHook = '__experimental_woocommerce_blocks_checkout_update_order_from_request';
    $hooksVersionMatrix = [
      '7.2.0' => 'woocommerce_store_api_checkout_update_order_from_request',
      '6.3.0' => 'woocommerce_blocks_checkout_update_order_from_request',
    ];
    foreach ($hooksVersionMatrix as $version => $hook) {
      if (!$this->wooHelper->isWooCommerceBlocksActive($version)) {
        continue;
      }

      $addDataAttributesToBlockHook = $hook;
      break;
    }
    $this->wp->addAction(
      $addDataAttributesToBlockHook,
      [$this, 'processCheckoutBlockOptin'],
      10,
      2
    );
    $this->wp->addFilter(
      '__experimental_woocommerce_blocks_add_data_attributes_to_block',
      [$this, 'addDataAttributesToBlock']
    );
    $block = $this->wp->registerBlockTypeFromMetadata(Env::$assetsPath . '/dist/js/marketing_optin_block');
    // We need to force the script to load in the footer. register_block_type always adds the script to the header.
    if ($block instanceof \WP_Block_Type && $block->editor_script) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $wpScripts = $this->wp->getWpScripts();
      $wpScripts->add_data($block->editor_script, 'group', 1); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }

    $this->extendRestApi();
  }

  /**
   * Load blocks in frontend with Checkout.
   */
  public function registerCheckoutFrontendBlocks($integration_registry) {
    $integration_registry->register(new MarketingOptinBlock(
      [
      'defaultText' => $this->settings->get('woocommerce.optin_on_checkout.message', ''),
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
    if (!$this->settings->get('woocommerce.optin_on_checkout.enabled', false)) {
      return;
    }

    $extend = $this->wooHelper->isWooCommerceBlocksActive('7.2') ?
      StoreApi::container()->get(ExtendSchema::class) :
      Package::container()->get(ExtendRestApi::class);
    $extend->register_endpoint_data(
      [
        'endpoint' => CheckoutSchema::IDENTIFIER,
        'namespace' => 'mailpoet',
        'schema_callback' => function () {
          return [
            'optin' => [
              'description' => __('Subscribe to marketing opt-in.', 'mailpoet'),
              'type' => 'boolean',
            ],
          ];
        },
      ]
    );
  }

  public function processCheckoutBlockOptin(\WC_Order $order, $request) {
    $checkoutOptinEnabled = (bool)$this->settings->get(WooCommerceSubscription::OPTIN_ENABLED_SETTING_NAME);
    $checkoutOptin = isset($request['extensions']['mailpoet']['optin']) ? (bool)$request['extensions']['mailpoet']['optin'] : false;

    if (!$order->get_billing_email()) {
      return;
    }

    // Fetch existing woo subscriber and in case there is not any sync as guest
    $email = $order->get_billing_email();
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $email , 'isWoocommerceUser' => true]);
    if (!$subscriber instanceof SubscriberEntity) {
      $this->wooSegment->synchronizeGuestCustomer($order->get_id());
      $subscriber = $this->subscribersRepository->findOneBy(['email' => $email , 'isWoocommerceUser' => true]);
    }

    // Subscriber not found and guest sync failed
    if (!$subscriber instanceof SubscriberEntity) {
      return null;
    }

    $this->woocommerceSubscription->handleSubscriberOptin($subscriber, $checkoutOptinEnabled, $checkoutOptin);
  }
}
