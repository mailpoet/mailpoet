<?php

namespace MailPoet\Config;

use Codeception\Stub;
use Codeception\Stub\Expected;

class PluginActivatedHookTest extends \MailPoetTest {


  public function testItAddsANewMessageIfNetworkActivation() {
    $deferred_admin_notices = Stub::makeEmpty(
      'MailPoet\Config\DeferredAdminNotices',
      [
        'addNetworkAdminNotice' => Expected::exactly(1, function () {
        }),
      ],
      $this
    );
    $hook = new PluginActivatedHook($deferred_admin_notices);
    $hook->action("mailpoet/mailpoet.php", true);
  }

  public function testItDoesntAddAMessageIfPluginNameDiffers() {
    $deferred_admin_notices = Stub::makeEmpty(
      'MailPoet\Config\DeferredAdminNotices',
      [
        'addNetworkAdminNotice' => Expected::never(),
      ],
      $this
    );
    $hook = new PluginActivatedHook($deferred_admin_notices);
    $hook->action("some/plugin.php", true);
  }

  public function testItDoesntAddAMessageIfNoNetworkActivation() {
    $deferred_admin_notices = Stub::makeEmpty(
      'MailPoet\Config\DeferredAdminNotices',
      [
        'addNetworkAdminNotice' => Expected::never(),
      ],
      $this
    );
    $hook = new PluginActivatedHook($deferred_admin_notices);
    $hook->action("mailpoet/mailpoet.php", false);
  }

}
