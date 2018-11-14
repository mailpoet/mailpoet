<?php
namespace MailPoet\Test\Util;

class XlsxWriterTest extends \MailPoetTest {

  public function _before() {
  }

  public function testItCanBeCreated() {
    $writer = new \MailPoet\Util\XLSXWriter();
  }

  public function _after() {
  }
}
