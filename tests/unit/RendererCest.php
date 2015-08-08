<?php
use \UnitTester;
use \MailPoet\Config\Renderer;

class RendererCest {
  function _before() {
    $this->renderer = new Renderer();
  }

  function itWillNotEnableCacheWhenWpDebugIsOn() {
    $result = $this->renderer->detectCache();
    expect($result)->equals(false);
  }

  function _after() {
  }
}
