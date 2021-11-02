<?php

namespace MailPoet\PostEditorBlocks;

use MailPoet\Config\Env;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Settings\SettingsController;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;

// @todo Disable if blocks is inactive
class NewsletterBlock {
   /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
  }

  public function init() {
    $this->wp->registerBlockType( Env::$assetsPath . '/js/src/newsletter_block' );
    $this->wp->addFilter(
      '__experimental_woocommerce_blocks_add_data_attributes_to_block',
      [$this, 'addDataAttributesToBlock']
    );
    $this->wp->addAction(
      '__experimental_woocommerce_blocks_checkout_update_order_from_request',
      [$this, 'process_checkout_block_optin']
    );
    $this->addScriptData();
    $this->extendRestApi();
  }

  public function addDataAttributesToBlock( array $blocks ) {
    $blocks[] = 'mailpoet/newsletter-block';
    return $blocks;
  }

  public function addScriptData() {
    $data_registry = Package::container()->get(
      AssetDataRegistry::class
    );
    $data_registry->add( 'mailpoet_data', array(
      'newsletterDefaultText' => $this->settings->get('woocommerce.optin_on_checkout.message', ''),
			'newsletterEnabled'     => $this->settings->get('woocommerce.optin_on_checkout.enabled', false),
    ) );
  }

  public function extendRestApi() {
    $extend = Package::container()->get(
			ExtendRestApi::class
		);
		$extend->register_endpoint_data(
			array(
				'endpoint'        => CheckoutSchema::IDENTIFIER,
				'namespace'       => 'mailpoet',
				'schema_callback' => function() {
					return array(
						'newsletter-opt-in' => array(
							'description' => __( 'Subscribe to newsletter opt-in.', 'woo-gutenberg-products-block' ),
							'type'        => 'boolean',
						),
					);
				},
			)
		);
  }

  public function process_checkout_block_optin( \WC_Order $order, array $request ) {
    if ( ! $this->settings->get('woocommerce.optin_on_checkout.enabled', false) ) {
			return;
		}

		if ( ! $order ) {
			return;
		}

		if ( ! isset( $request['extensions']['mailpoet'][ 'optin' ] ) ) {
			return;
		}

    // @todo do optin
  }
}
