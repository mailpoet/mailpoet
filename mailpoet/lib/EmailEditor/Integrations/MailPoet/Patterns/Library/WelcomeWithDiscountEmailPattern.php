<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;

/**
 * Welcome email pattern for new subscribers.
 */
class WelcomeWithDiscountEmailPattern extends Pattern {
  protected $name = 'welcome-with-discount-email-content';
  protected $block_types = ['core/post-content']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $template_types = ['email-template']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $categories = ['welcome'];
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
      <h1 class="wp-block-heading ">' .
      /* translators: %s: Store name personalization tag */
      sprintf(__('Welcome to %s!', 'mailpoet'), '<!--[woocommerce/store-name]-->') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph -->
      <p>' .
      /* translators: %s: Customer full name personalization tag */
      sprintf(__('Hi %s, we are so glad to have you onboard. As a thank you, here is a 10%% discount for your first purchase.', 'mailpoet'), '<!--[woocommerce/customer-full-name]-->') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:paragraph -->
      <p>' .
      /* translators: %s: Site description personalization tag */
      __('Use this code at checkout to redeem your discount:', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:woocommerce/coupon-code {"align":"left"} -->
      <div class="wp-block-woocommerce-coupon-code alignleft"></div>
      <!-- /wp:woocommerce/coupon-code -->

      <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"left"}} -->
      <div class="wp-block-buttons">
      <!-- wp:button {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} -->
      <div class="wp-block-button has-custom-font-size" style="font-size:16px"><a class="wp-block-button__link wp-element-button" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20)" href="[mailpoet/site-homepage-url]">' . __('Start shopping', 'mailpoet') . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->


      <!-- wp:paragraph -->
      <p>' . __('Happy shopping!', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:paragraph -->
      <p>–<!--[woocommerce/site-title]--></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
    ';
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    /* translators: Name of a content pattern used as starting content of an email */
    return __('Welcome with Discount', 'mailpoet');
  }
}
