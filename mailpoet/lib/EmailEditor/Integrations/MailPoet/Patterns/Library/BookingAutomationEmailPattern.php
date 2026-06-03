<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;
use MailPoet\Util\CdnAssetUrl;

/**
 * Booking automation email pattern.
 */
class BookingAutomationEmailPattern extends Pattern {
  public const VARIANT_ABANDONED_SPOT = 'abandoned-spot';
  public const VARIANT_NEW_BOOKING = 'new-booking';
  public const VARIANT_PRE_VISIT_REMINDER = 'pre-visit-reminder';

  protected $name = 'booking-abandoned-spot';
  protected $block_types = ['core/post-content']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $template_types = ['email-template']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $categories = ['bookings'];
  protected $post_types = [EmailEditor::MAILPOET_EMAIL_POST_TYPE]; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

  /** @var string */
  private $variant;

  public function __construct(
    CdnAssetUrl $cdnAssetUrl,
    string $variant
  ) {
    parent::__construct($cdnAssetUrl);
    $this->variant = $variant;

    if ($variant === self::VARIANT_NEW_BOOKING) {
      $this->name = 'booking-new-booking-follow-up';
    } elseif ($variant === self::VARIANT_PRE_VISIT_REMINDER) {
      $this->name = 'booking-pre-visit-reminder';
    }
  }

  protected function get_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    if ($this->variant === self::VARIANT_NEW_BOOKING) {
      return $this->buildContent(
        __('Your booking is confirmed', 'mailpoet'),
        [
          sprintf(
            /* translators: %s: Subscriber first name personalization tag */
            __('Hi %s, your booking is confirmed. We’re looking forward to seeing you.', 'mailpoet'),
            $this->getSubscriberFirstNameTag()
          ),
          sprintf(
            /* translators: %s: WooCommerce booking product name personalization tag */
            __('You booked %s. Keep this email handy so the details are easy to find.', 'mailpoet'),
            '<!--[mailpoet/woocommerce-booking-product-name]-->'
          ),
          $this->getBookingDetailsCopy(),
          __('If anything changes or you have questions before your visit, reply to this email and we’ll help.', 'mailpoet'),
        ],
        __('Visit our site', 'mailpoet'),
        __('See you soon,', 'mailpoet')
      );
    }

    if ($this->variant === self::VARIANT_PRE_VISIT_REMINDER) {
      return $this->buildContent(
        __('Your booking is coming up', 'mailpoet'),
        [
          sprintf(
            /* translators: %s: Subscriber first name personalization tag */
            __('Hi %s, this is a friendly reminder about your upcoming booking.', 'mailpoet'),
            $this->getSubscriberFirstNameTag()
          ),
          $this->getBookingDetailsCopy(),
          __('Please arrive a few minutes early so we can get everything started on time. Reply to this email if you need to make a change.', 'mailpoet'),
        ],
        __('Visit our site', 'mailpoet'),
        __('We’ll see you soon,', 'mailpoet')
      );
    }

    return $this->buildContent(
      __('Your booking spot is waiting', 'mailpoet'),
      [
        sprintf(
          /* translators: %s: Subscriber first name personalization tag */
          __('Hi %s, it looks like you started a booking but did not finish reserving your spot.', 'mailpoet'),
          $this->getSubscriberFirstNameTag()
        ),
        sprintf(
          /* translators: %s: WooCommerce booking product name personalization tag */
          __('If you still want to book %s, come back and complete your reservation while availability is still open.', 'mailpoet'),
          '<!--[mailpoet/woocommerce-booking-product-name]-->'
        ),
        __('Booking availability can change quickly, so finishing sooner gives you the best chance of keeping the time you selected.', 'mailpoet'),
      ],
      __('Return to our site', 'mailpoet'),
      __('Hope to see you soon,', 'mailpoet')
    );
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    if ($this->variant === self::VARIANT_NEW_BOOKING) {
      return __('Booking Confirmation Follow-up', 'mailpoet');
    }

    if ($this->variant === self::VARIANT_PRE_VISIT_REMINDER) {
      return __('Booking Pre-visit Reminder', 'mailpoet');
    }

    return __('Abandoned Booking Reminder', 'mailpoet');
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

  private function getBookingDetailsCopy(): string {
    return sprintf(
      /* translators: 1: WooCommerce booking start date tag, 2: WooCommerce booking end date tag, 3: WooCommerce booking persons count tag */
      __('Details: starts %1$s, ends %2$s, for %3$s person(s).', 'mailpoet'),
      '<!--[mailpoet/woocommerce-booking-start-date]-->',
      '<!--[mailpoet/woocommerce-booking-end-date]-->',
      '<!--[mailpoet/woocommerce-booking-persons-count]-->'
    );
  }

  private function getSubscriberFirstNameTag(): string {
    return sprintf(
      '<!--[mailpoet/subscriber-firstname default="%s"]-->',
      /* translators: Default placeholder used when no subscriber name is available in "Hi %s" */
      esc_attr(_x('there', 'subscriber name placeholder', 'mailpoet'))
    );
  }
}
