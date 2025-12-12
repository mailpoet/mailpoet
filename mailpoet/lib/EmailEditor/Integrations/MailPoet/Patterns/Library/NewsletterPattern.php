<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\EmailEditor\Integrations\MailPoet\Patterns\Pattern;

/**
 * Newsletter email pattern for regular communications.
 */
class NewsletterPattern extends Pattern {
  protected $name = 'newsletter-content';
  protected $block_types = ['core/post-content']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $template_types = ['email-template']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  protected $categories = ['basic'];
  protected $post_types = [EmailEditor::MAILPOET_EMAIL_POST_TYPE]; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

  /**
   * Get pattern content.
   *
   * @return string Pattern HTML content.
   */
  protected function get_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return '
    <!-- wp:group {"layout":{"type":"constrained"}} -->
    <div class="wp-block-group">
      <!-- wp:heading {"level":1} -->
      <h1 class="wp-block-heading">' . __('Weekly Newsletter', 'mailpoet') . '</h1>
      <!-- /wp:heading -->

      <!-- wp:paragraph -->
      <p>' . __('Welcome to our weekly newsletter! Stay updated with the latest trends in hair care, styling tips, and exclusive offers.', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:image -->
      <figure class="wp-block-image"><img alt=""/></figure>
      <!-- /wp:image -->

      <!-- wp:paragraph -->
      <p>' . __('This week, we explore new products that enhance shine and promote healthy hair growth. Don‘t miss out on our exclusive offers tailored just for you!', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:heading {"level":2} -->
      <h2 class="wp-block-heading">' . __('Summer trends', 'mailpoet') . '</h2>
      <!-- /wp:heading -->

      <!-- wp:paragraph -->
      <p>' . __('Discover the latest in skincare with our innovative formulas that hydrate and rejuvenate. Join us for special discounts available for a limited time!', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:paragraph -->
      <p>' . __('Unveil a fresh approach to wellness with our cutting-edge supplements designed to boost energy and support your immune system. Take advantage of our introductory pricing!', 'mailpoet') . '</p>
      <!-- /wp:paragraph -->

      <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
      <div class="wp-block-buttons"><!-- wp:button -->
      <div class="wp-block-button"><a href="[mailpoet/site-homepage-url]" class="wp-block-button__link wp-element-button">' . __('Visit our store', 'mailpoet') . '</a></div>
      <!-- /wp:button --></div>
      <!-- /wp:buttons -->
    </div>
    <!-- /wp:group -->
    ';
  }

  protected function get_title(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    /* translators: Name of a content pattern used as starting content of an email */
    return __('Newsletter', 'mailpoet');
  }
}
