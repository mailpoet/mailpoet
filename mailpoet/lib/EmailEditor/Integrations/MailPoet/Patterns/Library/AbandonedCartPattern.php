<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

/**
 * Abandoned cart email pattern for cart recovery.
 */
class AbandonedCartPattern extends AbstractAbandonedCartPattern {
  protected $name = 'abandoned-cart-content';

  protected function get_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return '
    <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
      <!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading ">' . __('Don‘t let this gem slip away', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph -->
      <p>' . __('You’ve already done the hard part: finding something great. Now’s the time to make it yours.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      ' . $this->getProductCollectionBlock() . '
    </div>
    <!-- /wp:group -->
    ';
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    /* translators: Name of a content pattern used as starting content of an email */
    return __('Abandoned Cart', 'mailpoet');
  }
}
