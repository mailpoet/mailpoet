<?php

/*
Plugin Name: MailPoet Testing Fix: Use SMTP In PHPMailer on WP 5.6
Version: 0.1
*/

add_action('phpmailer_init', 'mailpoet_test_phpmailer_use_smtp');

function mailpoet_test_phpmailer_use_smtp($phpmailer) {
    global $wp_version;

  if (!getenv('CIRCLE_BRANCH') || !preg_match('/^5\.6/', $wp_version)) {
    return;
  }

    $phpmailer->isSMTP();
    $phpmailer->Host = 'mailhog';
    $phpmailer->SMTPAuth = false;
    $phpmailer->Port = 1025;
    $phpmailer->Username = '';
    $phpmailer->Password = '';
}
