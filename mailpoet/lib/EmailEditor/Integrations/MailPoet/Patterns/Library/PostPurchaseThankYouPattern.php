<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;

/**
 * Post purchase thank you email pattern.
 */
class PostPurchaseThankYouPattern extends Pattern {
  protected $name = 'post-purchase-thank-you';
  protected $block_types = ['core/post-content']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $template_types = ['email-template']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $categories = ['purchase'];
  protected $post_types = [EmailEditor::MAILPOET_EMAIL_POST_TYPE]; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

  /**
   * Get pattern content.
   *
   * @return string Pattern HTML content.
   */
  protected function get_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return '
    <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
      <!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading">' .
      /* translators: %s is a placeholder for the customer first name */
      sprintf(__('%s, thank you for your order', 'mailpoet'), '<!--[woocommerce/customer-first-name]-->') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
      <p style="padding-top:0;padding-bottom:var(--wp--preset--spacing--30);font-size:16px">' .
      __('Thanks for shopping with us. Your order is being processed, and we can’t wait for you to receive it.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:heading {"style":{"border":{"top":{"color":"var:preset|color|cyan-bluish-gray"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|20"}},"typography":{"fontSize":"24px"}}} -->
      <h2 class="wp-block-heading" style="border-top-color:var(--wp--preset--color--cyan-bluish-gray);padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--20);font-size:24px">' . __('You might also like', 'mailpoet') . '</h2>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
      <p style="padding-top:0;padding-bottom:var(--wp--preset--spacing--30);font-size:16px">
      ' . __('While you wait, check out other items that pair perfectly with your order.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:woocommerce/product-collection {"queryId":2,"query":{"perPage":4,"pages":1,"offset":0,"postType":"product","order":"desc","orderBy":"date","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false},"tagName":"div","displayLayout":{"type":"flex","columns":1,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/new-arrivals","hideControls":["inherit","attributes","keyword","order","default-order","featured","on-sale","stock-status","hand-picked","taxonomy","filterable","created","price-range"]} -->
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

      <!-- wp:spacer {"height":"30px"} -->
      <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
      <!-- /wp:spacer -->

      <!-- wp:paragraph {"fontSize":"medium"} -->
      <p class="has-medium-font-size">' . __('Happy shopping!', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:paragraph {"fontSize":"medium"} -->
      <p class="has-medium-font-size">–<!--[woocommerce/site-title]--></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
    ';
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    /* translators: Name of a content pattern used as starting content of an email */
    return __('Post Purchase Thank You', 'mailpoet');
  }
}
