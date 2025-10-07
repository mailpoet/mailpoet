<?php declare(strict_types = 1);

/**
 * Marketing Confirmation Email Template (Blocks)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/block/marketing-confirmation.php.
 *
 * @package MailPoet
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen -- removed to prevent empty new lines.
// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd -- removed to prevent empty new lines.
// phpcs:disable Generic.Files.InlineHTML.Found
?>

<!-- wp:heading {"level":1,"textAlign":"center"} -->
<h1 class="wp-block-heading has-text-align-center"><?php
  /* translators: %s: Site title */
  printf(esc_html__('Confirm your subscription to %s', 'mailpoet'), '<!--[woocommerce/site-title]-->');
?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>
  <?php
  echo sprintf(
    /* translators: %s: Subscriber first name */
    esc_html__('Hello %s,', 'mailpoet'),
    sprintf(
      '<!--[mailpoet/subscriber-firstname default="%s"]-->',
      /* translators: %s: Default placeholder used when no subscriber name is available, e.g. "Hello there" */
      esc_html(_x('there', 'subscriber name placeholder', 'mailpoet'))
    )
  );
  ?>
<br>
<?php
  /* translators: %s: Site title */
  printf(esc_html__('You\'ve received this message because you subscribed to %s. Please confirm your subscription to receive emails from us:', 'mailpoet'), '<!--[woocommerce/site-title]-->');
?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="[mailpoet/subscriber-activation-link]"><?php echo esc_html__('Click here to confirm your subscription', 'mailpoet'); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph -->
<p><?php echo esc_html__('Thank you,', 'mailpoet'); ?><br>
<a href="[woocommerce/site-homepage-url]"><!--[woocommerce/site-title]--></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p><?php echo esc_html__('If you received this email by mistake, simply delete it. You won\'t receive any more emails from us unless you confirm your subscription using the link above.', 'mailpoet'); ?></p>
<!-- /wp:paragraph -->

