<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;

class TwoColumn extends Pattern {
  protected $name = '2-column-content';
  protected $block_types = ['core/post-content']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $template_types = ['email-template']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $categories = ['email-contents'];

  protected function get_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
      <div class="wp-block-group" style="padding-top:0;padding-right:var(--wp--preset--spacing--20);padding-bottom:0;padding-left:var(--wp--preset--spacing--20)"><!-- wp:heading {"fontSize":"large"} -->
      <h2 class="wp-block-heading has-large-font-size">2 column layout</h2>
      <!-- /wp:heading -->

      <!-- wp:paragraph -->
      <p>A two-column layout organizes information into sections, making it easier for users to navigate content. Try other layouts by adding or removing columns, drag blocks into them to add content and customize your email styles from the styles panel.</p>
      <!-- /wp:paragraph --></div>
      <!-- /wp:group -->

      <!-- wp:columns {"style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"var:preset|spacing|10","right":"var:preset|spacing|10"}}}} -->
      <div class="wp-block-columns" style="padding-top:0;padding-right:var(--wp--preset--spacing--10);padding-bottom:0;padding-left:var(--wp--preset--spacing--10)"><!-- wp:column {"width":"","backgroundColor":"white","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"var:preset|spacing|10","right":"var:preset|spacing|10"}}}} -->
      <div class="wp-block-column has-white-background-color has-background" style="padding-top:0;padding-right:var(--wp--preset--spacing--10);padding-bottom:0;padding-left:var(--wp--preset--spacing--10)"><!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->

      <!-- wp:buttons -->
      <div class="wp-block-buttons"><!-- wp:button {"width":100} -->
      <div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link wp-element-button">Add button text</a></div>
      <!-- /wp:button --></div>
      <!-- /wp:buttons -->

      <!-- wp:heading {"level":3,"fontSize":"large"} -->
      <h3 class="wp-block-heading has-large-font-size">Heading</h3>
      <!-- /wp:heading -->

      <!-- wp:paragraph -->
      <p>You can also add text blocks into a column next to an image block to create unique layouts.</p>
      <!-- /wp:paragraph -->

      <!-- wp:buttons -->
      <div class="wp-block-buttons"><!-- wp:button {"width":100} -->
      <div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link wp-element-button">Add button text</a></div>
      <!-- /wp:button --></div>
      <!-- /wp:buttons --></div>
      <!-- /wp:column -->

      <!-- wp:column {"backgroundColor":"white","style":{"spacing":{"padding":{"right":"var:preset|spacing|10","left":"var:preset|spacing|10"}}}} -->
      <div class="wp-block-column has-white-background-color has-background" style="padding-right:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--10)"><!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->

      <!-- wp:buttons -->
      <div class="wp-block-buttons"><!-- wp:button {"width":100} -->
      <div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link wp-element-button">Add button text</a></div>
      <!-- /wp:button --></div>
      <!-- /wp:buttons -->

      <!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image --></div>
      <!-- /wp:column --></div>
      <!-- /wp:columns -->
    ';
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    /* translators: Name of a content pattern used as starting content of an email */
    return __('2 Column', 'mailpoet');
  }
}
