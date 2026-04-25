<?php declare(strict_types = 1);

// Turn off transaction emails by defining dummy wp_mail
if (!function_exists('wp_mail')) {
  function wp_mail($to, $subject, $message, $headers = '', $attachments = []) {
    return true;
  }
}

// Load WP
require_once(getenv('WP_ROOT') . '/wp-load.php');
