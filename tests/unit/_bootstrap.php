<?php

$console = new \Codeception\Lib\Console\Output([]);

abstract class MailPoetTest extends \Codeception\TestCase\Test {
  protected $runTestInSeparateProcess = false;
  protected $preserveGlobalState = false;
}

include '_fixtures.php';
