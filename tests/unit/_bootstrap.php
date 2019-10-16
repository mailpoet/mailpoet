<?php

if (!function_exists('__')) {
  function __($text) {
    return $text;
  }
}

// Fix for mocking WPFunctions
// [PHPUnit_Framework_Exception] Use of undefined constant OBJECT - assumed 'OBJECT' (this will throw an Error in a future version of PHP)
if (!defined('OBJECT')) {
  define( 'OBJECT', 'OBJECT' );
}

if (!defined('ABSPATH')) {
  define('ABSPATH', '/');
}

if (!defined('WP_DEBUG')) {
  define('WP_DEBUG', false);
}

$console = new \Codeception\Lib\Console\Output([]);

abstract class MailPoetUnitTest extends \Codeception\TestCase\Test {
  protected $runTestInSeparateProcess = false;
  protected $preserveGlobalState = false;
}

include '_fixtures.php';
