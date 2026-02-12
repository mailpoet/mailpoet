<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;

/**
 * Win Back Customer email pattern.
 */
class WinBackCustomerPattern extends Pattern {
  protected $name = 'win-back-customer';
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
      <h1 class="wp-block-heading">' . __('We Miss You! Here’s 15% Off ', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
      <p style="padding-top:0;padding-bottom:var(--wp--preset--spacing--30);font-size:16px">' .
      /* translators: %s is a placeholder for the first name */
      sprintf(__('Hi %s, come see what’s new — we’ve added fresh arrivals and exclusive deals just for you.', 'mailpoet'), '<!--[woocommerce/customer-first-name]-->') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
      <p style="font-size:16px">' .
      /* translators: %s: Site description personalization tag */
      __('Use this code at checkout to redeem your discount:', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:woocommerce/coupon-code {"align":"left"} -->
      <div class="wp-block-woocommerce-coupon-code alignleft"></div>
      <!-- /wp:woocommerce/coupon-code -->

      <!-- wp:buttons {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","justifyContent":"left"}} -->
      <div class="wp-block-buttons" style="padding-bottom:var(--wp--preset--spacing--30)">
      <!-- wp:button {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} -->
      <div class="wp-block-button"><a class="wp-block-button__link has-custom-font-size wp-element-button" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);font-size:16px" href="[mailpoet/site-homepage-url]">' . __('Start shopping', 'mailpoet') . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->

      <!-- wp:heading {"style":{"border":{"top":{"color":"var:preset|color|cyan-bluish-gray"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|20"}},"typography":{"fontSize":"24px"}}} -->
      <h2 class="wp-block-heading" style="border-top-color:var(--wp--preset--color--cyan-bluish-gray);padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--20);font-size:24px">' . __('You might also like', 'mailpoet') . '</h2>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
      <p style="padding-top:0;padding-bottom:var(--wp--preset--spacing--30);font-size:16px">
      ' . __('While you wait, check out other items that pair perfectly with your order.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:woocommerce/product-collection {"query":{"perPage":4,"pages":1,"offset":0,"postType":"product","order":"desc","orderBy":"date","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false},"tagName":"div","displayLayout":{"type":"flex","columns":1,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/new-arrivals","hideControls":["inherit","attributes","keyword","order","default-order","featured","on-sale","stock-status","hand-picked","taxonomy","filterable","created","price-range"]} -->
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
    return __('Win Back Customer', 'mailpoet');
  }
}
