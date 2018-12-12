<?php
namespace MailPoet\Test\Config;

use MailPoet\Config\Hooks;
use MailPoet\DI\ContainerWrapper;
use MailPoet\WP\Posts as WPPosts;

class HooksTest extends \MailPoetTest {
  function testItHooksSchedulerToMultiplePostTypes() {
    $hooks = new Hooks(ContainerWrapper::getInstance());
    $hooks->setupPostNotifications();
    expect(has_filter('transition_post_status', '\MailPoet\Newsletter\Scheduler\Scheduler::transitionHook'))->notEmpty();
  }
}
