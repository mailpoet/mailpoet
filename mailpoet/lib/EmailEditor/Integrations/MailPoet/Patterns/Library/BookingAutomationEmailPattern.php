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
  public const VARIANT_PRE_VISIT_WHAT_TO_EXPECT = 'pre-visit-what-to-expect';
  public const VARIANT_PRE_VISIT_TIPS = 'pre-visit-tips';
  public const VARIANT_POST_VISIT_REVIEW = 'post-visit-review';
  public const VARIANT_NEXT_BOOKING_NUDGE = 'next-booking-nudge';

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
    } elseif ($variant === self::VARIANT_PRE_VISIT_WHAT_TO_EXPECT) {
      $this->name = 'booking-pre-visit-what-to-expect';
    } elseif ($variant === self::VARIANT_PRE_VISIT_TIPS) {
      $this->name = 'booking-pre-visit-tips';
    } elseif ($variant === self::VARIANT_POST_VISIT_REVIEW) {
      $this->name = 'booking-post-visit-review';
    } elseif ($variant === self::VARIANT_NEXT_BOOKING_NUDGE) {
      $this->name = 'booking-next-booking-nudge';
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

    if ($this->variant === self::VARIANT_PRE_VISIT_WHAT_TO_EXPECT) {
      return $this->buildContent(
        __('What to expect at your booking', 'mailpoet'),
        [
          sprintf(
            /* translators: 1: Subscriber first name personalization tag, 2: WooCommerce booking product name personalization tag */
            __('Hi %1$s, here are a few details for your upcoming %2$s booking.', 'mailpoet'),
            $this->getSubscriberFirstNameTag(),
            '<!--[mailpoet/woocommerce-booking-product-name]-->'
          ),
          $this->getBookingDetailsCopy(),
          __('Please arrive a few minutes early and bring anything you need for the visit. If you have questions, reply to this email before your appointment.', 'mailpoet'),
        ],
        __('View our site', 'mailpoet'),
        __('See you soon,', 'mailpoet')
      );
    }

    if ($this->variant === self::VARIANT_PRE_VISIT_TIPS) {
      return $this->buildContent(
        __('Make the most of your booking', 'mailpoet'),
        [
          sprintf(
            /* translators: 1: Subscriber first name personalization tag, 2: WooCommerce booking product name personalization tag */
            __('Hi %1$s, your %2$s booking is coming up soon. A little preparation can help you get the most out of it.', 'mailpoet'),
            $this->getSubscriberFirstNameTag(),
            '<!--[mailpoet/woocommerce-booking-product-name]-->'
          ),
          $this->getBookingDetailsCopy(),
          __('Review the details, plan enough time before and after your visit, and reply to this email if there is anything we should know ahead of time.', 'mailpoet'),
        ],
        __('Review details', 'mailpoet'),
        __('We’ll see you soon,', 'mailpoet')
      );
    }

    if ($this->variant === self::VARIANT_POST_VISIT_REVIEW) {
      return $this->buildContent(
        __('How was your booking?', 'mailpoet'),
        [
          sprintf(
            /* translators: 1: Subscriber first name personalization tag, 2: WooCommerce booking product name personalization tag */
            __('Hi %1$s, thanks for joining us for %2$s. We hope everything went smoothly.', 'mailpoet'),
            $this->getSubscriberFirstNameTag(),
            '<!--[mailpoet/woocommerce-booking-product-name]-->'
          ),
          $this->getBookingDetailsCopy(),
          __('Your feedback helps us improve future bookings. Send us a quick note or visit our site to leave feedback.', 'mailpoet'),
        ],
        __('Leave feedback', 'mailpoet'),
        __('Thank you,', 'mailpoet')
      );
    }

    if ($this->variant === self::VARIANT_NEXT_BOOKING_NUDGE) {
      return $this->buildContent(
        __('Ready for your next booking?', 'mailpoet'),
        [
          sprintf(
            /* translators: 1: Subscriber first name personalization tag, 2: WooCommerce booking product name personalization tag */
            __('Hi %1$s, it’s been a little while since your %2$s booking, and we’d love to welcome you back.', 'mailpoet'),
            $this->getSubscriberFirstNameTag(),
            '<!--[mailpoet/woocommerce-booking-product-name]-->'
          ),
          sprintf(
            /* translators: %s: WooCommerce booking start date personalization tag */
            __('Last time, you joined us on %s. We hope it was time well spent.', 'mailpoet'),
            '<!--[mailpoet/woocommerce-booking-start-date]-->'
          ),
          __('Whenever you’re ready for your next visit, we’ll be glad to have you. Just reply to this email if you’d like a hand picking a time.', 'mailpoet'),
        ],
        __('Book again', 'mailpoet'),
        __('Hope to see you soon,', 'mailpoet')
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

    if ($this->variant === self::VARIANT_PRE_VISIT_WHAT_TO_EXPECT) {
      return __('Booking Preparation', 'mailpoet');
    }

    if ($this->variant === self::VARIANT_PRE_VISIT_TIPS) {
      return __('Booking Tips', 'mailpoet');
    }

    if ($this->variant === self::VARIANT_POST_VISIT_REVIEW) {
      return __('Booking Review Request', 'mailpoet');
    }

    if ($this->variant === self::VARIANT_NEXT_BOOKING_NUDGE) {
      return __('Next Booking Nudge', 'mailpoet');
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
