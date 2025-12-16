<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;

/**
 * Welcome email pattern for new subscribers.
 */
class WelcomeEmailPattern extends Pattern {
  protected $name = 'welcome-email-content';
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
    <!-- wp:group {"layout":{"type":"constrained"}} -->
    <div class="wp-block-group">
      <!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading ">' . __('Welcome to <!--[woocommerce/store-name]-->!', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph -->
      <p>' . __('Hi <!--[woocommerce/customer-full-name]-->, we are so glad to have you onboard.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->

      <!-- wp:paragraph -->
      <p>' . __('We‘re absolutely thrilled to have you join us. Get ready to discover a world of <!--[mailpoet/site-description]--> that we know you‘ll love.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
      <div class="wp-block-buttons"><!-- wp:button -->
      <div class="wp-block-button"><a href="[mailpoet/site-homepage-url]" class="wp-block-button__link wp-element-button">' . __('Shop now', 'mailpoet') . '</a></div>
      <!-- /wp:button --></div>
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
    return __('Welcome Email', 'mailpoet');
  }
}
