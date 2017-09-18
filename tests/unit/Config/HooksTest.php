<?php
namespace MailPoet\Test\Config;

use MailPoet\Config\Hooks;

class HooksTest extends \MailPoetTest {
  function testItHooksSchedulerToMultiplePostTypes() {
    $hooks = new Hooks();
    $hooks->setupPostNotifications();
    $post_types = get_post_types();
    foreach($post_types as $post_type) {
      expect(has_filter('publish_' . $post_type, '\MailPoet\Newsletter\Scheduler\Scheduler::schedulePostNotification'))->notEmpty();
    }
  }
}