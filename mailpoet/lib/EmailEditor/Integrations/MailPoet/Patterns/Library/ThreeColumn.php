<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;

class ThreeColumn extends Pattern {
  protected $name = '3-column-content';
  protected $block_types = []; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $template_types = ['email-template']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $categories = ['email-contents'];

  protected function get_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
      <div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--10);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--20)"><!-- wp:heading -->
      <h2 class="wp-block-heading">' . __('3 column layout', 'mailpoet') . '</h2>
      <!-- /wp:heading --></div>
      <!-- /wp:group -->

      <!-- wp:columns {"style":{"spacing":{"padding":{"right":"var:preset|spacing|10","left":"var:preset|spacing|10"}}},"metadata":{"categories":["email-contents"],"patternName":"mailpoet/1-column-content"}} -->
      <div class="wp-block-columns" style="padding-right:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--10)"><!-- wp:column {"style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"var:preset|spacing|10","right":"var:preset|spacing|10"}}}} -->
      <div class="wp-block-column" style="padding-top:0;padding-right:var(--wp--preset--spacing--10);padding-bottom:0;padding-left:var(--wp--preset--spacing--10)"><!-- wp:image {"scale":"cover"} -->
      <figure class="wp-block-image"><img alt="" style="object-fit:cover"/></figure>
      <!-- /wp:image --></div>
      <!-- /wp:column -->

      <!-- wp:column {"style":{"spacing":{"padding":{"right":"var:preset|spacing|10","left":"var:preset|spacing|10"}}}} -->
      <div class="wp-block-column" style="padding-right:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--10)"><!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image --></div>
      <!-- /wp:column -->

      <!-- wp:column {"style":{"spacing":{"padding":{"right":"var:preset|spacing|10","left":"var:preset|spacing|10"}}}} -->
      <div class="wp-block-column" style="padding-right:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--10)"><!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image --></div>
      <!-- /wp:column --></div>
      <!-- /wp:columns -->

      <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|20","left":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
      <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:paragraph -->
      <p>' . __('A three-column layout organizes information into sections, making it easier for users to navigate content. Try other layouts by adding or removing columns, drag blocks into them to add content, and customize your email styles from the styles panel.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:buttons -->
      <div class="wp-block-buttons"><!-- wp:button -->
      <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">' . __('Add button text', 'mailpoet') . '</a></div>
      <!-- /wp:button --></div>
      <!-- /wp:buttons --></div>
      <!-- /wp:group -->
    ';
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    /* translators: Name of a content pattern used as starting content of an email */
    return __('3 Columns', 'mailpoet');
  }
}
