<?php
use \MailPoet\Config\Renderer;

class RendererTest extends MailPoetTest {
  function _before() {
    $this->renderer = new Renderer();
  }

  function testItWillNotEnableCacheWhenWpDebugIsOn() {
    $result = $this->renderer->detectCache();
    expect($result)->equals(false);
  }

  function _after() {
  }
}
