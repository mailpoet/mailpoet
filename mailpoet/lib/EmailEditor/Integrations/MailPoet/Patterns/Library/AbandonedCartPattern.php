<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;

/**
 * Abandoned cart email pattern for cart recovery.
 */
class AbandonedCartPattern extends Pattern {
  protected $name = 'abandoned-cart-content';
  protected $block_types = ['core/post-content']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $template_types = ['email-template']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $categories = ['abandoned-cart'];
  protected $post_types = [EmailEditor::MAILPOET_EMAIL_POST_TYPE]; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

  protected function get_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return '
    <!-- wp:group {"layout":{"type":"constrained"}} -->
    <div class="wp-block-group">
      <!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading ">' . __('Don‘t let this gem slip away', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph -->
      <p>' . __('You’ve already done the hard part: finding something great. Now’s the time to make it yours.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->

      <!-- wp:heading {"level":2} -->
      <h2 class="wp-block-heading ">TODO Abandoned Cart Block</h2>
      <!-- /wp:heading -->

      <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
      <div class="wp-block-buttons"><!-- wp:button -->
      <div class="wp-block-button"><a href="[mailpoet/site-homepage-url]" class="wp-block-button__link wp-element-button">' . __('Finish checkout', 'mailpoet') . '</a></div>
      <!-- /wp:button --></div>
      <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->
    ';
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    /* translators: Name of a content pattern used as starting content of an email */
    return __('Abandoned Cart', 'mailpoet');
  }
}
