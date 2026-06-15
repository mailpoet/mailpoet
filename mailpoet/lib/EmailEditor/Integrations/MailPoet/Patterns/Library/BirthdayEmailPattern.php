<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;
use MailPoet\Util\CdnAssetUrl;

/**
 * Birthday email pattern.
 */
class BirthdayEmailPattern extends Pattern {
  protected $name = 'birthday-email-content';
  protected $block_types = ['core/post-content']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $template_types = ['email-template']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $categories = ['celebrations'];
  protected $post_types = [EmailEditor::MAILPOET_EMAIL_POST_TYPE]; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

  /** @var bool */
  private $withDiscount;

  public function __construct(
    CdnAssetUrl $cdnAssetUrl,
    bool $withDiscount = false
  ) {
    parent::__construct($cdnAssetUrl);
    $this->withDiscount = $withDiscount;

    if ($withDiscount) {
      $this->name = 'birthday-email-with-discount';
    }
  }

  /**
   * Get pattern content.
   *
   * @return string Pattern HTML content.
   */
  protected function get_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    if ($this->withDiscount) {
      return $this->getDiscountContent();
    }

    return '
    <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
      <!-- wp:heading {"textAlign":"center","level":1} -->
      <h1 class="wp-block-heading has-text-align-center">' . __('Happy birthday!', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"18px"},"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
      <p class="has-text-align-center" style="padding-top:0;padding-bottom:var(--wp--preset--spacing--30);font-size:18px">' . __('Wishing you a day filled with good things.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
      <p style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);font-size:16px">' . __('We’re glad you’re part of our community. Here’s to another year of moments worth celebrating.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
    ';
  }

  private function getDiscountContent(): string {
    return '
    <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
      <!-- wp:heading {"textAlign":"center","level":1} -->
      <h1 class="wp-block-heading has-text-align-center">' . __('Happy birthday - here’s 10% off', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"18px"},"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
      <p class="has-text-align-center" style="padding-top:0;padding-bottom:var(--wp--preset--spacing--30);font-size:18px">' . __('A little birthday treat for your next order.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|20"}}}} -->
      <p style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--20);font-size:16px">' . __('We’re wishing you a wonderful day. Use this code for 10% off your next order:', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      ' . $this->getGeneratedCouponBlock('center', 10, 10) . '

      <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"14px"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|30"}}}} -->
      <p class="has-text-align-center" style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--30);font-size:14px">' . __('Valid for the next 10 days.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
      <div class="wp-block-buttons">
      <!-- wp:button {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} -->
      <div class="wp-block-button"><a class="wp-block-button__link has-custom-font-size wp-element-button" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);font-size:16px" href="[mailpoet/site-homepage-url]">' . __('Shop birthday picks', 'mailpoet') . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->
    ';
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    if ($this->withDiscount) {
      /* translators: Name of a content pattern used as starting content of an email */
      return __('Birthday Email with Discount', 'mailpoet');
    }

    /* translators: Name of a content pattern used as starting content of an email */
    return __('Birthday Email', 'mailpoet');
  }
}
