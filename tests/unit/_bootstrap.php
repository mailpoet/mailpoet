<?php

function __($text) {
  return $text;
}

// Fix for mocking WPFunctions
// [PHPUnit_Framework_Exception] Use of undefined constant OBJECT - assumed 'OBJECT' (this will throw an Error in a future version of PHP)
if (!defined('OBJECT')) {
  define( 'OBJECT', 'OBJECT' );
}

define('ABSPATH', '/');

$console = new \Codeception\Lib\Console\Output([]);

abstract class MailPoetUnitTest extends \Codeception\TestCase\Test {
  protected $runTestInSeparateProcess = false;
  protected $preserveGlobalState = false;
}

include '_fixtures.php';
