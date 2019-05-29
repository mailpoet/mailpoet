<?php

function __($text) {
  return $text;
}

define('ABSPATH', '/');

$console = new \Codeception\Lib\Console\Output([]);

abstract class MailPoetUnitTest extends \Codeception\TestCase\Test {
  protected $runTestInSeparateProcess = false;
  protected $preserveGlobalState = false;
}

include '_fixtures.php';
