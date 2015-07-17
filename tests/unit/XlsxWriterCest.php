<?php
use \UnitTester;

class XlsxWriterCest {

    public function _before() {
    }

    public function it_can_be_created() {
      $writer = new \MailPoet\Util\XLSXWriter();
    }

    public function _after() {
    }
}
