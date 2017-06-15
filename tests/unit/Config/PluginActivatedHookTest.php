<?php

namespace MailPoet\Config;

use Codeception\Util\Stub;

class PluginActivatedHookTest extends \MailPoetTest {


  public function testItAddsANewMessageIfNetworkActivation() {
    $deferred_admin_notices = Stub::makeEmpty(
      'MailPoet\Config\DeferredAdminNotices',
      array(
        'addNetworkAdminNotice' => Stub::exactly(1, function () {
        }),
      ),
      $this
    );
    $hook = new PluginActivatedHook($deferred_admin_notices);
    $hook->action("mailpoet", true);
  }

  public function testItDoesntAddsAMessageIfNoNetworkActivation() {
    $deferred_admin_notices = Stub::makeEmpty(
      'MailPoet\Config\DeferredAdminNotices',
      array(
        'addNetworkAdminNotice' => Stub::never(),
      ),
      $this
    );
    $hook = new PluginActivatedHook($deferred_admin_notices);
    $hook->action("mailpoet", false);
  }

}
