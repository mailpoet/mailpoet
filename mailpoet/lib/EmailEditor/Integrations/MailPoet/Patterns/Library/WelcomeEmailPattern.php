<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;
use MailPoet\Util\CdnAssetUrl;

/**
 * Welcome email pattern for new subscribers.
 */
class WelcomeEmailPattern extends Pattern {
  protected $name = 'welcome-email-content';
  protected $block_types = ['core/post-content']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $template_types = ['email-template']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $categories = ['welcome'];
  protected $post_types = [EmailEditor::MAILPOET_EMAIL_POST_TYPE]; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

  /** @var string */
  private $variant;

  public function __construct(
    CdnAssetUrl $cdnAssetUrl,
    string $variant = 'welcome'
  ) {
    parent::__construct($cdnAssetUrl);
    $this->variant = $variant;
    if ($variant === 'subscription-churned') {
      $this->name = 'subscription-churned-follow-up';
      $this->categories = ['subscriptions'];
    } elseif ($variant === 'subscription-trial-ending') {
      $this->name = 'subscription-trial-ending-follow-up';
      $this->categories = ['subscriptions'];
    }
  }

  /**
   * Get pattern content.
   *
   * @return string Pattern HTML content.
   */
  protected function get_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    if ($this->variant === 'subscription-churned') {
      return $this->getSubscriptionChurnedContent();
    }

    if ($this->variant === 'subscription-trial-ending') {
      return $this->getSubscriptionTrialEndingContent();
    }

    return '
    <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
      <!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading ">' .
      /* translators: %s: Site title personalization tag */
      sprintf(__('Welcome to %s!', 'mailpoet'), '<!--[mailpoet/site-title]-->') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph -->
      <p>' .
      sprintf(
        /* translators: %s: Subscriber first name personalization tag */
        __('Hi %s, we are so glad to have you onboard.', 'mailpoet'),
        sprintf(
          '<!--[mailpoet/subscriber-firstname default="%s"]-->',
          /* translators: Default placeholder used when no subscriber name is available in "Hi %s" */
          esc_attr(_x('there', 'subscriber name placeholder', 'mailpoet'))
        )
      ) . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:image {"sizeSlug":"full"} -->
      <figure class="wp-block-image size-full"><img src="' . esc_url($this->cdnAssetUrl->generateCdnUrl('email-editor/welcome-email.jpg')) . '" alt="' . esc_attr__('Welcome email image', 'mailpoet') . '"/></figure>
      <!-- /wp:image -->

      <!-- wp:paragraph -->
      <p>' .
      /* translators: %s: Site description personalization tag */
      sprintf(__('We‘re absolutely thrilled to have you join us. Get ready to discover a world of %s that we know you‘ll love.', 'mailpoet'), '<!--[mailpoet/site-description]-->') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
      <div class="wp-block-buttons">
      <!-- wp:button {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} -->
      <div class="wp-block-button"><a class="wp-block-button__link wp-element-button has-custom-font-size" style="font-size:16px;padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20)" href="[mailpoet/site-homepage-url]">' . __('Shop now', 'mailpoet') . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->


      <!-- wp:paragraph -->
      <p>' . __('Happy shopping!', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:paragraph -->
      <p>–<!--[mailpoet/site-title]--></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
    ';
  }

  private function getSubscriptionChurnedContent(): string {
    return '
    <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
      <!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading">' . __('Can we ask why you left?', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
      <p style="padding-top:0;padding-bottom:var(--wp--preset--spacing--30);font-size:16px">' . __('We noticed your subscription ended. If something did not work for you, we would appreciate your feedback.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
      <p style="padding-top:0;padding-bottom:var(--wp--preset--spacing--30);font-size:16px">' . __('Your thoughts help us improve the experience for every subscriber.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:buttons {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","justifyContent":"left"}} -->
      <div class="wp-block-buttons" style="padding-bottom:var(--wp--preset--spacing--30)">
      <!-- wp:button {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} -->
      <div class="wp-block-button"><a class="wp-block-button__link has-custom-font-size wp-element-button" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);font-size:16px" href="[mailpoet/site-homepage-url]">' . __('Share feedback', 'mailpoet') . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->

      <!-- wp:paragraph {"fontSize":"medium"} -->
      <p class="has-medium-font-size">' . __('Thanks for helping us improve,', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:paragraph {"fontSize":"medium"} -->
      <p class="has-medium-font-size">–<!--[woocommerce/site-title]--></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
    ';
  }

  private function getSubscriptionTrialEndingContent(): string {
    return '
    <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
      <!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading">' . __('How was your trial?', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
      <p style="padding-top:0;padding-bottom:var(--wp--preset--spacing--30);font-size:16px">' . __('Your trial is ending soon. Keep your subscription active to continue enjoying everything included.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
      <p style="padding-top:0;padding-bottom:var(--wp--preset--spacing--30);font-size:16px">' . __('If you have questions before continuing, reply to this email and we will help.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:buttons {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","justifyContent":"left"}} -->
      <div class="wp-block-buttons" style="padding-bottom:var(--wp--preset--spacing--30)">
      <!-- wp:button {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} -->
      <div class="wp-block-button"><a class="wp-block-button__link has-custom-font-size wp-element-button" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);font-size:16px" href="[mailpoet/site-homepage-url]">' . __('Continue subscription', 'mailpoet') . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->

      <!-- wp:paragraph {"fontSize":"medium"} -->
      <p class="has-medium-font-size">' . __('We hope you stay with us,', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:paragraph {"fontSize":"medium"} -->
      <p class="has-medium-font-size">–<!--[woocommerce/site-title]--></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
    ';
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    if ($this->variant === 'subscription-churned') {
      return __('Subscription churned follow-up', 'mailpoet');
    }

    if ($this->variant === 'subscription-trial-ending') {
      return __('Subscription trial ending follow-up', 'mailpoet');
    }

    /* translators: Name of a content pattern used as starting content of an email */
    return __('Welcome Email', 'mailpoet');
  }
}
