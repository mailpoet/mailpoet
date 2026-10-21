<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Templates;

/**
 * Classic editor bodies for automation template emails, keyed by the block pattern
 * the template uses in the block editor.
 */
class ClassicTemplateEmails {
  public function getBody(string $pattern): ?array {
    $blocks = $this->getContentBlocks($pattern);
    if ($blocks === null) {
      return null;
    }

    return [
      'content' => [
        'type' => 'container',
        'orientation' => 'vertical',
        'styles' => ['block' => ['backgroundColor' => 'transparent']],
        'blocks' => [
          $this->section('#f8f8f8', [$this->header()]),
          $this->section('#ffffff', array_merge([$this->spacer('30px')], $blocks, [$this->spacer('30px')])),
          $this->section('#f8f8f8', [$this->footer()]),
        ],
      ],
      'globalStyles' => [
        'text' => ['fontColor' => '#000000', 'fontFamily' => 'Arial', 'fontSize' => '16px'],
        'h1' => ['fontColor' => '#111111', 'fontFamily' => 'Arial', 'fontSize' => '30px'],
        'h2' => ['fontColor' => '#222222', 'fontFamily' => 'Arial', 'fontSize' => '24px'],
        'h3' => ['fontColor' => '#333333', 'fontFamily' => 'Arial', 'fontSize' => '22px'],
        'link' => ['fontColor' => '#21759B', 'textDecoration' => 'underline'],
        'wrapper' => ['backgroundColor' => '#ffffff'],
        'body' => ['backgroundColor' => '#eeeeee'],
      ],
    ];
  }

