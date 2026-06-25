<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\ProductCollection;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Initializer as WooCommerceBlocksInitializer;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Wires WooCommerce's email block renderers when WooCommerce registers the
 * blocks but leaves render_email_callback unset.
 *
 * WooCommerce sets render_email_callback through the block_type_metadata_settings
 * filter while blocks register. On some builds the product blocks are registered
 * before that filter is in place, so the email renderer never gets attached. The
 * email editor then falls back to WooCommerce's frontend renderer, which resolves
 * cart-contents and order collections against the live store state and renders the
 * product blocks empty during a real send.
 *
 * Mirrors CouponBlockGenerator::registerEmailRenderer for product blocks.
 */
class ProductCollectionEmailRendererRegistrar {
  private const PRODUCT_BLOCK_NAMES = [
    'woocommerce/product-collection',
    'woocommerce/product-image',
    'woocommerce/product-price',
    'woocommerce/product-button',
    'woocommerce/product-sale-badge',
  ];

  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function init(): void {
    if (!class_exists(Rendering_Context::class) || !class_exists(Email_Editor_Container::class)) {
      return;
    }

    $this->wp->addAction('woocommerce_email_editor_render_start', [$this, 'registerEmailRenderers']);
  }

  public function registerEmailRenderers(): void {
    if (!class_exists(\WP_Block_Type_Registry::class) || !class_exists(WooCommerceBlocksInitializer::class)) {
      return;
    }

    $renderer = $this->getWooCommerceBlocksRenderer();
    if (!$renderer) {
      return;
    }

    $renderEmailCallbackProperty = 'render_email_callback';
    $registry = \WP_Block_Type_Registry::get_instance();
    foreach (self::PRODUCT_BLOCK_NAMES as $blockName) {
      $blockType = $registry->get_registered($blockName);
      if (!$blockType) {
        continue;
      }

      $currentCallback = get_object_vars($blockType)[$renderEmailCallbackProperty] ?? null;
      if (!$this->needsEmailRenderer($currentCallback)) {
        continue;
      }

      // @phpstan-ignore-next-line -- WooCommerce email editor reads this dynamic block setting.
      $blockType->{$renderEmailCallbackProperty} = [$renderer, 'render_block'];
    }
  }

  private function getWooCommerceBlocksRenderer(): ?WooCommerceBlocksInitializer {
    try {
      return Email_Editor_Container::container()->get(WooCommerceBlocksInitializer::class);
    } catch (\Throwable $e) {
      return null;
    }
  }

  /**
   * Only fill the gap when no renderer is wired yet. An existing callback, whether
   * WooCommerce's own or a custom one from another integration, is left untouched.
   *
   * @param mixed $currentCallback
   */
  public function needsEmailRenderer($currentCallback): bool {
    return $currentCallback === null;
  }
}
