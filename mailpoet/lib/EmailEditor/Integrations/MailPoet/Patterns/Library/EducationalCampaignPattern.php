<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;

/**
 * Educational campaign email pattern.
 */
class EducationalCampaignPattern extends Pattern {
  protected $name = 'educational-campaign';
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
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
      <!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading">' .
      /* translators: [Product Name] is a placeholder for the product name */
      __('How to Get the Most from [Product Name]', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"}}} -->
      <p style="font-size:16px">' .
      /* translators: [product] is a placeholder for the product name */
      __('Our latest guide walks you through expert tips to make the most out of your [product].', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:heading -->
      <h2 class="wp-block-heading">' . __('How it works', 'mailpoet') . '</h2>
      <!-- /wp:heading -->

      <!-- wp:columns -->
      <div class="wp-block-columns">
      <!-- wp:column {"style":{"spacing":{"padding":{"right":"0","left":"0"}}}} -->
      <div class="wp-block-column" style="padding-right:0;padding-left:0">
      <!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->
      </div>
      <!-- /wp:column -->

      <!-- wp:column {"style":{"spacing":{"padding":{"right":"0","left":"var:preset|spacing|30"}}}} -->
      <div class="wp-block-column" style="padding-right:0;padding-left:var(--wp--preset--spacing--30)">
      <!-- wp:heading {"level":3} -->
      <h3 class="wp-block-heading">' . __('Step 1', 'mailpoet') . '</h3>
      <!-- /wp:heading -->

      <!-- wp:paragraph -->
      <p>' .
      /* translators: Placeholder text for a brief step description */
      __('[Brief description]', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->
      </div>
      <!-- /wp:column -->
      </div>
      <!-- /wp:columns -->

      <!-- wp:spacer {"height":"30px"} -->
      <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
      <!-- /wp:spacer -->

      <!-- wp:columns -->
      <div class="wp-block-columns">
      <!-- wp:column {"style":{"spacing":{"padding":{"right":"var:preset|spacing|30","left":"0"}}}} -->
      <div class="wp-block-column" style="padding-right:var(--wp--preset--spacing--30);padding-left:0">
      <!-- wp:heading {"level":3} -->
      <h3 class="wp-block-heading">' . __('Step 2', 'mailpoet') . '</h3>
      <!-- /wp:heading -->

      <!-- wp:paragraph -->
      <p>' .
      /* translators: Placeholder text for a brief step description */
      __('[Brief description]', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->
      </div>
      <!-- /wp:column -->

      <!-- wp:column {"style":{"spacing":{"padding":{"right":"0","left":"0"}}}} -->
      <div class="wp-block-column" style="padding-right:0;padding-left:0">
      <!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->
      </div>
      <!-- /wp:column -->
      </div>
      <!-- /wp:columns -->

      <!-- wp:spacer {"height":"30px"} -->
      <div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>
      <!-- /wp:spacer -->

      <!-- wp:columns -->
      <div class="wp-block-columns">
      <!-- wp:column {"style":{"spacing":{"padding":{"right":"0","left":"0"}}}} -->
      <div class="wp-block-column" style="padding-right:0;padding-left:0">
      <!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->
      </div>
      <!-- /wp:column -->

      <!-- wp:column {"style":{"spacing":{"padding":{"right":"0","left":"var:preset|spacing|30"}}}} -->
      <div class="wp-block-column" style="padding-right:0;padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"level":3} -->
      <h3 class="wp-block-heading">' . __('Step 3', 'mailpoet') . '</h3>
      <!-- /wp:heading -->

      <!-- wp:paragraph -->
      <p>' .
      /* translators: Placeholder text for a brief step description */
      __('[Brief description]', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->
      </div>
      <!-- /wp:column -->
      </div>
      <!-- /wp:columns -->

      <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
      <div class="wp-block-buttons">
      <!-- wp:button -->
      <div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="[mailpoet/site-homepage-url]">' . __('Read the guide', 'mailpoet') . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->
    ';
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    /* translators: Name of a content pattern used as starting content of an email */
    return __('Educational Campaign', 'mailpoet');
  }
}
