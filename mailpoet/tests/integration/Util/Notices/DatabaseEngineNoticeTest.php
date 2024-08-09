<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\WP\Functions as WPFunctions;

class DatabaseEngineNoticeTest extends \MailPoetTest {
  /** @var DatabaseEngineNotice */
  private $notice;

  public function _before() {
    parent::_before();
    $this->notice = new DatabaseEngineNotice(
      new WPFunctions()
    );
  }

  public function testItDoesntDisplayWhenDisabled() {
    $this->notice->disable();
    $result = $this->notice->init(true);
    verify($result)->null();
  }
}
