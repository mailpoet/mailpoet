<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;

class OneColumn extends Pattern {
  protected $name = '1-column-content';
  protected $block_types = []; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $template_types = ['email-template']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $categories = ['email-contents'];

  protected function get_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return '
    <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|20","left":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:heading -->
    <h2 class="wp-block-heading">' . __('1 column layout', 'mailpoet') . '</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>' . __('A one-column layout is great for simplified and concise content, like announcements or newsletters with brief updates. Drag blocks to add content and customize your styles from the styles panel on the top right.', 'mailpoet') . '</p>
    <!-- /wp:paragraph -->

    <!-- wp:image -->
    <figure class="wp-block-image"><img alt=""/></figure>
    <!-- /wp:image -->

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
    return __('1 Column', 'mailpoet');
  }
}
