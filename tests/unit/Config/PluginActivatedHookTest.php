<?php

namespace MailPoet\Config;

use Codeception\Util\Stub;

class PluginActivatedHookTest extends \MailPoetTest {


  public function testItAddsANewMessageIfNetworkActivation() {
    $deferredAdminNotices = Stub::makeEmpty(
      'MailPoet\Config\DeferredAdminNotices',
      array(
        'addNetworkAdminNotice' => Stub::exactly(1, function () {
        }),
      ),
      $this
    );
    $hook = new PluginActivatedHook($deferredAdminNotices);
    $hook->action("mailpoet", true);
  }

  public function testItDoesntAddsAMessageIfNoNetworkActivation() {
    $deferredAdminNotices = Stub::makeEmpty(
      'MailPoet\Config\DeferredAdminNotices',
      array(
        'addNetworkAdminNotice' => Stub::never(),
      ),
      $this
    );
    $hook = new PluginActivatedHook($deferredAdminNotices);
    $hook->action("mailpoet", false);
  }

}
