<?php declare(strict_types = 1);

/**
 * Marketing Confirmation Email Template (Plain)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/marketing-confirmation.php.
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

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html($email_heading);
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Subscriber first name */
echo esc_html(sprintf(__('Hello %s,', 'mailpoet'), $subscriber_firstname ?: __('there', 'mailpoet')));
echo "\n\n";

/* translators: %s: Site name */
echo esc_html(sprintf(__('You\'ve received this message because you subscribed to %s. Please confirm your subscription to receive emails from us:', 'mailpoet'), get_bloginfo('name')));
echo "\n\n";

esc_html_e('Click here to confirm your subscription:', 'mailpoet');
echo "\n";
echo esc_url($activation_link);
echo "\n\n";

esc_html_e('Thank you,', 'mailpoet');
echo "\n";
echo esc_html(get_bloginfo('name'));
echo "\n";
echo esc_url(home_url());

if ($additional_content) {
    echo "\n\n" . esc_html(wp_strip_all_tags($additional_content));
}

echo "\n\n" . esc_html__('If you received this email by mistake, simply delete it. You won\'t receive any more emails from us unless you confirm your subscription using the link above.', 'mailpoet');
