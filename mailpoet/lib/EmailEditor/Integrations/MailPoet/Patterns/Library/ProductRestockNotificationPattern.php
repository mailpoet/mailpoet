<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;

/**
 * Product restock notification email pattern.
 */
class ProductRestockNotificationPattern extends Pattern {
  protected $name = 'product-restock-notification';
  protected $block_types = ['core/post-content']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $template_types = ['email-template']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $categories = ['newsletter'];
  protected $post_types = [EmailEditor::MAILPOET_EMAIL_POST_TYPE]; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

  /**
   * Get pattern content.
   *
   * @return string Pattern HTML content.
   */
  protected function get_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return '
    <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading">' .
      /* translators: [Product Name] is a placeholder for the product name */
      __('[Product Name] Is back in stock!', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
      <p style="font-size:16px">' . __('One of our most-loved items just returned. Hurry before it sells out again!', 'mailpoet') . '</p>
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
      <!-- wp:button -->
      <div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="[mailpoet/site-homepage-url]">' . __('Shop now', 'mailpoet') . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->
    ';
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    /* translators: Name of a content pattern used as starting content of an email */
    return __('Product Restock Notification', 'mailpoet');
  }
}
