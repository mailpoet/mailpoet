<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

class XlsxWriterTest extends \MailPoetUnitTest {
  public function _before() {
  }

  public function testItCanBeCreated() {
    $writer = new \MailPoetVendor\XLSXWriter();
  }

  public function _after() {
  }
}
