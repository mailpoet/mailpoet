<?php declare(strict_types = 1);

$console = new \Codeception\Lib\Console\Output([]);

if (!function_exists('esc_attr')) {
  function esc_attr($attr) {
    return $attr;
  }
}

abstract class MailPoetUnitTest extends \Codeception\TestCase\Test {
  protected $runTestInSeparateProcess = false;
  protected $preserveGlobalState = false;
}

include '_stubs.php';
