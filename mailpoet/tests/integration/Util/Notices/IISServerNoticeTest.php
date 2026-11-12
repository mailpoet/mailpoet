<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class IISServerNoticeTest extends \MailPoetTest {
  /** @var IISServerNotice */
  private $notice;

  /** @var WPFunctions */
  private $wp;

  /** @var mixed */
  private $previousServerSoftware;

  public function _before() {
    parent::_before();
    $this->previousServerSoftware = $_SERVER['SERVER_SOFTWARE'] ?? null;
    $this->wp = new WPFunctions();
    $this->wp->deleteTransient(IISServerNotice::OPTION_NAME);
    $this->notice = new IISServerNotice($this->wp);
  }

  public function _after() {
    if ($this->previousServerSoftware === null) {
      unset($_SERVER['SERVER_SOFTWARE']);
    } else {
      $_SERVER['SERVER_SOFTWARE'] = $this->previousServerSoftware;
    }
    $this->wp->deleteTransient(IISServerNotice::OPTION_NAME);
    parent::_after();
  }

  public function testItDisplaysWarningOnIIS() {
    $_SERVER['SERVER_SOFTWARE'] = 'Microsoft-IIS/10.0';

    verify($this->notice->init(false))->null();
    $result = $this->notice->init(true);

    $this->assertInstanceOf(Notice::class, $result);
    verify($result->getMessage())->stringContainsString('Microsoft IIS');
  }

  public function testItDoesNotDisplayOnOtherServers() {
    $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.62 (Debian)';

    verify($this->notice->init(true))->null();
  }

  public function testItDoesNotDisplayWhenDismissed() {
    $_SERVER['SERVER_SOFTWARE'] = 'Microsoft-IIS/10.0';
    $this->notice->disable();

    verify($this->notice->init(true))->null();
  }
}
