<?php

class UtilCSSCest {
  public function _before() {
    $this->css = new \MailPoet\Util\CSS();
  }

  // tests
  public function itCanBeInstantiated() {
    expect_that($this->css instanceof \MailPoet\Util\CSS);
  }
}
