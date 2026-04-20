<?php declare(strict_types = 1);

// Turn off transaction emails by defining dummy wp_mail
if (!function_exists('wp_mail')) {
  function wp_mail($to, $subject, $message, $headers = '', $attachments = []) {
    return true;
  }
}

// Load WP
require_once(getenv('WP_ROOT') . '/wp-load.php');

// Load tests_env autoload so Codeception classes used by DataGenerator are available
// when invoked outside the Codeception test container (e.g. via `./do generate:data`).
$testsEnvAutoload = dirname(__DIR__, 3) . '/tests_env/vendor/autoload.php';
if (file_exists($testsEnvAutoload)) {
  require_once $testsEnvAutoload;
}
