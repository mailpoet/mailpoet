<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;

/**
 * Product purchase follow-up email pattern.
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
    return '
    <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
      <!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading">' .
      /* translators: [Product] is a placeholder for the product category */
      __('Loving your [Product]? Make it even better', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
      <p style="padding-top:0;padding-bottom:var(--wp--preset--spacing--30);font-size:16px">' .
      __('Here are a few essentials that pair perfectly.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->

      <!-- wp:heading {"textAlign":"center"} -->
      <h2 class="wp-block-heading has-text-align-center">' . __('Product name', 'mailpoet') . '</h2>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"16px"}}} -->
      <p class="has-text-align-center" style="font-size:16px">$99.90</p>
      <!-- /wp:paragraph -->

      <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
      <div class="wp-block-buttons">
      <!-- wp:button {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} -->
      <div class="wp-block-button has-custom-font-size" style="font-size:16px"><a class="wp-block-button__link wp-element-button" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20)" href="[mailpoet/site-homepage-url]">' . __('Shop now', 'mailpoet') . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->

      <!-- wp:heading {"style":{"border":{"top":{"color":"var:preset|color|cyan-bluish-gray"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|20"}},"typography":{"fontSize":"24px"}}} -->
      <h2 class="wp-block-heading" style="border-top-color:var(--wp--preset--color--cyan-bluish-gray);padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--20);font-size:24px">' . __('You might also like', 'mailpoet') . '</h2>
      <!-- /wp:heading -->

      <!-- wp:columns -->
      <div class="wp-block-columns">
      <!-- wp:column {"style":{"spacing":{"padding":{"right":"var:preset|spacing|20","left":"0"}}}} -->
      <div class="wp-block-column" style="padding-right:var(--wp--preset--spacing--20);padding-left:0"><!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->

      <!-- wp:heading {"level":3} -->
      <h3 class="wp-block-heading">' . __('Product', 'mailpoet') . '</h3>
      <!-- /wp:heading -->

      <!-- wp:heading {"level":4} -->
      <h4 class="wp-block-heading">$99.90</h4>
      <!-- /wp:heading -->

      <!-- wp:buttons {"layout":{"justifyContent":"center"}} -->
      <div class="wp-block-buttons">
      <!-- wp:button {"width":100,"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
      <div class="wp-block-button has-custom-width wp-block-button__width-100 has-custom-font-size" style="font-size:16px"><a class="wp-block-button__link wp-element-button" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)" href="[mailpoet/site-homepage-url]">' . __('Shop now', 'mailpoet') . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->
      </div>
      <!-- /wp:column -->

      <!-- wp:column {"style":{"spacing":{"padding":{"right":"0","left":"var:preset|spacing|20"}}}} -->
      <div class="wp-block-column" style="padding-right:0;padding-left:var(--wp--preset--spacing--20)"><!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->

      <!-- wp:heading {"level":3} -->
      <h3 class="wp-block-heading">' . __('Product', 'mailpoet') . '</h3>
      <!-- /wp:heading -->

      <!-- wp:heading {"level":4} -->
      <h4 class="wp-block-heading">$99.90</h4>
      <!-- /wp:heading -->

      <!-- wp:buttons {"layout":{"justifyContent":"center"}} -->
      <div class="wp-block-buttons">
      <!-- wp:button {"width":100,"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
      <div class="wp-block-button has-custom-width wp-block-button__width-100 has-custom-font-size" style="font-size:16px"><a class="wp-block-button__link wp-element-button" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)" href="[mailpoet/site-homepage-url]">' . __('Shop now', 'mailpoet') . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->
      </div>
      <!-- /wp:column -->
      </div>
      <!-- /wp:columns -->

      <!-- wp:columns -->
      <div class="wp-block-columns">
      <!-- wp:column {"style":{"spacing":{"padding":{"right":"var:preset|spacing|20","left":"0"}}}} -->
      <div class="wp-block-column" style="padding-right:var(--wp--preset--spacing--20);padding-left:0"><!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->

      <!-- wp:heading {"level":3} -->
      <h3 class="wp-block-heading">' . __('Product', 'mailpoet') . '</h3>
      <!-- /wp:heading -->

      <!-- wp:heading {"level":4} -->
      <h4 class="wp-block-heading">$99.90</h4>
      <!-- /wp:heading -->

      <!-- wp:buttons {"layout":{"justifyContent":"center"}} -->
      <div class="wp-block-buttons">
      <!-- wp:button {"width":100,"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
      <div class="wp-block-button has-custom-width wp-block-button__width-100 has-custom-font-size" style="font-size:16px"><a class="wp-block-button__link wp-element-button" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)" href="[mailpoet/site-homepage-url]">' . __('Shop now', 'mailpoet') . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->
      </div>
      <!-- /wp:column -->

      <!-- wp:column {"style":{"spacing":{"padding":{"right":"0","left":"var:preset|spacing|20"}}}} -->
      <div class="wp-block-column" style="padding-right:0;padding-left:var(--wp--preset--spacing--20)"><!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->

      <!-- wp:heading {"level":3} -->
      <h3 class="wp-block-heading">' . __('Product', 'mailpoet') . '</h3>
      <!-- /wp:heading -->

      <!-- wp:heading {"level":4} -->
      <h4 class="wp-block-heading">$99.90</h4>
      <!-- /wp:heading -->

      <!-- wp:buttons {"layout":{"justifyContent":"center"}} -->
      <div class="wp-block-buttons">
      <!-- wp:button {"width":100,"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
      <div class="wp-block-button has-custom-width wp-block-button__width-100 has-custom-font-size" style="font-size:16px"><a class="wp-block-button__link wp-element-button" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)" href="[mailpoet/site-homepage-url]">' . __('Shop now', 'mailpoet') . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->
      </div>
      <!-- /wp:column -->
      </div>
      <!-- /wp:columns -->

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
