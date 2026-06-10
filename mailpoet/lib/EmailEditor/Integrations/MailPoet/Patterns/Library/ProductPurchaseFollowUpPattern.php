<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;
use MailPoet\EmailEditor\Integrations\MailPoet\ProductCollection\OrderProductCollectionProcessor;

/**
 * Product purchase follow-up email pattern.
 *
 * The product grid uses the order cross-sells collection: at send time it shows
 * cross-sells of the purchased products (or their related products as backup),
 * so the recommendations match what the customer actually bought.
 */
class ProductPurchaseFollowUpPattern extends Pattern {
  protected $name = 'product-purchase-follow-up';
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
    return $this->buildContent($this->getProductPlaceholderColumns([
      'product-small-03.jpg',
      'product-small-05.jpg',
      'product-small-04.jpg',
      'product-small-06.jpg',
    ]));
  }

  public function get_email_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return $this->buildContent($this->getRecommendedProductCollectionBlock(
      OrderProductCollectionProcessor::COLLECTION_ORDER_CROSS_SELLS,
      'popularity'
    ));
  }

  private function buildContent(string $productSection): string {
    return '
    <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
      <!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading">' .
      /* translators: PRODUCT NAME is placeholder text that merchants replace with their own content. */
      __('Loving your PRODUCT NAME? Make it even better', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
      <p style="padding-top:0;padding-bottom:var(--wp--preset--spacing--30);font-size:16px">' .
      __('Here are a few essentials that pair perfectly with your purchase.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      ' . $productSection . '

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
    return __('Product Purchase Follow-Up', 'mailpoet');
  }
}
