<?php
/**
 * Plugin Name: MailPoet Dev SMTP
 * Description: Routes wp_mail() to a local SMTP catcher (Mailpit by default on host.docker.internal:1026).
 */

add_action('phpmailer_init', function ($mailer) {
    $mailer->isSMTP();
    $mailer->Host        = defined('MAILPOET_DEV_SMTP_HOST') ? MAILPOET_DEV_SMTP_HOST : 'host.docker.internal';
    $mailer->Port        = defined('MAILPOET_DEV_SMTP_PORT') ? (int) MAILPOET_DEV_SMTP_PORT : 1026;
    $mailer->SMTPAuth    = false;
    $mailer->SMTPAutoTLS = false;
});

// PHPMailer's SMTP mode rejects `wordpress@localhost` (localhost is not a valid FQDN),
// which is the default From address on a stock wp-env install. Swap it for a FQDN
// so wp_mail() works out of the box without requiring an explicit From header.
add_filter('wp_mail_from', function ($from) {
    if (is_string($from) && substr($from, -10) === '@localhost') {
        return 'dev@mailpoet.local';
    }
    return $from;
});
