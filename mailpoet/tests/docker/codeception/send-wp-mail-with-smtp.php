<?php
/*
Plugin Name: Send wp_mail with SMTP
Version: 0.1
*/

add_action('phpmailer_init', 'mailpoet_test_phpmailer_use_smtp');

function mailpoet_test_phpmailer_use_smtp($phpmailer) {
    global $wp_version;

    $phpmailer->isSMTP();
    $phpmailer->Host = 'mailhog';
    $phpmailer->SMTPAuth = false;
    $phpmailer->Port = 1025;
    $phpmailer->Username = '';
    $phpmailer->Password = '';
}
