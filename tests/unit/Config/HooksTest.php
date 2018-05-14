<?php
namespace MailPoet\Test\Config;

use MailPoet\Config\Hooks;
use MailPoet\WP\Posts as WPPosts;

class HooksTest extends \MailPoetTest {
  function testItHooksSchedulerToMultiplePostTypes() {
    $hooks = new Hooks();
    $hooks->setupPostNotifications();
    foreach(WPPosts::getTypes() as $post_type) {
      expect(has_filter('publish_' . $post_type, '\MailPoet\Newsletter\Scheduler\Scheduler::schedulePostNotification'))->notEmpty();
    }
  }
}