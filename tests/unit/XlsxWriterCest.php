<?php

class XlsxWriterCest {

    public function _before() {
    }

    public function itCanBeCreated() {
      $writer = new \MailPoet\Util\XLSXWriter();
    }

    public function _after() {
    }
}
