<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;

/**
 * Base class for abandoned cart email patterns.
 */
abstract class AbstractAbandonedCartPattern extends Pattern {
  protected $block_types = ['core/post-content']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $template_types = ['email-template']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $categories = ['abandoned-cart'];
  protected $post_types = [EmailEditor::MAILPOET_EMAIL_POST_TYPE]; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

  /**
   * Get Product Collection block configured for cart contents.
   */
  protected function getProductCollectionBlock(): string {
    return '
      <!-- wp:woocommerce/product-collection {"query":{"perPage":10,"pages":1,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":1,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/cart-contents","hideControls":["inherit","attributes","keyword","order","default-order","featured","on-sale","stock-status","hand-picked","taxonomy","filterable","created","price-range"],"queryContextIncludes":["cart"],"__privatePreviewState":{"isPreview":false,"previewMessage":"Actual products will vary depending on the page being viewed."}} -->
      <div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
      <!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true,"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
      <!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->
      <!-- /wp:woocommerce/product-image -->

      <!-- wp:post-title {"textAlign":"center","isLink":true,"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}},"typography":{"fontSize":"24px"}},"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

      <!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","style":{"typography":{"fontSize":"14px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} /-->

      <!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true,"style":{"typography":{"fontSize":"16px"}}} /-->
      <!-- /wp:woocommerce/product-template -->
      </div>
      <!-- /wp:woocommerce/product-collection -->
    ';

  }
}
