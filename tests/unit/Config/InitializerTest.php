<?php

class InitializerTest extends MailPoetTest {
  function testItConfiguresHooks() {
    global $wp_filter;
    $is_hooked = false;
    // mailpoet should hook to 'wp_loaded' with priority of 10
    foreach($wp_filter['wp_loaded'][10] as $name => $hook) {
      if(preg_match('/setupHooks/', $name)) $is_hooked = true;
    }
    expect($is_hooked)->true();
  }
}