  private function getContentBlocks(string $pattern): ?array {
    switch ($pattern) {
      case 'welcome-email-content':
        return [
          // translators: %s: site title
          $this->heading($this->withSiteTitle(__('Welcome to %s!', 'mailpoet'))),
          // translators: %s: subscriber first name
          $this->text($this->withFirstName(__('Hi %s, we are so glad to have you onboard.', 'mailpoet'))),
          $this->text(__('We’re absolutely thrilled to have you join us.', 'mailpoet')),
          $this->button(__('Shop now', 'mailpoet')),
          $this->text(__('Happy shopping!', 'mailpoet')),
        ];
      case 'birthday-email-content':
        return [
          $this->heading(__('Happy birthday!', 'mailpoet')),
          $this->text(__('Wishing you a day filled with good things.', 'mailpoet')),
          $this->text(__('We’re glad you’re part of our community. Here’s to another year of moments worth celebrating.', 'mailpoet')),
        ];
      case 'birthday-email-with-discount':
        return [
          $this->heading(__('Happy birthday - here’s 10% off', 'mailpoet')),
          $this->text(__('We’re wishing you a wonderful day. Use this code for 10% off your next order:', 'mailpoet')),
          $this->coupon(),
          $this->text(__('Valid for the next 10 days.', 'mailpoet')),
          $this->button(__('Shop birthday picks', 'mailpoet')),
        ];
      case 'first-purchase-thank-you':
        return [
          $this->heading(__('Thank You for Your First Order', 'mailpoet')),
          // translators: %s: site title
          $this->text($this->withSiteTitle(__('We’re thrilled you chose %s. Your order is being processed, and we can’t wait for you to receive it.', 'mailpoet'))),
          $this->button(__('Shop now', 'mailpoet')),
          $this->text(__('Happy shopping!', 'mailpoet')),
        ];
      case 'post-purchase-thank-you':
        return [
          $this->heading(__('Thank you for your loyalty', 'mailpoet')),
          $this->text(__('Your continued support means a lot to us.', 'mailpoet')),
          $this->button(__('Shop now', 'mailpoet')),
          $this->text(__('Happy shopping!', 'mailpoet')),
        ];
      case 'abandoned-cart-content':
        return [
          $this->heading(__('Don‘t let this gem slip away', 'mailpoet')),
          $this->text(__('You’ve already done the hard part: finding something great. Now’s the time to make it yours.', 'mailpoet')),
          $this->abandonedCartContent(),
          $this->button(__('Complete your purchase', 'mailpoet')),
        ];
      case 'product-purchase-follow-up':
        return $this->purchaseFollowUp(
          __('Loving your purchase? Make it even better', 'mailpoet'),
          __('Here are a few essentials that pair perfectly with your purchase.', 'mailpoet')
        );
      case 'tag-purchase-follow-up':
        return $this->purchaseFollowUp(
          __('You have great taste — there is more where that came from', 'mailpoet'),
          __('We picked a few more favorites from the same collection as your order.', 'mailpoet')
        );
      case 'category-purchase-follow-up':
        return $this->purchaseFollowUp(
          __('Great choice! Here is more you might love', 'mailpoet'),
          __('We picked a few more favorites from the same category as your order.', 'mailpoet')
        );
      case 'abandoned-cart-reminder-content':
        return [
          $this->heading(__('Still thinking it over?', 'mailpoet')),
          $this->text(__('The items in your cart are still here — but we can’t hold them forever. Popular pieces tend to sell out quickly, so now’s a great time to come back and make them yours.', 'mailpoet')),
          $this->abandonedCartContent(),
          $this->text(__('Checkout only takes a minute, and it’s always secure. If anything’s holding you back, just reply to this email — we’re happy to help.', 'mailpoet')),
          $this->button(__('Complete your purchase', 'mailpoet')),
        ];
      case 'abandoned-cart-with-discount-content':
        return [
          $this->heading(__('We Saved Your Cart + Little Surprise', 'mailpoet')),
          $this->text(__('Good news — your cart is still here! Even better? You can get 10% off if you check out in the next 24 hours.', 'mailpoet')),
          $this->text(__('Use this code at checkout to redeem your discount:', 'mailpoet')),
          $this->coupon(),
          $this->button(__('Complete your purchase', 'mailpoet')),
          $this->abandonedCartContent(),
        ];
      case 'educational-campaign':
        return [
          // translators: %s: site title
          $this->heading($this->withSiteTitle(__('How to Get the Most from %s', 'mailpoet'))),
          // translators: %s: site title
          $this->text($this->withSiteTitle(__('Our latest guide walks you through tips to make the most from %s.', 'mailpoet'))),
          $this->subheading(__('How it works', 'mailpoet')),
          $this->text('<strong>' . __('Step 1', 'mailpoet') . '</strong><br />' . __('Brief description', 'mailpoet')),
          $this->text('<strong>' . __('Step 2', 'mailpoet') . '</strong><br />' . __('Brief description', 'mailpoet')),
          $this->text('<strong>' . __('Step 3', 'mailpoet') . '</strong><br />' . __('Brief description', 'mailpoet')),
          $this->button(__('Learn more', 'mailpoet')),
        ];
      case 'ask-for-review-post-purchase':
        return [
          $this->heading(__('How was your experience?', 'mailpoet')),
          $this->text(__('Thanks again for your order. Your feedback helps other shoppers choose with confidence.', 'mailpoet')),
          $this->text(__('If you have a minute, your review would mean a lot.', 'mailpoet')),
          $this->button(__('Leave a review', 'mailpoet')),
          $this->text(__('We appreciate your time and hope to see you again soon.', 'mailpoet')),
        ];
      case 'positive-review-follow-up':
        return [
          $this->heading(__('Thanks for your review!', 'mailpoet')),
          $this->text(__('Your review made our day. We’re thrilled you had a good experience and grateful that you took the time to share it.', 'mailpoet')),
          $this->text(__('Reviews like yours help other shoppers choose with confidence. Thanks for being part of our community.', 'mailpoet')),
          $this->text(__('With appreciation,', 'mailpoet') . '<br />[site:title]'),
        ];
      case 'negative-review-follow-up':
        return [
          $this->heading(__('Sorry to hear that', 'mailpoet')),
          $this->text(__('Thank you for being honest in your review. We’re sorry your experience did not meet expectations.', 'mailpoet')),
          $this->text(__('We’d like to understand what happened and see how we can make things right. Reply to this email and we’ll take care of it.', 'mailpoet')),
          $this->text(__('We appreciate the chance to improve,', 'mailpoet') . '<br />[site:title]'),
        ];
      case 'reward-positive-reviewer':
        return [
          $this->heading(__('Thanks for your review!', 'mailpoet')),
          $this->text(__('Thank you for taking the time to leave such a thoughtful review. We’re grateful for your support.', 'mailpoet')),
          $this->text(__('As a thank you, here’s a discount coupon for your next order:', 'mailpoet')),
          $this->coupon(),
          $this->button(__('Shop now', 'mailpoet')),
          $this->text(__('See you soon,', 'mailpoet') . '<br />[site:title]'),
        ];
      case 'win-back-customer':
        return [
          $this->heading(__('We Miss You! Here’s 15% Off', 'mailpoet')),
          $this->text(__('We’ve got an exclusive deal waiting just for you.', 'mailpoet')),
          $this->text(__('Use this code at checkout to redeem your discount:', 'mailpoet')),
          $this->coupon(15),
          $this->button(__('Shop now', 'mailpoet')),
          $this->text(__('Happy shopping!', 'mailpoet')),
        ];
      case 'win-back-customer-reminder':
        return [
          $this->heading(__('We miss you', 'mailpoet')),
          $this->text(__('It’s been a little while since your last visit, and we’d love to welcome you back.', 'mailpoet')),
          $this->text(__('New favorites may be waiting for you in the shop.', 'mailpoet')),
          $this->button(__('Shop now', 'mailpoet')),
          $this->text(__('See you soon,', 'mailpoet') . '<br />[site:title]'),
        ];
      case 'win-back-customer-final-nudge':
        return [
          $this->heading(__('Still thinking it over?', 'mailpoet')),
          $this->text(__('There’s still time to find something you’ll love.', 'mailpoet')),
          $this->text(__('Come back when you’re ready and continue where you left off.', 'mailpoet')),
          $this->button(__('Shop now', 'mailpoet')),
          $this->text(__('Happy shopping!', 'mailpoet')),
        ];
      case 'booking-abandoned-spot':
        return $this->letter(
          __('Your booking spot is waiting', 'mailpoet'),
          // translators: %s: subscriber first name
          $this->withFirstName(__('Hi %s, it looks like you started a booking but did not finish reserving your spot.', 'mailpoet')),
          __('Booking availability can change quickly, so finishing sooner gives you the best chance of keeping the time you selected.', 'mailpoet'),
          __('Return to our site', 'mailpoet'),
          __('Hope to see you soon,', 'mailpoet')
        );
      case 'booking-new-booking-follow-up':
        return $this->letter(
          __('Your booking is confirmed', 'mailpoet'),
          // translators: %s: subscriber first name
          $this->withFirstName(__('Hi %s, your booking is confirmed. We’re looking forward to seeing you.', 'mailpoet')),
          __('If anything changes or you have questions before your visit, reply to this email and we’ll help.', 'mailpoet'),
          __('Visit our site', 'mailpoet'),
          __('See you soon,', 'mailpoet')
        );
      case 'booking-pre-visit-reminder':
        return $this->letter(
          __('Your booking is coming up', 'mailpoet'),
          // translators: %s: subscriber first name
          $this->withFirstName(__('Hi %s, this is a friendly reminder about your upcoming booking.', 'mailpoet')),
          __('Please arrive a few minutes early so we can get everything started on time. Reply to this email if you need to make a change.', 'mailpoet'),
          __('Visit our site', 'mailpoet'),
          __('We’ll see you soon,', 'mailpoet')
        );
      case 'booking-pre-visit-what-to-expect':
        return $this->letter(
          __('What to expect at your booking', 'mailpoet'),
          // translators: %s: subscriber first name
          $this->withFirstName(__('Hi %s, here are a few details for your upcoming booking.', 'mailpoet')),
          __('Please arrive a few minutes early and bring anything you need for the visit. If you have questions, reply to this email before your appointment.', 'mailpoet'),
          __('View our site', 'mailpoet'),
          __('See you soon,', 'mailpoet')
        );
      case 'booking-pre-visit-tips':
        return $this->letter(
          __('Make the most of your booking', 'mailpoet'),
          // translators: %s: subscriber first name
          $this->withFirstName(__('Hi %s, your booking is coming up soon. A little preparation can help you get the most out of it.', 'mailpoet')),
          __('Review the details, plan enough time before and after your visit, and reply to this email if there is anything we should know ahead of time.', 'mailpoet'),
          __('Review details', 'mailpoet'),
          __('We’ll see you soon,', 'mailpoet')
        );
      case 'booking-post-visit-review':
        return $this->letter(
          __('How was your booking?', 'mailpoet'),
          // translators: %s: subscriber first name
          $this->withFirstName(__('Hi %s, thanks for joining us. We hope everything went smoothly.', 'mailpoet')),
          __('Your feedback helps us improve future bookings. Send us a quick note or visit our site to leave feedback.', 'mailpoet'),
          __('Leave feedback', 'mailpoet'),
          __('Thank you,', 'mailpoet')
        );
      case 'booking-next-booking-nudge':
        return $this->letter(
          __('Ready for your next booking?', 'mailpoet'),
          // translators: %s: subscriber first name
          $this->withFirstName(__('Hi %s, it’s been a little while since your last booking, and we’d love to welcome you back.', 'mailpoet')),
          __('Whenever you’re ready for your next visit, we’ll be glad to have you. Just reply to this email if you’d like a hand picking a time.', 'mailpoet'),
          __('Book again', 'mailpoet'),
          __('Hope to see you soon,', 'mailpoet')
        );
      case 'subscription-purchase-follow-up':
        return $this->letter(
          __('Welcome to your subscription', 'mailpoet'),
          // translators: %s: subscriber first name
          $this->withFirstName(__('Hi %s, thanks for subscribing. Your subscription is active, and we’re glad to have you with us.', 'mailpoet')),
          __('You can review billing, renewals, and subscription details from your account on our site.', 'mailpoet'),
          __('Visit our site', 'mailpoet'),
          __('Thanks for joining us,', 'mailpoet')
        );
      case 'subscription-renewal-follow-up':
        return $this->letter(
          __('Your subscription renewed', 'mailpoet'),
          // translators: %s: subscriber first name
          $this->withFirstName(__('Hi %s, your subscription has renewed successfully. Thanks for staying with us.', 'mailpoet')),
          __('We’ll keep working to make every renewal worth it. If you have questions, reply to this email and we’ll help.', 'mailpoet'),
          __('Visit our site', 'mailpoet'),
          __('Thanks for being with us,', 'mailpoet')
        );
      case 'subscription-failed-renewal-follow-up':
        return $this->letter(
          __('We couldn’t renew your subscription', 'mailpoet'),
          // translators: %s: subscriber first name
          $this->withFirstName(__('Hi %s, we tried to renew your subscription but the payment did not go through.', 'mailpoet')),
          __('To keep your subscription active, please sign in to your account and update your payment details. If you already updated them, you can ignore this message.', 'mailpoet'),
          __('Visit our site', 'mailpoet'),
          __('We’re here to help,', 'mailpoet')
        );
      case 'subscription-churned-follow-up':
        return $this->letter(
          __('We’d value your feedback', 'mailpoet'),
          // translators: %s: subscriber first name
          $this->withFirstName(__('Hi %s, we noticed your subscription has ended. We’re sorry to see you go.', 'mailpoet')),
          __('You can reply directly to this email. Every note helps us improve the experience for future subscribers.', 'mailpoet'),
          __('Visit our site', 'mailpoet'),
          __('Thanks for your feedback,', 'mailpoet')
        );
      case 'subscription-trial-ended-follow-up':
        return $this->letter(
          __('Your trial has ended', 'mailpoet'),
          // translators: %s: subscriber first name
          $this->withFirstName(__('Hi %s, thanks for trying us. We hope your trial gave you a useful look at what’s included.', 'mailpoet')),
          __('Still deciding? Reply with any questions and we’ll help you choose the next step.', 'mailpoet'),
          __('Visit our site', 'mailpoet'),
          __('Thanks for trying us,', 'mailpoet')
        );
      case 'subscription-win-back':
        return $this->letter(
          __('See what’s new', 'mailpoet'),
          // translators: %s: subscriber first name
          $this->withFirstName(__('Hi %s, it’s been a while since your subscription ended, and we’d love to welcome you back.', 'mailpoet')),
          __('When you’re ready, visit our site to see what’s changed and start again.', 'mailpoet'),
          __('Visit our site', 'mailpoet'),
          __('Hope to see you again,', 'mailpoet')
        );
      default:
        return null;
    }
  }

