<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

/**
 * Second abandoned cart reminder email pattern for cart recovery.
 */
class AbandonedCartReminderPattern extends AbstractAbandonedCartPattern {
  protected $name = 'abandoned-cart-reminder-content';

  protected function get_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return $this->buildContent($this->getProductPlaceholderBlocks(['newsletter.jpg']));
  }

  public function get_email_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return $this->buildContent($this->getProductCollectionBlock());
  }

  private function buildContent(string $productSection): string {
    return '
    <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
      <!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading ">' . __('Still thinking it over?', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph -->
      <p>' . __('The items in your cart are still here — but we can’t hold them forever. Popular pieces tend to sell out quickly, so now’s a great time to come back and make them yours.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      ' . $productSection . '

      <!-- wp:paragraph -->
      <p>' . __('Checkout only takes a minute, and it’s always secure. If anything’s holding you back, just reply to this email — we’re happy to help.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
    ';
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    /* translators: Name of a content pattern used as starting content of an email */
    return __('Abandoned Cart Reminder', 'mailpoet');
  }
}
