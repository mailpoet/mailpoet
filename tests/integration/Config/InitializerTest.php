<?php

namespace MailPoet\Test\Config;

class InitializerTest extends \MailPoetTest {
  public function testItConfiguresHooks() {
    global $wp_filter; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $isHooked = false;
    // mailpoet should hook to 'wp_loaded' with priority of 10
    foreach ($wp_filter['wp_loaded'][10] as $name => $hook) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      if (preg_match('/postInitialize/', $name)) $isHooked = true;
    }
    expect($isHooked)->true();
  }
}