  // Heading, greeting, one paragraph, a button and a sign-off. Used by the booking and subscription emails.
  private function letter(string $heading, string $greeting, string $text, string $buttonText, string $signOff): array {
    return [
      $this->heading($heading),
      $this->text($greeting),
      $this->text($text),
      $this->button($buttonText),
      $this->text($signOff . '<br />[site:title]'),
    ];
  }

  private function purchaseFollowUp(string $heading, string $text): array {
    return [
      $this->heading($heading),
      $this->text($text),
      $this->button(__('Shop now', 'mailpoet')),
      $this->text(__('Happy shopping!', 'mailpoet')),
    ];
  }

  private function withSiteTitle(string $text): string {
    return sprintf($text, '[site:title]');
  }

  private function withFirstName(string $text): string {
    return sprintf($text, sprintf(
      '[subscriber:firstname | default:%s]',
      _x('there', 'subscriber name placeholder', 'mailpoet')
    ));
  }

  private function section(string $backgroundColor, array $blocks): array {
    return [
      'type' => 'container',
      'orientation' => 'horizontal',
      'styles' => ['block' => ['backgroundColor' => $backgroundColor]],
      'blocks' => [
        [
          'type' => 'container',
          'orientation' => 'vertical',
          'styles' => ['block' => ['backgroundColor' => 'transparent']],
          'blocks' => $blocks,
        ],
      ],
    ];
  }

