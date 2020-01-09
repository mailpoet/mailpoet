<?php

namespace MailPoet\Test\Config;

class InitializerTest extends \MailPoetTest {
  public function testItConfiguresHooks() {
    global $wpFilter;
    $isHooked = false;
    // mailpoet should hook to 'wp_loaded' with priority of 10
    foreach ($wpFilter['wp_loaded'][10] as $name => $hook) {
      if (preg_match('/postInitialize/', $name)) $isHooked = true;
    }
    expect($isHooked)->true();
  }
}
