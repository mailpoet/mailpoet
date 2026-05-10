<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class DeferredAdminNoticesTest extends \MailPoetUnitTest {
  /** @var WPFunctions & MockObject */
  private $wpFunctions;

  public function _before() {
    parent::_before();
    $this->wpFunctions = $this->createMock(WPFunctions::class);
    WPFunctions::set($this->wpFunctions);
  }

  public function _after() {
    WPFunctions::set(new WPFunctions());
    parent::_after();
  }

  public function testItCleansInvalidStoredNotices(): void {
    $this->wpFunctions->expects($this->once())
      ->method('getOption')
      ->with(DeferredAdminNotices::OPTIONS_KEY_NAME, [])
      ->willReturn('invalid');
    $this->wpFunctions->expects($this->once())
      ->method('deleteOption')
      ->with(DeferredAdminNotices::OPTIONS_KEY_NAME);
    $this->wpFunctions->expects($this->never())
      ->method('addAction');

    (new DeferredAdminNotices())->printAndClean();
  }

  public function testItSkipsMalformedNoticeItems(): void {
    $this->wpFunctions->expects($this->once())
      ->method('getOption')
      ->with(DeferredAdminNotices::OPTIONS_KEY_NAME, [])
      ->willReturn([
        ['message' => 'Multisite warning'],
        'invalid',
        ['unknown' => 'value'],
      ]);
    $this->wpFunctions->expects($this->once())
      ->method('addAction')
      ->with('network_admin_notices', $this->isType('array'));
    $this->wpFunctions->expects($this->once())
      ->method('deleteOption')
      ->with(DeferredAdminNotices::OPTIONS_KEY_NAME);

    (new DeferredAdminNotices())->printAndClean();
  }

  public function testItResetsInvalidStoredNoticesWhenAddingANotice(): void {
    $this->wpFunctions->expects($this->once())
      ->method('getOption')
      ->with(DeferredAdminNotices::OPTIONS_KEY_NAME, [])
      ->willReturn('invalid');
    $this->wpFunctions->expects($this->once())
      ->method('updateOption')
      ->with(DeferredAdminNotices::OPTIONS_KEY_NAME, [[
        'message' => 'Multisite warning',
        'networkAdmin' => true,
      ]]);

    (new DeferredAdminNotices())->addNetworkAdminNotice('Multisite warning');
  }
}
