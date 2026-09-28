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
          $this->heading(__('Welcome to [site:title]!', 'mailpoet')),
          $this->text(__('Hi [subscriber:firstname | default:there], we are so glad to have you onboard.', 'mailpoet')),
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
          $this->text(__('We’re thrilled you chose [site:title]. Your order is being processed, and we can’t wait for you to receive it.', 'mailpoet')),
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
      default:
        return null;
    }
  }

  private function purchaseFollowUp(string $heading, string $text): array {
    return [
      $this->heading($heading),
      $this->text($text),
      $this->button(__('Shop now', 'mailpoet')),
      $this->text(__('Happy shopping!', 'mailpoet')),
    ];
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

  private function button(string $text): array {
    return [
      'type' => 'button',
      'text' => $text,
      'url' => '[site:homepage_url]',
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

  private function coupon(): array {
    return [
      'type' => 'coupon',
      'source' => 'createNew',
      'code' => 'XXXX-XXXXXXX-XXXX',
      'amount' => 10,
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
