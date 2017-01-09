<?php

class HooksTest extends MailPoetTest {
  function testItHooksSchedulerToMultiplePostTypes() {
    global $wp_filter;
    $post_types = get_post_types();
    $hook_count = 0;
    foreach($post_types as $post_type) {
      expect(!empty($wp_filter['publish_' . $post_type]))->true();
      $filter = $wp_filter['publish_' . $post_type];
      $is_hooked = false;
      foreach($filter->callbacks[10] as $name => $hook) {
        if(!preg_match('/schedulePostNotification/', $name)) continue;
        $is_hooked = true;
        $hook_count++;
      }
      expect($is_hooked)->true();
    }
    expect($hook_count)->equals(count($post_types));
  }
}