  private function heading(string $text): array {
    return $this->textBlock('<h1 style="text-align: center;"><strong>' . $text . '</strong></h1>');
  }

  private function subheading(string $text): array {
    return $this->textBlock('<h2>' . $text . '</h2>');
  }

  private function text(string $text): array {
    return $this->textBlock('<p>' . $text . '</p>');
  }

  private function textBlock(string $html): array {
    return ['type' => 'text', 'text' => $html];
  }

  private function spacer(string $height): array {
    return [
      'type' => 'spacer',
      'styles' => ['block' => ['backgroundColor' => 'transparent', 'height' => $height]],
    ];
  }

  private function button(string $text, string $url = '[site:homepage_url]'): array {
    return [
      'type' => 'button',
      'text' => $text,
      'url' => $url,
      'styles' => [
        'block' => [
          'backgroundColor' => '#2ea1cd',
          'borderColor' => '#0074a2',
          'borderWidth' => '0px',
          'borderRadius' => '5px',
          'borderStyle' => 'solid',
          'width' => '220px',
          'lineHeight' => '50px',
          'fontColor' => '#ffffff',
          'fontFamily' => 'Arial',
          'fontSize' => '18px',
          'fontWeight' => 'bold',
          'textAlign' => 'center',
        ],
      ],
    ];
  }

