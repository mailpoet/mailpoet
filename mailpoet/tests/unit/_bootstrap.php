<?php declare(strict_types = 1);

if (!function_exists('__')) {
  function __($text, $domain) {
    return $text;
  }
}
if (!function_exists('_x')) {
  function _x($text, $context, $domain) {
    return $text;
  }
}
if (!function_exists('esc_attr')) {
  function esc_attr($attr) {
    return $attr;
  }
}

// Fix for mocking WPFunctions
// [PHPUnit\Framework\Exception] Use of undefined constant OBJECT - assumed 'OBJECT' (this will throw an Error in a future version of PHP)
if (!defined('OBJECT')) {
  define( 'OBJECT', 'OBJECT' );
}

if (!defined('ABSPATH')) {
  define('ABSPATH', '/');
}

if (!defined('WPINC')) {
  define('WPINC', getenv('WP_ROOT') . '/wp-includes');
}

if (!defined('WP_DEBUG')) {
  define('WP_DEBUG', false);
}

$console = new \Codeception\Lib\Console\Output([]);

// phpcs:ignore PSR1.Classes.ClassDeclaration,Squiz.Classes.ClassFileName
abstract class MailPoetUnitTest extends \Codeception\TestCase\Test {
  protected $runTestInSeparateProcess = false;
  protected $preserveGlobalState = false;
}

include '_fixtures.php';
include '_stubs.php';
