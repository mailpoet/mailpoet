<?php

namespace MailPoet\Test\Util;

class XlsxWriterTest extends \MailPoetUnitTest {

  public function _before() {
  }

  public function testItCanBeCreated() {
    $writer = new \MailPoet\Util\XLSXWriter();
  }

  public function _after() {
  }
}
