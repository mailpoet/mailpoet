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
