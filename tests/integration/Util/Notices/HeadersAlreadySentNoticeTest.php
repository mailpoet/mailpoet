<?php

namespace MailPoet\Util\Notices;

use Codeception\Util\Stub;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class HeadersAlreadySentNoticeTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->wp = new WPFunctions;
    delete_transient(HeadersAlreadySentNotice::OPTION_NAME);
  }

  public function _after() {
    delete_transient(HeadersAlreadySentNotice::OPTION_NAME);
  }

  public function testItPrintsWarningWhenHeadersAreSent() {
    $headersAlreadySentNotice = Stub::construct(
      HeadersAlreadySentNotice::class,
      [$this->settings, $this->wp],
      ['headersSent' => true]
    );
    $notice = $headersAlreadySentNotice->init(true);
    expect($notice->getMessage())->contains('It looks like there\'s an issue with some of the PHP files on your website');
    expect($notice->getMessage())->contains('https://kb.mailpoet.com/article/325-the-captcha-image-doesnt-show-up');
  }

  public function testItPrintsNoWarningWhenHeadersAreNotSent() {
    $headersAlreadySentNotice = Stub::construct(
      HeadersAlreadySentNotice::class,
      [$this->settings, $this->wp],
      ['headersSent' => false]
    );
    $notice = $headersAlreadySentNotice->init(true);
    expect($notice)->null();
  }

  public function testItPrintsWarningWhenWhitespaceIsInBuffer() {
    ob_start();
    echo "  \n \t  \r\n  ";
    $headersAlreadySentNotice = Stub::construct(
      HeadersAlreadySentNotice::class,
      [$this->settings, $this->wp],
      ['headersSent' => false]
    );
    $notice = $headersAlreadySentNotice->init(true);
    expect($notice->getMessage())->contains('It looks like there\'s an issue with some of the PHP files on your website');
    ob_end_clean();
  }

  public function testItPrintsNoWarningWhenDisabled() {
    $headersAlreadySentNotice = Stub::construct(
      HeadersAlreadySentNotice::class,
      [$this->settings, $this->wp],
      ['headersSent' => true]
    );
    $warning = $headersAlreadySentNotice->init(false);
    expect($warning)->null();
  }

  public function testItPrintsNoWarningWhenDismissed() {
    $headersAlreadySentNotice = Stub::construct(
      HeadersAlreadySentNotice::class,
      [$this->settings, $this->wp],
      ['headersSent' => true]
    );
    $headersAlreadySentNotice->disable();
    $warning = $headersAlreadySentNotice->init(true);
    expect($warning)->null();
  }

  public function testItPrintsCaptchaAndTrackingMessagesIfEnabled() {
    $headersAlreadySentNotice = Stub::make(HeadersAlreadySentNotice::class);
    $notice = $headersAlreadySentNotice->display(true, true);
    expect($notice->getMessage())->contains('Inaccurate tracking');
    expect($notice->getMessage())->contains('CAPTCHA not rendering');
  }

  public function testItPrintsNoCaptchaMessageIfCaptchaDisabled() {
    $headersAlreadySentNotice = Stub::make(HeadersAlreadySentNotice::class);
    $notice = $headersAlreadySentNotice->display(false, true);
    expect($notice->getMessage())->contains('Inaccurate tracking');
    expect($notice->getMessage())->notContains('CAPTCHA not rendering');
  }

  public function testItPrintsNoTrackingMessageIftrackingDisabled() {
    $headersAlreadySentNotice = Stub::make(HeadersAlreadySentNotice::class);
    $notice = $headersAlreadySentNotice->display(true, false);
    expect($notice->getMessage())->notContains('Inaccurate tracking');
    expect($notice->getMessage())->contains('CAPTCHA not rendering');
  }

  public function testItPrintsNoMessagesWhenCaptchaAndTrackingDisabled() {
    $headersAlreadySentNotice = Stub::make(HeadersAlreadySentNotice::class);
    $notice = $headersAlreadySentNotice->display(false, false);
    expect($notice)->null();
  }
}
