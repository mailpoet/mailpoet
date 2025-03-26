<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class Product implements CategoryInterface {
  /** @var WooCommerceHelper */
  private $wooCommerceHelper;

  public function __construct(
    WooCommerceHelper $wooCommerceHelper,
  ) {
    $this->wooCommerceHelper = $wooCommerceHelper;
  }

  public function process(
    array $shortcodeDetails,
    ?NewsletterEntity $newsletter = null,
    ?SubscriberEntity $subscriber = null,
    ?SendingQueueEntity $queue = null,
    string $content = '',
    bool $wpUserPreview = false
  ): ?string {
    // If WooCommerce is not active, return the original shortcode
    if (!$this->wooCommerceHelper->isWooCommerceActive()) {
      return $shortcodeDetails['shortcode'];
    }

    // Get default value if specified
    $defaultValue = ($shortcodeDetails['action_argument'] === 'default') ?
      $shortcodeDetails['action_argument_value'] :
      '';

    // Get product ID from the newsletter's metadata if available
    $productId = null;
    if ($newsletter && $newsletter->getOptions()) {
      $options = $newsletter->getOptions();
      if (isset($options['productId'])) {
        $productId = $options['productId'];
      }
    }

    if (!$productId) {
      return $defaultValue;
    }

    // Try to get WooCommerce product
    $product = null;
    try {
      $product = wc_get_product($productId);
    } catch (\Exception $e) {
      // Product not found or error, return default value
      return $defaultValue;
    }

    if (!$product) {
      return $defaultValue;
    }

    // Process different product fields
    switch ($shortcodeDetails['action']) {
      case 'name':
        return !empty($product->get_name()) ?
          htmlspecialchars($product->get_name(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) : 
          $defaultValue;

      case 'description':
        return !empty($product->get_description()) ?
          wp_kses_post($product->get_description()) : 
          $defaultValue;

      case 'short_description':
        return !empty($product->get_short_description()) ?
          wp_kses_post($product->get_short_description()) : 
          $defaultValue;

      case 'price':
        return $product->get_price_html();

      case 'regular_price':
        return $product->get_regular_price();

      case 'sale_price':
        return $product->get_sale_price();

      case 'sku':
        return !empty($product->get_sku()) ?
          htmlspecialchars($product->get_sku(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) : 
          $defaultValue;

      default:
        return $shortcodeDetails['shortcode'];
    }
  }
}
