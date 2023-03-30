<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use Codeception\Util\Stub;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
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
    parent::_after();
    delete_transient(HeadersAlreadySentNotice::OPTION_NAME);
  }

  public function testItPrintsWarningWhenHeadersAreSent() {
    $headersAlreadySentNotice = Stub::construct(
      HeadersAlreadySentNotice::class,
      [$this->settings, $this->diContainer->get(TrackingConfig::class), $this->wp],
      ['headersSent' => true]
    );
    $notice = $headersAlreadySentNotice->init(true);
    expect($notice->getMessage())->stringContainsString('It looks like there\'s an issue with some of the PHP files on your website');
    expect($notice->getMessage())->stringContainsString('https://kb.mailpoet.com/article/325-the-captcha-image-doesnt-show-up');
  }

  public function testItPrintsNoWarningWhenHeadersAreNotSent() {
    $headersAlreadySentNotice = Stub::construct(
      HeadersAlreadySentNotice::class,
      [$this->settings, $this->diContainer->get(TrackingConfig::class), $this->wp],
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
      [$this->settings, $this->diContainer->get(TrackingConfig::class), $this->wp],
      ['headersSent' => false]
    );
    $notice = $headersAlreadySentNotice->init(true);
    expect($notice->getMessage())->stringContainsString('It looks like there\'s an issue with some of the PHP files on your website');
    ob_end_clean();
  }

  public function testItPrintsNoWarningWhenDisabled() {
    $headersAlreadySentNotice = Stub::construct(
      HeadersAlreadySentNotice::class,
      [$this->settings, $this->diContainer->get(TrackingConfig::class), $this->wp],
      ['headersSent' => true]
    );
    $warning = $headersAlreadySentNotice->init(false);
    expect($warning)->null();
  }

  public function testItPrintsNoWarningWhenDismissed() {
    $headersAlreadySentNotice = Stub::construct(
      HeadersAlreadySentNotice::class,
      [$this->settings, $this->diContainer->get(TrackingConfig::class), $this->wp],
      ['headersSent' => true]
    );
    $headersAlreadySentNotice->disable();
    $warning = $headersAlreadySentNotice->init(true);
    expect($warning)->null();
  }

  public function testItPrintsCaptchaAndTrackingMessagesIfEnabled() {
    $headersAlreadySentNotice = Stub::make(HeadersAlreadySentNotice::class);
    $notice = $headersAlreadySentNotice->display(true, true);
    expect($notice->getMessage())->stringContainsString('Inaccurate tracking');
    expect($notice->getMessage())->stringContainsString('CAPTCHA not rendering');
  }

  public function testItPrintsNoCaptchaMessageIfCaptchaDisabled() {
    $headersAlreadySentNotice = Stub::make(HeadersAlreadySentNotice::class);
    $notice = $headersAlreadySentNotice->display(false, true);
    expect($notice->getMessage())->stringContainsString('Inaccurate tracking');
    expect($notice->getMessage())->stringNotContainsString('CAPTCHA not rendering');
  }

  public function testItPrintsNoTrackingMessageIftrackingDisabled() {
    $headersAlreadySentNotice = Stub::make(HeadersAlreadySentNotice::class);
    $notice = $headersAlreadySentNotice->display(true, false);
    expect($notice->getMessage())->stringNotContainsString('Inaccurate tracking');
    expect($notice->getMessage())->stringContainsString('CAPTCHA not rendering');
  }

  public function testItPrintsNoMessagesWhenCaptchaAndTrackingDisabled() {
    $headersAlreadySentNotice = Stub::make(HeadersAlreadySentNotice::class);
    $notice = $headersAlreadySentNotice->display(false, false);
    expect($notice)->null();
  }
}
