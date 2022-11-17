<?php declare(strict_types = 1);

namespace MailPoet\Config;

use Codeception\Stub;
use Codeception\Stub\Expected;

class PluginActivatedHookTest extends \MailPoetTest {
  public function testItAddsANewMessageIfNetworkActivation() {
    $deferredAdminNotices = Stub::makeEmpty(
      'MailPoet\Config\DeferredAdminNotices',
      [
        'addNetworkAdminNotice' => Expected::exactly(1, function () {
        }),
      ],
      $this
    );
    $hook = new PluginActivatedHook($deferredAdminNotices);
    $hook->action("mailpoet/mailpoet.php", true);
  }

  public function testItDoesntAddAMessageIfPluginNameDiffers() {
    $deferredAdminNotices = Stub::makeEmpty(
      'MailPoet\Config\DeferredAdminNotices',
      [
        'addNetworkAdminNotice' => Expected::never(),
      ],
      $this
    );
    $hook = new PluginActivatedHook($deferredAdminNotices);
    $hook->action("some/plugin.php", true);
  }

  public function testItDoesntAddAMessageIfNoNetworkActivation() {
    $deferredAdminNotices = Stub::makeEmpty(
      'MailPoet\Config\DeferredAdminNotices',
      [
        'addNetworkAdminNotice' => Expected::never(),
      ],
      $this
    );
    $hook = new PluginActivatedHook($deferredAdminNotices);
    $hook->action("mailpoet/mailpoet.php", false);
  }
}
