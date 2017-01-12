<?php

class HooksTest extends MailPoetTest {
  function testItHooksSchedulerToMultiplePostTypes() {
    $post_types = get_post_types();
    foreach($post_types as $post_type) {
      expect(has_filter('publish_' . $post_type, '\MailPoet\Newsletter\Scheduler\Scheduler::schedulePostNotification'))->notEmpty();
    }
  }
}