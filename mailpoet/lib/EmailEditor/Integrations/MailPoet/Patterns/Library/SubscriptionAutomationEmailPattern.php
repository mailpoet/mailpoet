<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;
use MailPoet\Util\CdnAssetUrl;

/**
 * Subscription automation email pattern.
 */
class SubscriptionAutomationEmailPattern extends Pattern {
  public const VARIANT_PURCHASE = 'purchase';
  public const VARIANT_RENEWAL = 'renewal';
  public const VARIANT_FAILED_RENEWAL = 'failed-renewal';

  protected $name = 'subscription-purchase-follow-up';
  protected $block_types = ['core/post-content']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $template_types = ['email-template']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $categories = ['subscriptions'];
  protected $post_types = [EmailEditor::MAILPOET_EMAIL_POST_TYPE]; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

  /** @var string */
  private $variant;

  public function __construct(
    CdnAssetUrl $cdnAssetUrl,
    string $variant
  ) {
    parent::__construct($cdnAssetUrl);
    $this->variant = $variant;

    if ($variant === self::VARIANT_RENEWAL) {
      $this->name = 'subscription-renewal-follow-up';
    } elseif ($variant === self::VARIANT_FAILED_RENEWAL) {
      $this->name = 'subscription-failed-renewal-follow-up';
    }
  }

  protected function get_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    if ($this->variant === self::VARIANT_RENEWAL) {
      return $this->buildContent(
        __('Your subscription renewed', 'mailpoet'),
        [
          sprintf(
            /* translators: %s: Subscriber first name personalization tag */
            __('Hi %s, your subscription has renewed successfully. Thanks for staying with us.', 'mailpoet'),
            $this->getSubscriberFirstNameTag()
          ),
          sprintf(
            /* translators: %s: WooCommerce subscription title personalization tag */
            __('Your %s subscription is active for the new billing period, so there is nothing you need to do right now.', 'mailpoet'),
            '<!--[mailpoet/woocommerce-subscription-title]-->'
          ),
          __('We’ll keep working to make every renewal worth it. If you have questions, reply to this email and we’ll help.', 'mailpoet'),
        ],
        __('Visit our site', 'mailpoet'),
        __('Thanks for being with us,', 'mailpoet')
      );
    }

    if ($this->variant === self::VARIANT_FAILED_RENEWAL) {
      return $this->buildContent(
        __('We couldn’t renew your subscription', 'mailpoet'),
        [
          sprintf(
            /* translators: %s: Subscriber first name personalization tag */
            __('Hi %s, we tried to renew your subscription but the payment did not go through.', 'mailpoet'),
            $this->getSubscriberFirstNameTag()
          ),
          sprintf(
            /* translators: %s: WooCommerce subscription title personalization tag */
            __('To keep %s active, please sign in to your account and update your payment details.', 'mailpoet'),
            '<!--[mailpoet/woocommerce-subscription-title]-->'
          ),
          __('If you already updated your details, you can ignore this message. If you need help, reply and we’ll take a look.', 'mailpoet'),
        ],
        __('Visit our site', 'mailpoet'),
        __('We’re here to help,', 'mailpoet')
      );
    }

    return $this->buildContent(
      __('Welcome to your subscription', 'mailpoet'),
      [
        sprintf(
          /* translators: %s: Subscriber first name personalization tag */
          __('Hi %s, thanks for subscribing. Your subscription is active, and we’re glad to have you with us.', 'mailpoet'),
          $this->getSubscriberFirstNameTag()
        ),
        sprintf(
          /* translators: %s: WooCommerce subscription title personalization tag */
          __('You’re subscribed to %s. We’ll send you useful updates, renewal reminders, and anything you need to get the most from it.', 'mailpoet'),
          '<!--[mailpoet/woocommerce-subscription-title]-->'
        ),
        __('You can review billing, renewals, and subscription details from your account on our site.', 'mailpoet'),
      ],
      __('Visit our site', 'mailpoet'),
      __('Thanks for joining us,', 'mailpoet')
    );
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    if ($this->variant === self::VARIANT_RENEWAL) {
      return __('Subscription Renewal Follow-up', 'mailpoet');
    }

    if ($this->variant === self::VARIANT_FAILED_RENEWAL) {
      return __('Subscription Failed Renewal Follow-up', 'mailpoet');
    }

    return __('Subscription Purchase Follow-up', 'mailpoet');
  }

  /**
   * @param string[] $paragraphs
   */
  private function buildContent(string $heading, array $paragraphs, string $buttonText, string $signoff): string {
    $paragraphBlocks = '';
    foreach ($paragraphs as $paragraph) {
      $paragraphBlocks .= '
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|30"}}}} -->
      <p style="padding-top:0;padding-bottom:var(--wp--preset--spacing--30);font-size:16px">' . $paragraph . '</p>
      <!-- /wp:paragraph -->';
    }

    return '
    <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
      <!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading">' . $heading . '</h1>
      <!-- /wp:heading -->

      ' . $paragraphBlocks . '

      <!-- wp:buttons {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","justifyContent":"left"}} -->
      <div class="wp-block-buttons" style="padding-bottom:var(--wp--preset--spacing--30)">
      <!-- wp:button {"style":{"typography":{"fontSize":"16px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} -->
      <div class="wp-block-button"><a class="wp-block-button__link has-custom-font-size wp-element-button" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);font-size:16px" href="[mailpoet/site-homepage-url]">' . $buttonText . '</a></div>
      <!-- /wp:button -->
      </div>
      <!-- /wp:buttons -->

      <!-- wp:paragraph {"fontSize":"medium"} -->
      <p class="has-medium-font-size">' . $signoff . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:paragraph {"fontSize":"medium"} -->
      <p class="has-medium-font-size">–<!--[woocommerce/site-title]--></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
    ';
  }

  private function getSubscriberFirstNameTag(): string {
    return sprintf(
      '<!--[mailpoet/subscriber-firstname default="%s"]-->',
      /* translators: Default placeholder used when no subscriber name is available in "Hi %s" */
      esc_attr(_x('there', 'subscriber name placeholder', 'mailpoet'))
    );
  }
}
