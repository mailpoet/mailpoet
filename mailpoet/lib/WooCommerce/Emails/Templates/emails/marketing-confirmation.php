<?php declare(strict_types = 1);

/**
 * Marketing Confirmation Email Template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/marketing-confirmation.php.
 *
 * @var WC_Email $email
 * @var string $email_heading
 * @var string $subscriber_firstname
 * @var string $activation_link
 * @var string $additional_content
 *
 * @package MailPoet
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// @phpcs:disable Generic.Files.InlineHTML.Found

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email); ?>

<p>
<?php
  /* translators: %s: Subscriber first name */
  echo esc_html(sprintf(__('Hello %s,', 'mailpoet'), $subscriber_firstname ?: _x('there', 'subscriber name placeholder', 'mailpoet')));
?>
</p>

<p>
<?php
  /* translators: %s: Site name */
  echo esc_html(sprintf(__('You\'ve received this message because you subscribed to %s. Please confirm your subscription to receive emails from us:', 'mailpoet'), get_bloginfo('name')));
?>
</p>

<p><a href="<?php echo esc_url($activation_link); ?>"><?php esc_html_e('Click here to confirm your subscription', 'mailpoet'); ?></a></p>

<p><?php esc_html_e('Thank you,', 'mailpoet'); ?><br>
<a href="<?php echo esc_url(home_url()); ?>"><?php echo esc_html(get_bloginfo('name')); ?></a>
</p>

<?php if ($additional_content) : ?>
    <p><?php echo wp_kses_post($additional_content); ?></p>
<?php endif; ?>

<p><?php echo esc_html__('If you received this email by mistake, simply delete it. You won\'t receive any more emails from us unless you confirm your subscription using the link above.', 'mailpoet'); ?></p>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