  private function coupon(int $amount = 10): array {
    return [
      'type' => 'coupon',
      'source' => 'createNew',
      'code' => 'XXXX-XXXXXXX-XXXX',
      'amount' => $amount,
      'amountMax' => 100,
      'discountType' => 'percent',
      'expiryDay' => 10,
      'usageLimit' => '',
      'usageLimitPerUser' => '',
      'minimumAmount' => '',
      'maximumAmount' => '',
      'emailRestrictions' => '',
      'productIds' => [],
      'excludedProductIds' => [],
      'productCategoryIds' => [],
      'excludedProductCategoryIds' => [],
      'styles' => [
        'block' => [
          'backgroundColor' => '#ffffff',
          'borderColor' => '#000000',
          'borderWidth' => '2px',
          'borderRadius' => '5px',
          'borderStyle' => 'dashed',
          'width' => '288px',
          'lineHeight' => '50px',
          'fontColor' => '#000000',
          'fontFamily' => 'Courier New',
          'fontSize' => '24px',
          'fontWeight' => 'bold',
          'textAlign' => 'center',
        ],
      ],
    ];
  }

  private function abandonedCartContent(): array {
    return [
      'type' => 'abandonedCartContent',
      'withLayout' => true,
      'amount' => '2',
      'contentType' => 'product',
      'postStatus' => 'publish',
      'inclusionType' => 'include',
      'displayType' => 'excerpt',
      'titleFormat' => 'h3',
      'titleAlignment' => 'left',
      'titleIsLink' => false,
      'imageFullWidth' => false,
      'titlePosition' => 'aboveExcerpt',
      'featuredImagePosition' => 'left',
      'pricePosition' => 'below',
      'readMoreType' => 'none',
      'readMoreText' => '',
      'readMoreButton' => [],
      'sortBy' => 'newest',
      'showDivider' => true,
      'divider' => [
        'type' => 'divider',
        'styles' => [
          'block' => [
            'backgroundColor' => 'transparent',
            'padding' => '13px',
            'borderStyle' => 'solid',
            'borderWidth' => '1px',
            'borderColor' => '#dddddd',
          ],
        ],
        'context' => 'abandonedCartContent.divider',
      ],
      'backgroundColor' => '#ffffff',
      'backgroundColorAlternate' => '#ffffff',
    ];
  }

  private function header(): array {
    return [
      'type' => 'header',
      'text' => '<a href="[link:newsletter_view_in_browser_url]">' . __('View this in your browser.', 'mailpoet') . '</a>',
      'styles' => [
        'block' => ['backgroundColor' => 'transparent'],
        'text' => ['fontColor' => '#222222', 'fontFamily' => 'Arial', 'fontSize' => '12px', 'textAlign' => 'center'],
        'link' => ['fontColor' => '#6cb7d4', 'textDecoration' => 'underline'],
      ],
    ];
  }

  private function footer(): array {
    return [
      'type' => 'footer',
      'text' => '<p><a href="[link:subscription_unsubscribe_url]">' . __('Unsubscribe', 'mailpoet') . '</a> | <a href="[link:subscription_manage_url]">' . __('Manage your subscription', 'mailpoet') . '</a><br />[site:title]</p>',
      'styles' => [
        'block' => ['backgroundColor' => 'transparent'],
        'text' => ['fontColor' => '#222222', 'fontFamily' => 'Arial', 'fontSize' => '12px', 'textAlign' => 'center'],
        'link' => ['fontColor' => '#6cb7d4', 'textDecoration' => 'none'],
      ],
    ];
  }
}
