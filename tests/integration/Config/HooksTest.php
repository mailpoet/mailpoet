<?php

namespace MailPoet\Test\Config;

use MailPoet\Config\Hooks;
use MailPoet\DI\ContainerWrapper;

class HooksTest extends \MailPoetTest {
  public function testItHooksSchedulerToMultiplePostTypes() {
    $hooks = ContainerWrapper::getInstance()->get(Hooks::class);
    $hooks->setupPostNotifications();
    expect(has_filter('transition_post_status'))->notEmpty();
  }
}
