<?php declare(strict_types = 1);

// Load Composer autoload - we need to load it explicitly because we run the tests from the root of the project via /tests_env/vendor/bin/codecept
require_once(__DIR__ . '/../../vendor/autoload.php');

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
  define('OBJECT', 'OBJECT');
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
  protected $tester = null;
}

include '_fixtures.php';
include '_stubs.php';
